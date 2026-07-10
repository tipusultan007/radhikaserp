<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SalePayment;
use App\Models\Customer;
use App\Models\Warehouse;
use App\Models\ProductVariant;
use App\Models\Batch;
use App\Models\InventoryTransaction;
use App\Models\Journal;
use App\Models\JournalEntry;
use App\Models\ChartOfAccount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SaleController extends Controller
{
    public function index(Request $request)
    {
        $query = Sale::with(['customer', 'warehouse']);

        if ($request->filled('start_date')) {
            $query->whereDate('date', '>=', $request->start_date);
        }
        
        if ($request->filled('end_date')) {
            $query->whereDate('date', '<=', $request->end_date);
        }
        
        if ($request->filled('invoice_no')) {
            $query->where('invoice_no', 'like', '%' . $request->invoice_no . '%');
        }
        
        if ($request->filled('customer_id')) {
            $query->where('customer_id', $request->customer_id);
        }
        
        if ($request->filled('status')) {
            $query->where('payment_status', $request->status);
        }

        if ($request->filled('source')) {
            if ($request->source === 'admin') {
                $query->where(function ($q) {
                    $q->where('source', 'admin')->orWhereNull('source');
                });
            } else {
                $query->where('source', $request->source);
            }
        }

        if ($request->filled('delivery_status')) {
            if ($request->delivery_status === 'pending') {
                $query->where(function($q) {
                    $q->where('delivery_status', 'pending')->orWhere(function($subQ) {
                        $subQ->whereNull('delivery_status')->whereNull('dispatched_at')->whereNull('delivered_at');
                    });
                });
            } elseif ($request->delivery_status === 'accepted') {
                $query->where('delivery_status', 'accepted');
            } elseif ($request->delivery_status === 'processing') {
                $query->where('delivery_status', 'processing');
            } elseif ($request->delivery_status === 'cancelled') {
                $query->where('delivery_status', 'cancelled');
            } elseif ($request->delivery_status === 'dispatched') {
                $query->where(function($q) {
                    $q->where('delivery_status', 'dispatched')->orWhere(function($subQ) {
                        $subQ->whereNull('delivery_status')->whereNotNull('dispatched_at')->whereNull('delivered_at');
                    });
                });
            } elseif ($request->delivery_status === 'delivered') {
                $query->where(function($q) {
                    $q->where('delivery_status', 'delivered')->orWhere(function($subQ) {
                        $subQ->whereNull('delivery_status')->whereNotNull('delivered_at');
                    });
                });
            }
        }

        $sales = $query->latest('date')->paginate(15)->withQueryString();
        $customers = Customer::orderBy('name')->get();
        
        $totalSalesCount = Sale::count();
        $pendingSalesCount = Sale::where(function($q) {
            $q->where('delivery_status', 'pending')->orWhere(function($subQ) {
                $subQ->whereNull('delivery_status')->whereNull('dispatched_at')->whereNull('delivered_at');
            });
        })->count();
        $acceptedSalesCount = Sale::where('delivery_status', 'accepted')->count();
        $processingSalesCount = Sale::where('delivery_status', 'processing')->count();
        $cancelledSalesCount = Sale::where('delivery_status', 'cancelled')->count();
        $dispatchedSalesCount = Sale::where(function($q) {
            $q->where('delivery_status', 'dispatched')->orWhere(function($subQ) {
                $subQ->whereNull('delivery_status')->whereNotNull('dispatched_at')->whereNull('delivered_at');
            });
        })->count();
        $deliveredSalesCount = Sale::where(function($q) {
            $q->where('delivery_status', 'delivered')->orWhere(function($subQ) {
                $subQ->whereNull('delivery_status')->whereNotNull('delivered_at');
            });
        })->count();

        return view('sales.index', compact('sales', 'customers', 'totalSalesCount', 'pendingSalesCount', 'acceptedSalesCount', 'processingSalesCount', 'cancelledSalesCount', 'dispatchedSalesCount', 'deliveredSalesCount'));
    }

    public function export(Request $request)
    {
        $query = Sale::with(['customer', 'warehouse']);

        if ($request->filled('start_date')) {
            $query->whereDate('date', '>=', $request->start_date);
        }
        
        if ($request->filled('end_date')) {
            $query->whereDate('date', '<=', $request->end_date);
        }
        
        if ($request->filled('invoice_no')) {
            $query->where('invoice_no', 'like', '%' . $request->invoice_no . '%');
        }
        
        if ($request->filled('customer_id')) {
            $query->where('customer_id', $request->customer_id);
        }
        
        if ($request->filled('status')) {
            $query->where('payment_status', $request->status);
        }

        if ($request->filled('source')) {
            if ($request->source === 'admin') {
                $query->where(function ($q) {
                    $q->where('source', 'admin')->orWhereNull('source');
                });
            } else {
                $query->where('source', $request->source);
            }
        }

        if ($request->filled('delivery_status')) {
            if ($request->delivery_status === 'pending') {
                $query->where(function($q) {
                    $q->where('delivery_status', 'pending')->orWhere(function($subQ) {
                        $subQ->whereNull('delivery_status')->whereNull('dispatched_at')->whereNull('delivered_at');
                    });
                });
            } elseif ($request->delivery_status === 'accepted') {
                $query->where('delivery_status', 'accepted');
            } elseif ($request->delivery_status === 'processing') {
                $query->where('delivery_status', 'processing');
            } elseif ($request->delivery_status === 'cancelled') {
                $query->where('delivery_status', 'cancelled');
            } elseif ($request->delivery_status === 'dispatched') {
                $query->where(function($q) {
                    $q->where('delivery_status', 'dispatched')->orWhere(function($subQ) {
                        $subQ->whereNull('delivery_status')->whereNotNull('dispatched_at')->whereNull('delivered_at');
                    });
                });
            } elseif ($request->delivery_status === 'delivered') {
                $query->where(function($q) {
                    $q->where('delivery_status', 'delivered')->orWhere(function($subQ) {
                        $subQ->whereNull('delivery_status')->whereNotNull('delivered_at');
                    });
                });
            }
        }

        $sales = $query->latest('date')->get();

        $headers = array(
            "Content-type"        => "text/csv",
            "Content-Disposition" => "attachment; filename=sales_export_" . date('Y-m-d_H-i-s') . ".csv",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        );

        $callback = function() use($sales) {
            $file = fopen('php://output', 'w');
            fputcsv($file, array('Date', 'Invoice No', 'Customer', 'Warehouse', 'Payment Status', 'Total Amount', 'Paid Amount', 'Due Amount', 'Dispatched At', 'Delivered At'));

            foreach ($sales as $sale) {
                fputcsv($file, array(
                    $sale->date,
                    $sale->invoice_no,
                    $sale->customer->name ?? '',
                    $sale->warehouse->name ?? '',
                    $sale->payment_status,
                    $sale->total,
                    $sale->paid_amount,
                    $sale->due_amount,
                    $sale->dispatched_at,
                    $sale->delivered_at
                ));
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function create()
    {
        $customers = Customer::all();
        $warehouses = Warehouse::all();
        $variants = ProductVariant::with('product')->get();
        $paymentMethods = ChartOfAccount::where('is_payment_method', true)->get();
        
        return view('pos.index', compact('customers', 'warehouses', 'variants', 'paymentMethods'));
    }

    public function ajaxGetVariants(Request $request)
    {
        $warehouse_id = $request->warehouse_id;
        if (!$warehouse_id) {
            return response()->json([]);
        }

        $stocks = \App\Models\Batch::where('warehouse_id', $warehouse_id)
            ->select('product_variant_id', DB::raw('SUM(remaining_qty) as stock'))
            ->groupBy('product_variant_id')
            ->get()
            ->keyBy('product_variant_id');

        $variants = ProductVariant::with('product')->get();

        $options = [];
        foreach ($variants as $variant) {
            $stock = $stocks->has($variant->id) ? $stocks->get($variant->id)->stock : 0;
            if ($stock > 0) {
                $displayName = $variant->product->name;
                // If variant name is different from product name, display both
                if ($variant->name !== $variant->product->name && $variant->name !== 'Default') {
                    $displayName .= ' - ' . $variant->name;
                }

                $options[] = [
                    'id' => $variant->id,
                    'text' => $displayName . ' (Stock: ' . (float)$stock . ')',
                    'stock' => $stock,
                    'price' => $variant->price,
                    'unit_qty' => $variant->unit_qty
                ];
            }
        }

        return response()->json($options);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'warehouse_id' => 'required|exists:warehouses,id',
            'date' => 'required|date',
            'discount' => 'nullable|numeric|min:0',
            'delivery_charge' => 'nullable|numeric|min:0',
            'is_promotional' => 'nullable|boolean',
            'paid_amount' => 'nullable|numeric|min:0',
            'payment_method' => 'nullable|exists:chart_of_accounts,id',
            'items' => 'required|array|min:1',
            'items.*.product_variant_id' => 'required|exists:product_variants,id',
            'items.*.qty' => 'required|numeric|min:0.001',
            'items.*.unit_price' => 'required|numeric|min:1',
            'delivery_method' => 'nullable|string|in:pickup,own_delivery,steadfast',
        ]);

        $discount = $validated['discount'] ?? 0;
        $deliveryCharge = $validated['delivery_charge'] ?? 0;
        $paidAmount = $validated['paid_amount'] ?? 0;

        try {
            DB::beginTransaction();

            $warehouseId = $validated['warehouse_id'];

            // Calculate Totals
            $subtotal = 0;
            foreach ($validated['items'] as $item) {
                $subtotal += $item['qty'] * $item['unit_price'];
            }

            $total = max(0, $subtotal + $deliveryCharge - $discount);
            
            $customer = Customer::find($validated['customer_id']);
            $totalPaymentAvailable = $paidAmount + $customer->wallet_balance;

            $walletUsed = 0;
            $newAdvance = 0;
            $dueAmount = 0;

            if ($totalPaymentAvailable >= $total) {
                // Fully paid (with or without wallet)
                $walletUsed = max(0, $total - $paidAmount);
                $newAdvance = max(0, $paidAmount - $total);
            } else {
                // Partially paid (even after emptying wallet)
                $walletUsed = $customer->wallet_balance;
                $dueAmount = $total - $totalPaymentAvailable;
                $newAdvance = 0;
            }

            $paymentStatus = $dueAmount > 0 ? ($paidAmount > 0 || $walletUsed > 0 ? 'partial' : 'due') : 'paid';

            // Create Sale
            $sale = Sale::create([
                'invoice_no' => 'INV-' . strtoupper(Str::random(6)),
                'customer_id' => $validated['customer_id'],
                'warehouse_id' => $warehouseId,
                'date' => $validated['date'],
                'subtotal' => $subtotal,
                'discount' => $discount,
                'delivery_charge' => $deliveryCharge,
                'is_promotional' => $validated['is_promotional'] ?? false,
                'total' => $total,
                'paid_amount' => $paidAmount,
                'due_amount' => $dueAmount,
                'payment_status' => $paymentStatus,
                'payment_method' => $validated['payment_method'] ?? null,
                'delivery_method' => $validated['delivery_method'] ?? null,
                'shipping_address' => $request->input('shipping_address'),
                'created_by' => auth()->id() ?? 1,
            ]);

            // Update Customer Due and Wallet
            $customer->wallet_balance = $customer->wallet_balance - $walletUsed + $newAdvance;
            if ($dueAmount > 0) {
                $customer->total_due += $dueAmount;
            }
            $customer->save();

            // Record Payment
            if ($paidAmount > 0) {
                SalePayment::create([
                    'sale_id' => $sale->id,
                    'amount' => $paidAmount,
                    'method' => 'cash', // simple default for now
                    'date' => $validated['date'],
                    'reference' => 'POS Payment',
                ]);
            }

            $totalCogs = 0;
            $grandTotalWeight = 0;

            foreach ($validated['items'] as $item) {
                $variantId = $item['product_variant_id'];
                $itemQty = $item['qty'];
                $unitPrice = $item['unit_price'];
                $lineTotal = $itemQty * $unitPrice;
                
                $variant = ProductVariant::find($variantId);
                $unitQty = $variant ? $variant->getBaseQuantity() : 1;
                
                $grandTotalWeight += ($itemQty * $unitQty);

                // Just save the item without inventory deduction initially
                SaleItem::create([
                    'sale_id' => $sale->id,
                    'product_variant_id' => $variantId,
                    'batch_id' => null,
                    'qty' => $itemQty,
                    'unit_price' => $unitPrice,
                    'total_price' => $itemQty * $unitPrice,
                    'total_weight' => $itemQty * $unitQty,
                ]);
            }
            
            // Save the total weight to the Sale
            $sale->total_weight = $grandTotalWeight;
            $sale->save();

            // Accounting Entries
            $inventoryFinAcc = ChartOfAccount::firstOrCreate(['name' => 'Inventory (Finished)', 'type' => 'asset']);
            $cogsAcc = ChartOfAccount::firstOrCreate(['name' => 'Cost of Goods Sold', 'type' => 'expense']);
            $salesRevAcc = ChartOfAccount::firstOrCreate(['name' => 'Sales Revenue', 'type' => 'income']);
            
            $cashAcc = isset($validated['payment_method']) ? ChartOfAccount::find($validated['payment_method']) : ChartOfAccount::firstOrCreate(['name' => 'Cash', 'type' => 'asset']);
            $arAcc = ChartOfAccount::firstOrCreate(['name' => 'Accounts Receivable', 'type' => 'asset']);

            $journal = Journal::create([
                'journal_no' => 'JNL-' . strtoupper(Str::random(6)),
                'date' => $validated['date'],
                'reference_type' => Sale::class,
                'reference_id' => $sale->id,
                'notes' => 'POS Sale ' . $sale->invoice_no,
                'created_by' => auth()->id() ?? 1,
            ]);

            $advAcc = ChartOfAccount::firstOrCreate(['name' => 'Customer Advance', 'type' => 'liability']);

            // 1. Revenue & Payment
            if ($paidAmount > 0) {
                JournalEntry::create([
                    'journal_id' => $journal->id,
                    'account_id' => $cashAcc->id,
                    'type' => 'debit',
                    'amount' => $paidAmount,
                ]);
            }
            if ($walletUsed > 0) {
                JournalEntry::create([
                    'journal_id' => $journal->id,
                    'account_id' => $advAcc->id,
                    'type' => 'debit',
                    'amount' => $walletUsed,
                ]);
            }
            if ($dueAmount > 0) {
                JournalEntry::create([
                    'journal_id' => $journal->id,
                    'account_id' => $arAcc->id,
                    'type' => 'debit',
                    'amount' => $dueAmount,
                ]);
            }
            JournalEntry::create([
                'journal_id' => $journal->id,
                'account_id' => $salesRevAcc->id,
                'type' => 'credit',
                'amount' => $total,
            ]);
            if ($newAdvance > 0) {
                JournalEntry::create([
                    'journal_id' => $journal->id,
                    'account_id' => $advAcc->id,
                    'type' => 'credit',
                    'amount' => $newAdvance,
                ]);
            }

            // COGS & Inventory Reduction entries are deferred until dispatch

            DB::commit();

            return redirect()->route('sales.index')->with('success', 'Sale completed successfully. Invoice: ' . $sale->invoice_no);

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => $e->getMessage()])->withInput();
        }
    }

    public function show(Sale $sale)
    {
        $sale->load(['items.productVariant.product', 'customer', 'warehouse']);
        $paymentMethods = \App\Models\ChartOfAccount::where('is_payment_method', true)->get();
        return view('sales.show', compact('sale', 'paymentMethods'));
    }

    public function print(Sale $sale)
    {
        $sale->load(['items.productVariant.product', 'customer', 'warehouse']);
        return view('sales.print', compact('sale'));
    }

    public function pdf(Sale $sale)
    {
        $sale->load(['items.productVariant.product', 'customer', 'warehouse']);
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('sales.print', compact('sale'));
        return $pdf->download('Invoice_' . $sale->invoice_no . '.pdf');
    }

    public function edit(Sale $sale)
    {
        $sale->load(['items.productVariant.product', 'customer', 'warehouse']);
        $customers = Customer::all();
        $warehouses = Warehouse::all();
        $variants = ProductVariant::with('product')->get();
        $paymentMethods = ChartOfAccount::where('is_payment_method', true)->get();
        
        return view('sales.edit', compact('sale', 'customers', 'warehouses', 'variants', 'paymentMethods'));
    }

    public function update(Request $request, Sale $sale)
    {
        $validated = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'warehouse_id' => 'required|exists:warehouses,id',
            'date' => 'required|date',
            'discount' => 'nullable|numeric|min:0',
            'delivery_charge' => 'nullable|numeric|min:0',
            'is_promotional' => 'nullable|boolean',
            'paid_amount' => 'nullable|numeric|min:0',
            'payment_method' => 'nullable|exists:chart_of_accounts,id',
            'items' => 'required|array|min:1',
            'items.*.product_variant_id' => 'required|exists:product_variants,id',
            'items.*.qty' => 'required|numeric|min:0.001',
            'items.*.unit_price' => 'required|numeric|min:1',
            'dispatched_at' => 'nullable|date',
            'dispatched_by' => 'nullable|exists:users,id',
            'delivery_method' => 'nullable|string|in:pickup,own_delivery,steadfast',
        ]);

        $discount = $validated['discount'] ?? 0;
        $deliveryCharge = $validated['delivery_charge'] ?? 0;
        $paidAmount = $validated['paid_amount'] ?? 0;
        
        $dispatchedAt = $request->input('dispatched_at', $sale->dispatched_at);
        $dispatchedBy = $request->input('dispatched_by', $sale->dispatched_by);

        try {
            DB::beginTransaction();

            $this->reverseSale($sale);

            $warehouseId = $validated['warehouse_id'];

            // Calculate Totals
            $subtotal = 0;
            foreach ($validated['items'] as $item) {
                $subtotal += $item['qty'] * $item['unit_price'];
            }

            $total = max(0, $subtotal + $deliveryCharge - $discount);
            
            $customer = Customer::find($validated['customer_id']);
            $totalPaymentAvailable = $paidAmount + $customer->wallet_balance;

            $walletUsed = 0;
            $newAdvance = 0;
            $dueAmount = 0;

            if ($totalPaymentAvailable >= $total) {
                $walletUsed = max(0, $total - $paidAmount);
                $newAdvance = max(0, $paidAmount - $total);
            } else {
                $walletUsed = $customer->wallet_balance;
                $dueAmount = $total - $totalPaymentAvailable;
                $newAdvance = 0;
            }

            $paymentStatus = $dueAmount > 0 ? ($paidAmount > 0 || $walletUsed > 0 ? 'partial' : 'due') : 'paid';

            // Update Sale
            $sale->update([
                'customer_id' => $validated['customer_id'],
                'warehouse_id' => $warehouseId,
                'date' => $validated['date'],
                'subtotal' => $subtotal,
                'discount' => $discount,
                'delivery_charge' => $deliveryCharge,
                'is_promotional' => $validated['is_promotional'] ?? false,
                'total' => $total,
                'paid_amount' => $paidAmount,
                'due_amount' => $dueAmount,
                'payment_status' => $paymentStatus,
                'payment_method' => $validated['payment_method'] ?? null,
                'delivery_method' => $validated['delivery_method'] ?? null,
                'dispatched_at' => $dispatchedAt,
                'dispatched_by' => $dispatchedBy,
                'shipping_address' => $request->input('shipping_address', $sale->shipping_address),
                // created_by is left unchanged
            ]);

            // Update Customer Due and Wallet
            $customer->wallet_balance = $customer->wallet_balance - $walletUsed + $newAdvance;
            if ($dueAmount > 0) {
                $customer->total_due += $dueAmount;
            }
            $customer->save();

            // Record Payment
            if ($paidAmount > 0) {
                SalePayment::create([
                    'sale_id' => $sale->id,
                    'amount' => $paidAmount,
                    'method' => 'cash',
                    'date' => $validated['date'],
                    'reference' => 'POS Payment',
                ]);
            }

            $totalCogs = 0;
            $grandTotalWeight = 0;

            foreach ($validated['items'] as $item) {
                $variantId = $item['product_variant_id'];
                $itemQty = $item['qty'];
                $unitPrice = $item['unit_price'];
                
                $variant = ProductVariant::find($variantId);
                $unitQty = $variant ? $variant->getBaseQuantity() : 1;
                
                $grandTotalWeight += ($itemQty * $unitQty);

                // Just save the item without inventory deduction initially
                SaleItem::create([
                    'sale_id' => $sale->id,
                    'product_variant_id' => $variantId,
                    'batch_id' => null,
                    'qty' => $itemQty,
                    'unit_price' => $unitPrice,
                    'total_price' => $itemQty * $unitPrice,
                    'total_weight' => $itemQty * $unitQty,
                ]);
            }

            $sale->total_weight = $grandTotalWeight;
            $sale->save();

            // Accounting Entries
            $inventoryFinAcc = ChartOfAccount::firstOrCreate(['name' => 'Inventory (Finished)', 'type' => 'asset']);
            $cogsAcc = ChartOfAccount::firstOrCreate(['name' => 'Cost of Goods Sold', 'type' => 'expense']);
            $salesRevAcc = ChartOfAccount::firstOrCreate(['name' => 'Sales Revenue', 'type' => 'income']);
            
            $cashAcc = isset($validated['payment_method']) ? ChartOfAccount::find($validated['payment_method']) : ChartOfAccount::firstOrCreate(['name' => 'Cash', 'type' => 'asset']);
            $arAcc = ChartOfAccount::firstOrCreate(['name' => 'Accounts Receivable', 'type' => 'asset']);

            $journal = Journal::create([
                'journal_no' => 'JNL-' . strtoupper(Str::random(6)),
                'date' => $validated['date'],
                'reference_type' => Sale::class,
                'reference_id' => $sale->id,
                'notes' => 'POS Sale ' . $sale->invoice_no . ' (Updated)',
                'created_by' => auth()->id() ?? 1,
            ]);

            $advAcc = ChartOfAccount::firstOrCreate(['name' => 'Customer Advance', 'type' => 'liability']);

            // 1. Revenue & Payment
            if ($paidAmount > 0) {
                JournalEntry::create([
                    'journal_id' => $journal->id,
                    'account_id' => $cashAcc->id,
                    'type' => 'debit',
                    'amount' => $paidAmount,
                ]);
            }
            if ($walletUsed > 0) {
                JournalEntry::create([
                    'journal_id' => $journal->id,
                    'account_id' => $advAcc->id,
                    'type' => 'debit',
                    'amount' => $walletUsed,
                ]);
            }
            if ($dueAmount > 0) {
                JournalEntry::create([
                    'journal_id' => $journal->id,
                    'account_id' => $arAcc->id,
                    'type' => 'debit',
                    'amount' => $dueAmount,
                ]);
            }
            JournalEntry::create([
                'journal_id' => $journal->id,
                'account_id' => $salesRevAcc->id,
                'type' => 'credit',
                'amount' => $total,
            ]);
            if ($newAdvance > 0) {
                JournalEntry::create([
                    'journal_id' => $journal->id,
                    'account_id' => $advAcc->id,
                    'type' => 'credit',
                    'amount' => $newAdvance,
                ]);
            }

            // COGS & Inventory Reduction entries are deferred until dispatch
            
            if ($dispatchedAt) {
                $sale->delivery_status = 'dispatched';
                $sale->save();
            }

            if (in_array($sale->delivery_status, ['dispatched', 'delivered'])) {
                $this->consumeStockForSale($sale);
            }

            DB::commit();

            return redirect()->route('sales.index')->with('success', 'Sale updated successfully.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => $e->getMessage()])->withInput();
        }
    }

    public function updateDetails(Request $request, Sale $sale)
    {
        $validated = $request->validate([
            'payment_status' => 'nullable|in:paid,partial,due',
            'delivery_status' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        try {
            $oldStatus = $sale->getOriginal('delivery_status');
            if (isset($validated['payment_status'])) {
                $sale->payment_status = $validated['payment_status'];
            }
            if (isset($validated['delivery_status'])) {
                $sale->delivery_status = $validated['delivery_status'];
                if ($validated['delivery_status'] === 'delivered' && !$sale->delivered_at) {
                    $sale->delivered_at = now();
                    $sale->delivered_by = auth()->id() ?? 1;
                }
            }
            if (isset($validated['notes'])) {
                $sale->notes = $validated['notes'];
            }
            $sale->save();

            if (isset($validated['delivery_status'])) {
                $wasDispatched = in_array($oldStatus, ['dispatched', 'delivered']);
                $isDispatched = in_array($sale->delivery_status, ['dispatched', 'delivered']);

                if ($isDispatched && !$wasDispatched) {
                    $this->consumeStockForSale($sale);
                } elseif (!$isDispatched && $wasDispatched) {
                    $this->revertStockForSale($sale);
                }
            }

            return back()->with('success', 'Sale details updated successfully.');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Failed to update details: ' . $e->getMessage()]);
        }
    }

    public function destroy(Sale $sale)
    {
        try {
            DB::beginTransaction();
            $this->reverseSale($sale);
            $sale->delete();
            DB::commit();
            return redirect()->route('sales.index')->with('success', 'Sale deleted and reversed successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Failed to delete sale: ' . $e->getMessage()]);
        }
    }

    private function reverseSale(Sale $sale)
    {
        $sale->load(['items.batch']);

        // 1. Restore Inventory
        foreach ($sale->items as $item) {
            if ($item->batch) {
                $item->batch->qty_out -= $item->qty;
                $item->batch->remaining_qty += $item->qty;
                $item->batch->save();
            }
        }

        // 2. Delete Inventory Transactions
        InventoryTransaction::where('reference_type', Sale::class)->where('reference_id', $sale->id)->delete();

        // 3 & 5. Revert Customer Due, Wallet Balance, and Accounting Entries
        $journal = Journal::where('reference_type', Sale::class)->where('reference_id', $sale->id)->first();
        
        $customer = Customer::find($sale->customer_id);
        if ($customer) {
            $walletUsed = 0;
            $newAdvance = 0;
            $advAcc = ChartOfAccount::where('name', 'Customer Advance')->first();
            
            if ($advAcc && $journal) {
                $walletUsed = JournalEntry::where('journal_id', $journal->id)->where('account_id', $advAcc->id)->where('type', 'debit')->sum('amount');
                $newAdvance = JournalEntry::where('journal_id', $journal->id)->where('account_id', $advAcc->id)->where('type', 'credit')->sum('amount');
            }

            $customer->wallet_balance = $customer->wallet_balance + $walletUsed - $newAdvance;
            
            if ($sale->due_amount > 0) {
                $customer->total_due = max(0, $customer->total_due - $sale->due_amount);
            }
            $customer->save();
        }

        // 4. Delete Payments
        SalePayment::where('sale_id', $sale->id)->delete();

        if ($journal) {
            JournalEntry::where('journal_id', $journal->id)->delete();
            $journal->delete();
        }

        // 6. Delete Sale Items
        SaleItem::where('sale_id', $sale->id)->delete();
    }

    private function consumeStockForSale(Sale $sale)
    {
        $hasTransactions = InventoryTransaction::where('reference_type', Sale::class)->where('reference_id', $sale->id)->exists();
        if ($hasTransactions) return;

        $totalCogs = 0;
        $items = SaleItem::where('sale_id', $sale->id)->get();
        
        $groupedItems = [];
        foreach ($items as $item) {
            if (!isset($groupedItems[$item->product_variant_id])) {
                $groupedItems[$item->product_variant_id] = [
                    'qty' => 0,
                    'unit_price' => $item->unit_price,
                    'total_weight' => 0
                ];
            }
            $groupedItems[$item->product_variant_id]['qty'] += $item->qty;
            $groupedItems[$item->product_variant_id]['total_weight'] += $item->total_weight;
        }

        SaleItem::where('sale_id', $sale->id)->delete();

        foreach ($groupedItems as $variantId => $data) {
            $itemQty = $data['qty'];
            $unitPrice = $data['unit_price'];
            $variant = ProductVariant::find($variantId);
            $unitQty = $variant ? $variant->getBaseQuantity() : 1;
            
            $batches = Batch::where('product_variant_id', $variantId)
                ->where('warehouse_id', $sale->warehouse_id)
                ->where('remaining_qty', '>', 0)
                ->orderBy('id', 'asc')
                ->lockForUpdate()
                ->get();

            $remainingToConsume = $itemQty;

            foreach ($batches as $batch) {
                if ($remainingToConsume <= 0) break;

                $takeQty = min($batch->remaining_qty, $remainingToConsume);
                $cogsForThisTake = $takeQty * $batch->cost_per_unit;

                $batch->qty_out += $takeQty;
                $batch->remaining_qty -= $takeQty;
                $batch->save();

                $totalCogs += $cogsForThisTake;
                $remainingToConsume -= $takeQty;

                SaleItem::create([
                    'sale_id' => $sale->id,
                    'product_variant_id' => $variantId,
                    'batch_id' => $batch->id,
                    'qty' => $takeQty,
                    'unit_price' => $unitPrice,
                    'total_price' => $takeQty * $unitPrice,
                    'total_weight' => $takeQty * $unitQty,
                ]);

                InventoryTransaction::create([
                    'warehouse_id' => $sale->warehouse_id,
                    'product_id' => $batch->product_id,
                    'product_variant_id' => $variantId,
                    'batch_id' => $batch->id,
                    'type' => 'sale',
                    'qty_in' => 0,
                    'qty_out' => $takeQty,
                    'cost' => $cogsForThisTake,
                    'reference_type' => Sale::class,
                    'reference_id' => $sale->id,
                    'date' => $sale->dispatched_at ?? $sale->date,
                    'created_by' => auth()->id() ?? 1,
                ]);
            }

            if (round($remainingToConsume, 4) > 0) {
                throw new \Exception("Insufficient finished stock for variant ID: {$variantId}. Shortfall: " . $remainingToConsume);
            }
        }

        if ($totalCogs > 0) {
            $journal = Journal::where('reference_type', Sale::class)->where('reference_id', $sale->id)->first();
            if ($journal) {
                $inventoryFinAcc = ChartOfAccount::firstOrCreate(['name' => 'Inventory (Finished)', 'type' => 'asset']);
                $cogsAcc = ChartOfAccount::firstOrCreate(['name' => 'Cost of Goods Sold', 'type' => 'expense']);
                
                JournalEntry::create([
                    'journal_id' => $journal->id,
                    'account_id' => $cogsAcc->id,
                    'type' => 'debit',
                    'amount' => $totalCogs,
                ]);
                JournalEntry::create([
                    'journal_id' => $journal->id,
                    'account_id' => $inventoryFinAcc->id,
                    'type' => 'credit',
                    'amount' => $totalCogs,
                ]);
            }
        }
    }

    private function revertStockForSale(Sale $sale)
    {
        $items = SaleItem::where('sale_id', $sale->id)->get();
        foreach ($items as $item) {
            if ($item->batch) {
                $item->batch->qty_out -= $item->qty;
                $item->batch->remaining_qty += $item->qty;
                $item->batch->save();
            }
        }

        InventoryTransaction::where('reference_type', Sale::class)->where('reference_id', $sale->id)->delete();

        $journal = Journal::where('reference_type', Sale::class)->where('reference_id', $sale->id)->first();
        if ($journal) {
            $inventoryFinAcc = ChartOfAccount::firstOrCreate(['name' => 'Inventory (Finished)', 'type' => 'asset']);
            $cogsAcc = ChartOfAccount::firstOrCreate(['name' => 'Cost of Goods Sold', 'type' => 'expense']);
            
            JournalEntry::where('journal_id', $journal->id)
                ->whereIn('account_id', [$inventoryFinAcc->id, $cogsAcc->id])
                ->delete();
        }

        $groupedItems = [];
        foreach ($items as $item) {
            if (!isset($groupedItems[$item->product_variant_id])) {
                $groupedItems[$item->product_variant_id] = [
                    'qty' => 0,
                    'unit_price' => $item->unit_price,
                    'total_weight' => 0
                ];
            }
            $groupedItems[$item->product_variant_id]['qty'] += $item->qty;
            $groupedItems[$item->product_variant_id]['total_weight'] += $item->total_weight;
        }

        SaleItem::where('sale_id', $sale->id)->delete();

        foreach ($groupedItems as $variantId => $data) {
            SaleItem::create([
                'sale_id' => $sale->id,
                'product_variant_id' => $variantId,
                'batch_id' => null,
                'qty' => $data['qty'],
                'unit_price' => $data['unit_price'],
                'total_price' => $data['qty'] * $data['unit_price'],
                'total_weight' => $data['total_weight'],
            ]);
        }
    }
}

