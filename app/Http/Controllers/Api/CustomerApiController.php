<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SalePayment;
use Illuminate\Support\Facades\DB;

class CustomerApiController extends Controller
{
    /**
     * Get all products with variants.
     */
    public function products()
    {
        $products = Product::with(['variants' => function ($query) {
            $query->where('status', true)->with('unit');
        }])->where('status', true)->get();

        return response()->json(['products' => $products]);
    }

    /**
     * Create a pending order.
     */
    public function storeOrder(Request $request)
    {
        \Illuminate\Support\Facades\Log::info('storeOrder payload:', $request->all());
        $request->validate([
            'items' => 'required|array|min:1',
            'items.*.product_variant_id' => 'required|exists:product_variants,id',
            'items.*.qty' => 'required|numeric|min:1',
            'items.*.unit_price' => 'required|numeric|min:0',
            'delivery_method' => 'nullable|string|in:manual,pickup,own_delivery,steadfast',
            'delivery_type' => 'nullable|integer|in:0,1',
        ]);

        $customer = $request->user();

        try {
            DB::beginTransaction();

            $subtotal = 0;
            $itemsData = [];

            foreach ($request->items as $item) {
                $total_price = $item['qty'] * $item['unit_price'];
                $subtotal += $total_price;

                $itemsData[] = [
                    'product_variant_id' => $item['product_variant_id'],
                    'qty' => $item['qty'],
                    'unit_price' => $item['unit_price'],
                    'total_price' => $total_price,
                ];
            }

            $deliveryMethod = $request->input('delivery_method', 'manual');
            $deliveryType = $request->input('delivery_type', 1); // Default to point delivery
            $deliveryCharge = 0;

            if ($deliveryMethod === 'steadfast') {
                if ($deliveryType == 0) {
                    $grandTotalWeight = 0;
                    foreach ($request->items as $item) {
                        $variant = \App\Models\ProductVariant::find($item['product_variant_id']);
                        $unitQty = $variant ? $variant->getBaseQuantity() : 1;
                        $grandTotalWeight += ($item['qty'] * $unitQty);
                    }
                    $deliveryCharge = max(1, ceil($grandTotalWeight)) * 20;
                } else {
                    $deliveryCharge = 0;
                }
            }

            $total = $subtotal + $deliveryCharge;

            // Create pending sale (order)
            $sale = Sale::create([
                'invoice_no' => 'ORD-' . strtoupper(uniqid()),
                'customer_id' => $customer->id,
                'warehouse_id' => 1, // Default warehouse or null if nullable
                'date' => now()->toDateString(),
                'subtotal' => $subtotal,
                'discount' => 0,
                'delivery_charge' => $deliveryCharge,
                'total' => $total,
                'paid_amount' => 0,
                'due_amount' => $total,
                'payment_status' => 'due',
                'created_by' => \App\Models\User::first()->id ?? 1, // Fallback to 1 if no admin exists
                'source' => 'customer',
                'estimate_delivery_date' => $request->input('estimate_delivery_date'),
                'delivery_status' => $deliveryMethod === 'steadfast' ? 'accepted' : 'pending',
                'delivery_method' => $deliveryMethod,
                'delivery_type' => $deliveryType,
                'shipping_address' => $request->input('shipping_address'),
            ]);

            // Create sale items
            foreach ($itemsData as $itemData) {
                $itemData['sale_id'] = $sale->id;
                SaleItem::create($itemData);
            }

            // Create Journal for Sale
            $journal = \App\Models\Journal::create([
                'journal_no' => 'JNL-' . strtoupper(\Illuminate\Support\Str::random(6)),
                'date' => $sale->date,
                'reference_type' => Sale::class,
                'reference_id' => $sale->id,
                'notes' => 'Customer App Order ' . $sale->invoice_no,
                'created_by' => \App\Models\User::first()->id ?? 1,
            ]);
            
            $arAcc = \App\Models\ChartOfAccount::where('name', 'Accounts Receivable')->first();
            $salesAcc = \App\Models\ChartOfAccount::where('name', 'Sales Revenue')->first();

            if ($arAcc && $salesAcc) {
                // AR Debit
                \App\Models\JournalEntry::create([
                    'journal_id' => $journal->id,
                    'account_id' => $arAcc->id,
                    'description' => 'Customer App Order ' . $sale->invoice_no,
                    'type' => 'debit',
                    'amount' => $total,
                ]);

                // Sales Revenue Credit
                \App\Models\JournalEntry::create([
                    'journal_id' => $journal->id,
                    'account_id' => $salesAcc->id,
                    'description' => 'Customer App Order ' . $sale->invoice_no,
                    'type' => 'credit',
                    'amount' => $total,
                ]);
            }

            // Update customer total due
            $customer->total_due += $total;
            $customer->save();

            DB::commit();

            try {
                $admins = \App\Models\User::all();
                \Illuminate\Support\Facades\Notification::send($admins, new \App\Notifications\NewOrderNotification($sale));
            } catch (\Exception $e) {
                // Ignore notification errors to not break order placement
            }

            return response()->json([
                'message' => 'Order created successfully.',
                'order' => $sale->load('items.productVariant')
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            \Illuminate\Support\Facades\Log::error('API storeOrder failed: ' . $e->getMessage() . "\n" . $e->getTraceAsString());
            return response()->json([
                'message' => 'Failed to create order.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get all orders for the authenticated customer.
     */
    public function orders(Request $request)
    {
        $orders = Sale::where('customer_id', $request->user()->id)
            ->orderBy('id', 'desc')
            ->get();

        return response()->json(['orders' => $orders]);
    }

    /**
     * Get order details.
     */
    public function orderDetails(Request $request, $id)
    {
        $order = Sale::with(['items.productVariant.product', 'payments'])
            ->where('customer_id', $request->user()->id)
            ->where('id', $id)
            ->firstOrFail();

        return response()->json(['order' => $order]);
    }

    /**
     * Get all payments made by the customer.
     */
    public function payments(Request $request)
    {
        // Fetch all payments for this customer's sales
        $payments = SalePayment::whereHas('sale', function ($query) use ($request) {
            $query->where('customer_id', $request->user()->id);
        })->with('sale:id,invoice_no,total')->orderBy('id', 'desc')->get();

        return response()->json(['payments' => $payments]);
    }

    /**
     * Get customer due amount.
     */
    public function dues(Request $request)
    {
        $customer = $request->user();
        
        return response()->json([
            'total_due' => $customer->total_due,
            'wallet_balance' => $customer->wallet_balance,
            'credit_limit' => $customer->credit_limit,
            'customer' => $customer,
        ]);
    }

    /**
     * Change customer password.
     */
    public function changePassword(Request $request)
    {
        $request->validate([
            'old_password' => 'required|string',
            'new_password' => 'required|string|min:6',
        ]);

        $customer = $request->user();

        if (!\Illuminate\Support\Facades\Hash::check($request->old_password, $customer->password)) {
            return response()->json(['error' => 'Incorrect old password.'], 400);
        }

        $customer->password = \Illuminate\Support\Facades\Hash::make($request->new_password);
        $customer->save();

        return response()->json(['message' => 'Password changed successfully.']);
    }
    /**
     * Get Customer Notifications.
     */
    public function notifications(Request $request)
    {
        $customer = $request->user();
        if (!$customer) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }

        $notifications = $customer->notifications()->take(50)->get();
        $unreadCount = $customer->unreadNotifications()->count();

        return response()->json([
            'notifications' => $notifications,
            'unread_count' => $unreadCount
        ]);
    }

    /**
     * Mark customer notifications as read.
     */
    public function markNotificationsRead(Request $request)
    {
        $customer = $request->user();
        if ($customer) {
            $customer->unreadNotifications->markAsRead();
        }
        return response()->json(['success' => true]);
    }

    /**
     * Get Customer Ledger.
     */
    public function ledger(Request $request)
    {
        $customer = $request->user();
        if (!$customer) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }

        $customer->load(['sales' => function ($query) {
            $query->orderBy('date', 'asc');
        }]);

        $arAcc = \App\Models\ChartOfAccount::where('name', 'Accounts Receivable')->first();
        $advAcc = \App\Models\ChartOfAccount::where('name', 'Customer Advance')->first();
        $arId = $arAcc ? $arAcc->id : 0;
        $advId = $advAcc ? $advAcc->id : 0;

        $journals = \App\Models\Journal::with(['entries', 'reference'])
            ->where(function($q) use ($customer) {
                $q->where('reference_type', \App\Models\Customer::class)->where('reference_id', $customer->id);
            })->orWhere(function($q) use ($customer) {
                $q->where('reference_type', \App\Models\Sale::class)->whereIn('reference_id', $customer->sales()->pluck('id'));
            })
            ->get();

        $ledgerEntries = collect();
        $runningBalance = 0;

        foreach ($journals as $journal) {
            $debit = 0;
            $credit = 0;

            if ($journal->reference_type == \App\Models\Sale::class) {
                $sale = $journal->reference;
                if ($sale && $sale->total >= 0) {
                    $runningBalance += $sale->total;
                    $ledgerEntries->push((object)[
                        'id' => $journal->id . '_sale',
                        'journal' => $journal,
                        'debit' => $sale->total,
                        'credit' => 0,
                        'running_balance' => $runningBalance,
                    ]);
                }

                $initialPaymentAmount = \App\Models\SalePayment::where('sale_id', $sale->id)
                    ->where(function($q) {
                        $q->whereNull('reference')
                          ->orWhereIn('reference', ['POS Payment', 'Wallet Payment']);
                    })
                    ->sum('amount');

                $hasJournal = $journals->contains(function($j) use ($sale) {
                    return str_contains($j->notes, 'Payment for POS Sale ' . $sale->invoice_no);
                });

                if ($initialPaymentAmount > 0 && !$hasJournal) {
                    $runningBalance -= $initialPaymentAmount;
                    $paymentJournal = clone $journal;
                    $paymentJournal->notes = 'Payment for ' . $sale->invoice_no;
                    
                    $ledgerEntries->push((object)[
                        'id' => $journal->id . '_pay',
                        'journal' => $paymentJournal,
                        'debit' => 0,
                        'credit' => $initialPaymentAmount,
                        'running_balance' => $runningBalance,
                    ]);
                }
                continue;
            } else {
                if ($journal->notes == 'Opening Balance') {
                    $debit = $customer->opening_balance;
                } else {
                    $credit = $journal->entries->whereIn('account_id', [$arId, $advId])->where('type', 'credit')->sum('amount');
                    $debit = $journal->entries->whereIn('account_id', [$arId, $advId])->where('type', 'debit')->sum('amount');
                }
            }

            $internalTransferAmount = 0;
            if ($debit > 0 && $credit > 0) {
                $internalTransferAmount = min($debit, $credit);
                if ($debit > $credit) {
                    $debit = $debit - $credit;
                    $credit = 0;
                } else if ($credit > $debit) {
                    $credit = $credit - $debit;
                    $debit = 0;
                } else {
                    $debit = 0;
                    $credit = 0;
                }
            }

            if ($debit == 0 && $credit == 0 && $internalTransferAmount == 0 && $journal->notes != 'Opening Balance') {
                continue;
            }

            if ($internalTransferAmount > 0) {
                $journal = clone $journal;
                $journal->notes .= " (Wallet Used: ৳" . number_format($internalTransferAmount, 0) . ")";
            }

            $runningBalance += $debit;
            $runningBalance -= $credit;

            $ledgerEntries->push((object)[
                'id' => $journal->id,
                'journal' => $journal,
                'debit' => $debit,
                'credit' => $credit,
                'running_balance' => $runningBalance,
            ]);
        }

        $ledgerEntries = $ledgerEntries->sortByDesc(function($entry) {
            $parts = explode('_', (string)$entry->id);
            $journalId = str_pad($parts[0], 10, '0', STR_PAD_LEFT);
            $subSeq = isset($parts[1]) ? $parts[1] : '0';
            
            $seqMap = [
                'sale' => '1',
                'pay' => '2',
            ];
            $seq = $seqMap[$subSeq] ?? '0';

            return $entry->journal->date . '_' . $journalId . '_' . $seq;
        })->values();

        $formattedLedger = [];
        foreach ($ledgerEntries as $entry) {
            $formattedLedger[] = [
                'id' => $entry->id,
                'date' => $entry->journal->date ? \Carbon\Carbon::parse($entry->journal->date)->format('Y-m-d') : ($entry->journal->created_at ? $entry->journal->created_at->toDateString() : ''),
                'description' => $entry->journal->notes ?? 'Transaction',
                'debit' => $entry->debit,
                'credit' => $entry->credit,
                'balance' => $entry->running_balance
            ];
        }

        return response()->json([
            'ledger' => $formattedLedger,
            'total_due' => $customer->total_due,
            'wallet_balance' => $customer->wallet_balance
        ]);
    }
}
