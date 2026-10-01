<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SalePayment;
use App\Models\Customer;
use App\Models\Warehouse;
use App\Models\Product;
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

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('invoice_no', 'like', "%{$search}%")
                  ->orWhereHas('customer', function($cq) use ($search) {
                      $cq->where('name', 'like', "%{$search}%")
                         ->orWhere('phone', 'like', "%{$search}%")
                         ->orWhere('company', 'like', "%{$search}%");
                  });
            });
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

        if ($request->filled('is_promotional')) {
            $query->where('is_promotional', $request->is_promotional == '1');
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

        $sales = $query->orderBy('id', 'desc')->paginate(15)->withQueryString();
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

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('invoice_no', 'like', "%{$search}%")
                  ->orWhereHas('customer', function($cq) use ($search) {
                      $cq->where('name', 'like', "%{$search}%")
                         ->orWhere('phone', 'like', "%{$search}%")
                         ->orWhere('company', 'like', "%{$search}%");
                  });
            });
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

    public function grid()
    {
        $customers = Customer::all();
        $warehouses = Warehouse::all();
        $paymentMethods = ChartOfAccount::where('is_payment_method', true)->get();
        
        return view('pos.grid', compact('customers', 'warehouses', 'paymentMethods'));
    }

    public function ajaxGetGridData(Request $request)
    {
        $warehouse_id = $request->warehouse_id;
        if (!$warehouse_id) {
            return response()->json(['products' => []]);
        }

        // Get total stock per variant in this warehouse
        $stocks = DB::table('batches')
            ->where('warehouse_id', $warehouse_id)
            ->select('product_variant_id', DB::raw('SUM(remaining_qty) as stock'))
            ->groupBy('product_variant_id')
            ->pluck('stock', 'product_variant_id')
            ->toArray();

        // Get all active products with active variants
        $products = Product::with(['unit', 'variants' => function ($q) {
            $q->where('status', true)->with('unit');
        }])
        ->where('status', true)
        ->get();

        $data = $products->map(function ($product) use ($stocks) {
            $totalWarehouseStock = 0;
            $variantsData = $product->variants->map(function ($variant) use ($product, $stocks, &$totalWarehouseStock) {
                $whStock = isset($stocks[$variant->id]) ? (float)$stocks[$variant->id] : 0;
                $totalWarehouseStock += $whStock;
                $totalStock = (float)$variant->current_stock;
                
                $displayName = $variant->name;
                if ($variant->name === 'Default' || $variant->name === $product->name) {
                    $displayName = 'Default';
                }

                return [
                    'id' => $variant->id,
                    'name' => $displayName,
                    'sku' => $variant->sku,
                    'unit_qty' => (float)$variant->unit_qty,
                    'unit_name' => $variant->unit->name ?? ($product->unit->name ?? ''),
                    'price' => (float)$variant->price,
                    'dealer_price' => (float)$variant->dealer_price,
                    'special_dealer_price' => (float)$variant->special_dealer_price,
                    'stock' => $whStock,
                    'total_stock' => $totalStock,
                ];
            })->values();

            return [
                'id' => $product->id,
                'name' => $product->name,
                'sku' => $product->sku,
                'image_url' => $product->image_url,
                'unit_name' => $product->unit->name ?? '',
                'total_stock' => $totalWarehouseStock,
                'variants' => $variantsData,
            ];
        });

        return response()->json(['products' => $data]);
    }

    public function ajaxGetVariants(Request $request)
    {
        $warehouse_id = $request->warehouse_id;
        $sale_id = $request->sale_id;
        
        if (!$warehouse_id) {
            return response()->json([]);
        }

        $stocksArray = \App\Models\Batch::where('warehouse_id', $warehouse_id)
            ->select('product_variant_id', DB::raw('SUM(remaining_qty) as stock'))
            ->groupBy('product_variant_id')
            ->pluck('stock', 'product_variant_id')
            ->toArray();

        // If editing a sale, add back the quantities already held by this sale
        // so those variants still show up in the dropdown with their total available + held stock.
        if ($sale_id) {
            $saleItems = \App\Models\SaleItem::where('sale_id', $sale_id)->get();
            foreach ($saleItems as $item) {
                if (isset($stocksArray[$item->product_variant_id])) {
                    $stocksArray[$item->product_variant_id] += $item->qty;
                } else {
                    $stocksArray[$item->product_variant_id] = $item->qty;
                }
            }
        }

        $variants = ProductVariant::with('product')->get();

        $options = [];
        foreach ($variants as $variant) {
            $whStock = (float)($stocksArray[$variant->id] ?? 0);
            $totalStock = (float)$variant->current_stock;
            $displayName = $variant->product ? $variant->product->name : 'Unknown Product';
            // If variant name is different from product name, display both
            if ($variant->name !== ($variant->product ? $variant->product->name : '') && $variant->name !== 'Default') {
                $displayName .= ' - ' . $variant->name;
            }

            $stockLabel = ($whStock === $totalStock)
                ? "(Stock: {$whStock})"
                : "(WH: {$whStock} | Total: {$totalStock})";

            $options[] = [
                'id' => $variant->id,
                'text' => "{$displayName} {$stockLabel}",
                'stock' => $whStock,
                'total_stock' => $totalStock,
                'price' => $variant->price,
                'dealer_price' => $variant->dealer_price,
                'special_dealer_price' => $variant->special_dealer_price,
                'unit_qty' => $variant->unit_qty
            ];
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
            'delivery_type' => 'nullable|integer|in:0,1',
            'order_type' => 'nullable|string|in:direct,invoice',
            'delivery_status' => 'nullable|string|in:pending,delivered,processing,dispatched,cancelled',
            'delivered_now' => 'nullable|boolean',
        ]);

        $discount = $validated['discount'] ?? 0;
        $deliveryCharge = $validated['delivery_charge'] ?? 0;
        $isPromotional = !empty($validated['is_promotional']);

        try {
            DB::beginTransaction();

            $warehouseId = $validated['warehouse_id'];

            // Consolidate duplicate variant items if any
            $consolidatedItems = [];
            foreach ($validated['items'] as $item) {
                $vid = $item['product_variant_id'];
                if (!isset($consolidatedItems[$vid])) {
                    $consolidatedItems[$vid] = [
                        'product_variant_id' => $vid,
                        'qty' => 0,
                        'unit_price' => $item['unit_price'],
                    ];
                }
                $consolidatedItems[$vid]['qty'] += (float)$item['qty'];
            }

            // Calculate Totals and Weight
            $subtotal = 0;
            $grandTotalWeight = 0;
            foreach ($consolidatedItems as $item) {
                $subtotal += $item['qty'] * $item['unit_price'];
                $variant = \App\Models\ProductVariant::find($item['product_variant_id']);
                $unitQty = $variant ? $variant->getBaseQuantity() : 1;
                $grandTotalWeight += ($item['qty'] * $unitQty);
            }

            $deliveryType = $validated['delivery_type'] ?? 1; // Default to point delivery
            if (($validated['delivery_method'] ?? null) === 'steadfast') {
                if ($deliveryType == 0) {
                    $deliveryCharge = max(1, ceil($grandTotalWeight)) * 20;
                } else {
                    $deliveryCharge = 0;
                }
            } else {
                $deliveryCharge = $validated['delivery_charge'] ?? 0;
            }

            $total = max(0, $subtotal + $deliveryCharge - $discount);
            $customer = Customer::find($validated['customer_id']);

            $walletUsed = 0;
            $newAdvance = 0;
            $dueAmount = 0;

            if ($isPromotional) {
                $paidAmount = 0;
                $paymentStatus = 'paid';
            } else {
                $paidAmount = $validated['paid_amount'] ?? 0;
                $totalPaymentAvailable = $paidAmount + ($customer ? $customer->wallet_balance : 0);

                if ($totalPaymentAvailable >= $total) {
                    // Fully paid (with or without wallet)
                    $walletUsed = max(0, $total - $paidAmount);
                    $newAdvance = max(0, $paidAmount - $total);
                } else {
                    // Partially paid (even after emptying wallet)
                    $walletUsed = $customer ? $customer->wallet_balance : 0;
                    $dueAmount = $total - $totalPaymentAvailable;
                    $newAdvance = 0;
                }

                $paymentStatus = $dueAmount > 0 ? ($paidAmount > 0 || $walletUsed > 0 ? 'partial' : 'due') : 'paid';
            }

            // Check Customer Credit Limit if there is an unpaid balance
            if ($dueAmount > 0 && $customer) {
                $creditCheck = $customer->checkCreditLimit($dueAmount);
                if (!$creditCheck['allowed']) {
                    DB::rollBack();
                    if ($request->expectsJson() || $request->ajax()) {
                        return response()->json([
                            'success' => false,
                            'message' => $creditCheck['reason'],
                            'credit_check' => $creditCheck,
                        ], 422);
                    }
                    return redirect()->back()
                        ->withInput()
                        ->with('error', $creditCheck['reason']);
                }
            }

            // Create Sale
            $sale = Sale::create([
                'invoice_no' => Sale::generateInvoiceNo($validated['date'] ?? null),
                'customer_id' => $validated['customer_id'],
                'warehouse_id' => $warehouseId,
                'date' => $validated['date'],
                'subtotal' => $subtotal,
                'discount' => $discount,
                'delivery_charge' => $deliveryCharge,
                'is_promotional' => $isPromotional,
                'total' => $total,
                'paid_amount' => $paidAmount + $walletUsed,
                'due_amount' => $dueAmount,
                'payment_status' => $paymentStatus,
                'payment_method' => $isPromotional ? null : ($validated['payment_method'] ?? null),
                'delivery_method' => $validated['delivery_method'] ?? null,
                'delivery_type' => $deliveryType,
                'delivery_status' => ($validated['delivery_method'] ?? null) === 'steadfast' ? 'accepted' : 'pending',
                'shipping_address' => $request->input('shipping_address'),
                'created_by' => auth()->id() ?? 1,
            ]);

            // Update Customer Due and Wallet (only for non-promotional sales)
            if (!$isPromotional && $customer) {
                $customer->wallet_balance = $customer->wallet_balance - $walletUsed + $newAdvance;
                if ($dueAmount > 0) {
                    $customer->total_due += $dueAmount;
                }
                $customer->save();
            }

            // Record Payment (only for non-promotional sales)
            if (!$isPromotional && $paidAmount > 0) {
                SalePayment::create([
                    'sale_id' => $sale->id,
                    'amount' => $paidAmount,
                    'method' => 'cash', // simple default for now
                    'date' => $validated['date'],
                    'reference' => 'POS Payment',
                ]);
            }
            if (!$isPromotional && $walletUsed > 0) {
                SalePayment::create([
                    'sale_id' => $sale->id,
                    'amount' => $walletUsed,
                    'method' => 'wallet',
                    'date' => $validated['date'],
                    'reference' => 'Wallet Payment',
                ]);
            }

            $totalCogs = 0;
            $grandTotalWeight = 0;

            foreach ($consolidatedItems as $item) {
                $variantId = $item['product_variant_id'];
                $itemQty = $item['qty'];
                $unitPrice = $item['unit_price'];
                $lineTotal = $itemQty * $unitPrice;
                
                $variant = ProductVariant::find($variantId);
                $unitQty = $variant ? $variant->getBaseQuantity() : 1;

                // Just save the item as single unified line item without batch splitting
                SaleItem::create([
                    'sale_id' => $sale->id,
                    'product_variant_id' => $variantId,
                    'batch_id' => null,
                    'qty' => $itemQty,
                    'unit_price' => $unitPrice,
                    'total_price' => $lineTotal,
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
            $promoAcc = ChartOfAccount::firstOrCreate(['name' => 'Promotional Expense', 'type' => 'expense']);
            
            $cashAcc = isset($validated['payment_method']) ? ChartOfAccount::find($validated['payment_method']) : ChartOfAccount::firstOrCreate(['name' => 'Cash', 'type' => 'asset']);
            $arAcc = ChartOfAccount::firstOrCreate(['name' => 'Accounts Receivable', 'type' => 'asset']);

            $journal = Journal::create([
                'journal_no' => 'JNL-' . strtoupper(Str::random(6)),
                'date' => $validated['date'],
                'reference_type' => Sale::class,
                'reference_id' => $sale->id,
                'notes' => 'POS Sale ' . $sale->invoice_no . ($isPromotional ? ' (Promotional)' : ''),
                'created_by' => auth()->id() ?? 1,
            ]);

            $advAcc = ChartOfAccount::firstOrCreate(['name' => 'Customer Advance', 'type' => 'liability']);

            // 1. Revenue
            if ($isPromotional) {
                JournalEntry::create([
                    'journal_id' => $journal->id,
                    'account_id' => $promoAcc->id,
                    'type' => 'debit',
                    'amount' => $total,
                ]);
            } else {
                JournalEntry::create([
                    'journal_id' => $journal->id,
                    'account_id' => $arAcc->id,
                    'type' => 'debit',
                    'amount' => $total,
                ]);
            }

            JournalEntry::create([
                'journal_id' => $journal->id,
                'account_id' => $salesRevAcc->id,
                'type' => 'credit',
                'amount' => $total,
            ]);

            // 1.5. Payment Journal (if paid amount or wallet used)
            if (!$isPromotional && ($paidAmount > 0 || $walletUsed > 0)) {
                $paymentJournal = Journal::create([
                    'journal_no' => 'PAY-' . strtoupper(Str::random(6)),
                    'date' => $validated['date'],
                    'reference_type' => Customer::class,
                    'reference_id' => $customer->id ?? null,
                    'notes' => 'Payment for POS Sale ' . $sale->invoice_no,
                    'created_by' => auth()->id() ?? 1,
                ]);

                if ($paidAmount > 0) {
                    JournalEntry::create([
                        'journal_id' => $paymentJournal->id,
                        'account_id' => $cashAcc->id,
                        'type' => 'debit',
                        'amount' => $paidAmount,
                    ]);
                }
                if ($walletUsed > 0) {
                    JournalEntry::create([
                        'journal_id' => $paymentJournal->id,
                        'account_id' => $advAcc->id,
                        'type' => 'debit',
                        'amount' => $walletUsed,
                    ]);
                }
                
                // Credit AR for the total payment made
                JournalEntry::create([
                    'journal_id' => $paymentJournal->id,
                    'account_id' => $arAcc->id,
                    'type' => 'credit',
                    'amount' => $paidAmount + $walletUsed,
                ]);

                SalePayment::where('sale_id', $sale->id)->update(['journal_id' => $paymentJournal->id]);
            }

            if (!$isPromotional && $newAdvance > 0) {
                JournalEntry::create([
                    'journal_id' => $journal->id,
                    'account_id' => $advAcc->id,
                    'type' => 'credit',
                    'amount' => $newAdvance,
                ]);
            }

            // COGS & Inventory Reduction: Check if marked as "Delivered Now"
            $isDeliveredNow = $request->boolean('delivered_now', false);

            if ($sale->delivery_method === 'steadfast') {
                \App\Services\SteadfastService::dispatchSale($sale);
            } elseif ($isDeliveredNow) {
                // Immediate counter sale: Delivered Now is checked -> Deduct stock immediately
                $sale->delivery_status = 'delivered';
                $sale->dispatched_at = now();
                $sale->delivered_at = now();
                $sale->dispatched_by = auth()->id() ?? 1;
                $sale->delivered_by = auth()->id() ?? 1;
                $sale->save();

                $this->consumeStockForSale($sale);
            } else {
                // Advance Invoice / Pending Order: Delivered Now is UNCHECKED -> Keep as Pending, DO NOT deduct stock
                $sale->delivery_status = 'pending';
                $sale->dispatched_at = null;
                $sale->delivered_at = null;
                $sale->save();
            }

            DB::commit();

            $msg = ($sale->delivery_status === 'pending')
                ? 'Advance Invoice created successfully (Status: Pending, stock pending fulfillment). Invoice: ' . $sale->invoice_no
                : 'Sale completed successfully. Invoice: ' . $sale->invoice_no;

            return redirect()->route('sales.index')->with('success', $msg);

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => $e->getMessage()])->withInput();
        }
    }

    public function show(Sale $sale)
    {
        $sale->load([
            'items.productVariant.product',
            'customer',
            'warehouse',
            'payments' => function ($q) {
                $q->orderBy('date', 'desc')->orderBy('id', 'desc');
            },
            'activities.user'
        ]);
        $paymentMethods = \App\Models\ChartOfAccount::where('is_payment_method', true)->get();
        return view('sales.show', compact('sale', 'paymentMethods'));
    }

    public function print(Sale $sale)
    {
        $sale->load(['items.productVariant.product', 'items.productVariant.unit', 'customer', 'warehouse', 'creator']);
        return view('sales.invoice', compact('sale'));
    }

    public function pdf(Sale $sale)
    {
        $sale->load(['items.productVariant.product', 'items.productVariant.unit', 'customer', 'warehouse', 'creator']);
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('sales.invoice', compact('sale'));
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
            'delivery_type' => 'nullable|integer|in:0,1',
        ]);

        $discount = $validated['discount'] ?? 0;
        $deliveryCharge = $validated['delivery_charge'] ?? 0;
        $isPromotional = !empty($validated['is_promotional']);
        
        $dispatchedAt = $request->input('dispatched_at', $sale->dispatched_at);
        $dispatchedBy = $request->input('dispatched_by', $sale->dispatched_by);

        try {
            DB::beginTransaction();

            $this->reverseSale($sale);

            $warehouseId = $validated['warehouse_id'];

            // Consolidate duplicate variant items if any
            $consolidatedItems = [];
            foreach ($validated['items'] as $item) {
                $vid = $item['product_variant_id'];
                if (!isset($consolidatedItems[$vid])) {
                    $consolidatedItems[$vid] = [
                        'product_variant_id' => $vid,
                        'qty' => 0,
                        'unit_price' => $item['unit_price'],
                    ];
                }
                $consolidatedItems[$vid]['qty'] += (float)$item['qty'];
            }

            // Calculate Totals and Weight
            $subtotal = 0;
            $grandTotalWeight = 0;
            foreach ($consolidatedItems as $item) {
                $subtotal += $item['qty'] * $item['unit_price'];
                $variant = \App\Models\ProductVariant::find($item['product_variant_id']);
                $unitQty = $variant ? $variant->getBaseQuantity() : 1;
                $grandTotalWeight += ($item['qty'] * $unitQty);
            }

            $deliveryType = $validated['delivery_type'] ?? 1; // Default to point delivery
            if (($validated['delivery_method'] ?? null) === 'steadfast') {
                if ($deliveryType == 0) {
                    $deliveryCharge = max(1, ceil($grandTotalWeight)) * 20;
                } else {
                    $deliveryCharge = 0;
                }
            } else {
                $deliveryCharge = $validated['delivery_charge'] ?? 0;
            }

            $total = max(0, $subtotal + $deliveryCharge - $discount);
            $customer = Customer::find($validated['customer_id']);

            $walletUsed = 0;
            $newAdvance = 0;
            $dueAmount = 0;

            if ($isPromotional) {
                $paidAmount = 0;
                $paymentStatus = 'paid';
            } else {
                $paidAmount = $validated['paid_amount'] ?? 0;
                $totalPaymentAvailable = $paidAmount + ($customer ? $customer->wallet_balance : 0);

                if ($totalPaymentAvailable >= $total) {
                    $walletUsed = max(0, $total - $paidAmount);
                    $newAdvance = max(0, $paidAmount - $total);
                } else {
                    $walletUsed = $customer ? $customer->wallet_balance : 0;
                    $dueAmount = $total - $totalPaymentAvailable;
                    $newAdvance = 0;
                }

                $paymentStatus = $dueAmount > 0 ? ($paidAmount > 0 || $walletUsed > 0 ? 'partial' : 'due') : 'paid';
            }

            // Update Sale
            $sale->update([
                'customer_id' => $validated['customer_id'],
                'warehouse_id' => $warehouseId,
                'date' => $validated['date'],
                'subtotal' => $subtotal,
                'discount' => $discount,
                'delivery_charge' => $deliveryCharge,
                'is_promotional' => $isPromotional,
                'total' => $total,
                'paid_amount' => $paidAmount + $walletUsed,
                'due_amount' => $dueAmount,
                'payment_status' => $paymentStatus,
                'payment_method' => $isPromotional ? null : ($validated['payment_method'] ?? null),
                'delivery_method' => $validated['delivery_method'] ?? null,
                'delivery_type' => $deliveryType,
                'dispatched_at' => $dispatchedAt,
                'dispatched_by' => $dispatchedBy,
                'shipping_address' => $request->input('shipping_address', $sale->shipping_address),
                // created_by is left unchanged
            ]);

            // Update Customer Due and Wallet (only for non-promotional sales)
            if (!$isPromotional && $customer) {
                $customer->wallet_balance = $customer->wallet_balance - $walletUsed + $newAdvance;
                if ($dueAmount > 0) {
                    $customer->total_due += $dueAmount;
                }
                $customer->save();
            }

            // Record Payment (only for non-promotional sales)
            if (!$isPromotional && $paidAmount > 0) {
                SalePayment::create([
                    'sale_id' => $sale->id,
                    'amount' => $paidAmount,
                    'method' => 'cash',
                    'date' => $validated['date'],
                    'reference' => 'POS Payment',
                ]);
            }
            if (!$isPromotional && $walletUsed > 0) {
                SalePayment::create([
                    'sale_id' => $sale->id,
                    'amount' => $walletUsed,
                    'method' => 'wallet',
                    'date' => $validated['date'],
                    'reference' => 'Wallet Payment',
                ]);
            }

            $totalCogs = 0;
            $grandTotalWeight = 0;

            foreach ($consolidatedItems as $item) {
                $variantId = $item['product_variant_id'];
                $itemQty = $item['qty'];
                $unitPrice = $item['unit_price'];
                $lineTotal = $itemQty * $unitPrice;
                
                $variant = ProductVariant::find($variantId);
                $unitQty = $variant ? $variant->getBaseQuantity() : 1;
                
                $grandTotalWeight += ($itemQty * $unitQty);

                // Just save the item as single unified line item without batch splitting
                SaleItem::create([
                    'sale_id' => $sale->id,
                    'product_variant_id' => $variantId,
                    'batch_id' => null,
                    'qty' => $itemQty,
                    'unit_price' => $unitPrice,
                    'total_price' => $lineTotal,
                    'total_weight' => $itemQty * $unitQty,
                ]);
            }

            $sale->total_weight = $grandTotalWeight;
            $sale->save();

            // Accounting Entries
            $inventoryFinAcc = ChartOfAccount::firstOrCreate(['name' => 'Inventory (Finished)', 'type' => 'asset']);
            $cogsAcc = ChartOfAccount::firstOrCreate(['name' => 'Cost of Goods Sold', 'type' => 'expense']);
            $salesRevAcc = ChartOfAccount::firstOrCreate(['name' => 'Sales Revenue', 'type' => 'income']);
            $promoAcc = ChartOfAccount::firstOrCreate(['name' => 'Promotional Expense', 'type' => 'expense']);
            
            $cashAcc = isset($validated['payment_method']) ? ChartOfAccount::find($validated['payment_method']) : ChartOfAccount::firstOrCreate(['name' => 'Cash', 'type' => 'asset']);
            $arAcc = ChartOfAccount::firstOrCreate(['name' => 'Accounts Receivable', 'type' => 'asset']);

            $journal = Journal::create([
                'journal_no' => 'JNL-' . strtoupper(Str::random(6)),
                'date' => $validated['date'],
                'reference_type' => Sale::class,
                'reference_id' => $sale->id,
                'notes' => 'POS Sale ' . $sale->invoice_no . ' (Updated' . ($isPromotional ? ', Promotional' : '') . ')',
                'created_by' => auth()->id() ?? 1,
            ]);

            $advAcc = ChartOfAccount::firstOrCreate(['name' => 'Customer Advance', 'type' => 'liability']);

            // 1. Revenue & Payment
            if ($isPromotional) {
                JournalEntry::create([
                    'journal_id' => $journal->id,
                    'account_id' => $promoAcc->id,
                    'type' => 'debit',
                    'amount' => $total,
                ]);
            } else {
                JournalEntry::create([
                    'journal_id' => $journal->id,
                    'account_id' => $arAcc->id,
                    'type' => 'debit',
                    'amount' => $total,
                ]);
            }
            JournalEntry::create([
                'journal_id' => $journal->id,
                'account_id' => $salesRevAcc->id,
                'type' => 'credit',
                'amount' => $total,
            ]);
            
            // Payment Journal (if paid amount or wallet used)
            if (!$isPromotional && ($paidAmount > 0 || $walletUsed > 0)) {
                $paymentJournal = Journal::create([
                    'journal_no' => 'PAY-' . strtoupper(Str::random(6)),
                    'date' => $validated['date'],
                    'reference_type' => Customer::class,
                    'reference_id' => $customer->id ?? null,
                    'notes' => 'Payment for POS Sale ' . $sale->invoice_no,
                    'created_by' => auth()->id() ?? 1,
                ]);

                if ($paidAmount > 0) {
                    JournalEntry::create([
                        'journal_id' => $paymentJournal->id,
                        'account_id' => $cashAcc->id,
                        'type' => 'debit',
                        'amount' => $paidAmount,
                    ]);
                }
                if ($walletUsed > 0) {
                    JournalEntry::create([
                        'journal_id' => $paymentJournal->id,
                        'account_id' => $advAcc->id,
                        'type' => 'debit',
                        'amount' => $walletUsed,
                    ]);
                }
                
                // Credit AR for the total payment made
                JournalEntry::create([
                    'journal_id' => $paymentJournal->id,
                    'account_id' => $arAcc->id,
                    'type' => 'credit',
                    'amount' => $paidAmount + $walletUsed,
                ]);

                SalePayment::where('sale_id', $sale->id)->update(['journal_id' => $paymentJournal->id]);
            }
            
            if (!$isPromotional && $newAdvance > 0) {
                JournalEntry::create([
                    'journal_id' => $journal->id,
                    'account_id' => $advAcc->id,
                    'type' => 'credit',
                    'amount' => $newAdvance,
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

            \App\Models\ActivityLog::create([
                'user_id' => auth()->id() ?? 1,
                'action' => 'sale_updated',
                'reference_type' => \App\Models\Sale::class,
                'reference_id' => $sale->id,
                'description' => "Order items and totals were modified by admin.",
            ]);

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
            'status' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        $newDeliveryStatus = $validated['delivery_status'] ?? $validated['status'] ?? null;

        try {
            DB::beginTransaction();

            $oldStatus = $sale->getOriginal('delivery_status');

            if (isset($validated['payment_status'])) {
                $sale->payment_status = $validated['payment_status'];
            }

            if ($newDeliveryStatus) {
                $sale->delivery_status = $newDeliveryStatus;

                if ($newDeliveryStatus === 'dispatched' && !$sale->dispatched_at) {
                    $sale->dispatched_at = now();
                    $sale->dispatched_by = auth()->id() ?? 1;
                }

                if ($newDeliveryStatus === 'delivered') {
                    if (!$sale->dispatched_at) {
                        $sale->dispatched_at = now();
                        $sale->dispatched_by = auth()->id() ?? 1;
                    }
                    if (!$sale->delivered_at) {
                        $sale->delivered_at = now();
                        $sale->delivered_by = auth()->id() ?? 1;
                    }
                }
            }

            if (isset($validated['notes'])) {
                $sale->notes = $validated['notes'];
            }

            $sale->save();

            if ($newDeliveryStatus) {
                $wasDispatched = in_array($oldStatus, ['dispatched', 'delivered']);
                $isDispatched = in_array($sale->delivery_status, ['dispatched', 'delivered']);

                if ($sale->delivery_status === 'accepted' && $oldStatus !== 'accepted' && $sale->delivery_method === 'steadfast') {
                    \App\Services\SteadfastService::dispatchSale($sale);
                }

                if ($isDispatched && !$wasDispatched) {
                    $this->consumeStockForSale($sale);
                } elseif (!$isDispatched && $wasDispatched) {
                    $this->revertStockForSale($sale);
                }

                \App\Models\ActivityLog::create([
                    'user_id' => auth()->id() ?? 1,
                    'action' => 'status_updated',
                    'reference_type' => Sale::class,
                    'reference_id' => $sale->id,
                    'description' => "Delivery status changed from " . ucfirst($oldStatus ?? 'pending') . " to " . ucfirst($newDeliveryStatus),
                ]);
            }

            DB::commit();

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Delivery status updated to ' . ucfirst($newDeliveryStatus ?? $sale->delivery_status) . ' successfully.',
                    'delivery_status' => $sale->delivery_status
                ]);
            }

            return back()->with('success', 'Sale details updated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
            }
            return back()->withErrors(['error' => 'Failed to update details: ' . $e->getMessage()]);
        }
    }

    public function destroy(Sale $sale)
    {
        try {
            DB::beginTransaction();
            $customer = Customer::find($sale->customer_id);
            $this->reverseSale($sale);
            $sale->delete();
            if ($customer) {
                $customer->recalculateBalances();
            }
            DB::commit();
            if (request()->expectsJson()) {
                return response()->json(['success' => true, 'message' => 'Sale deleted and reversed successfully.']);
            }
            return redirect()->route('sales.index')->with('success', 'Sale deleted and reversed successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            if (request()->expectsJson()) {
                return response()->json(['success' => false, 'error' => $e->getMessage()], 400);
            }
            return back()->withErrors(['error' => 'Failed to delete sale: ' . $e->getMessage()]);
        }
    }

    public function syncSteadfast(Request $request)
    {
        try {
            $result = \App\Services\SteadfastService::syncPendingSales();
            $msg = "Steadfast sync complete: {$result['updated']} order(s) updated out of {$result['total']} checked.";
            if ($result['errors'] > 0) {
                $msg .= " ({$result['errors']} errors encountered)";
            }
            return back()->with('success', $msg);
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Steadfast sync failed: ' . $e->getMessage()]);
        }
    }

    public function syncSingleSteadfast(Request $request, Sale $sale)
    {
        try {
            if (!$sale->consignment_id) {
                return back()->withErrors(['error' => 'This sale has no Steadfast Consignment ID.']);
            }
            $updated = \App\Services\SteadfastService::syncSaleStatus($sale);
            if ($updated) {
                return back()->with('success', "Steadfast status synced: Current status is " . ucfirst($sale->delivery_status));
            } else {
                return back()->with('success', "Steadfast status verified: Status remains " . ucfirst($sale->delivery_status));
            }
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Steadfast sync failed: ' . $e->getMessage()]);
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
        $txns = InventoryTransaction::where('reference_type', Sale::class)->where('reference_id', $sale->id)->get();
        foreach ($txns as $txn) {
            $txn->delete();
        }

        // 3. Find all related journals to delete
        $journalIdsToDelete = collect();

        // 3a. Sale Journal (by reference)
        $saleJournals = Journal::where('reference_type', Sale::class)->where('reference_id', $sale->id)->get();
        foreach ($saleJournals as $sj) {
            $journalIdsToDelete->push($sj->id);
        }

        // 3b. Payments for this sale
        $salePayments = SalePayment::where('sale_id', $sale->id)->get();
        $salePaymentIds = $salePayments->pluck('id')->toArray();

        // 3c. Journals referencing SalePayment
        if (!empty($salePaymentIds)) {
            $spJournals = Journal::where('reference_type', SalePayment::class)->whereIn('reference_id', $salePaymentIds)->get();
            foreach ($spJournals as $spj) {
                $journalIdsToDelete->push($spj->id);
            }
        }

        // 3d. Direct journal_id on SalePayment records
        foreach ($salePayments as $sp) {
            if (!empty($sp->journal_id)) {
                $isShared = SalePayment::where('journal_id', $sp->journal_id)->where('sale_id', '!=', $sale->id)->exists();
                if (!$isShared) {
                    $journalIdsToDelete->push($sp->journal_id);
                } else {
                    $sharedJournal = Journal::find($sp->journal_id);
                    if ($sharedJournal) {
                        foreach ($sharedJournal->entries as $entry) {
                            $entry->amount = max(0, $entry->amount - (float) $sp->amount);
                            $entry->save();
                        }
                    }
                }
            }
        }

        // 3e. Any journals whose notes mention this sale's invoice_no
        if (!empty($sale->invoice_no)) {
            $invJournals = Journal::where('notes', 'LIKE', '%' . $sale->invoice_no . '%')->get();
            foreach ($invJournals as $ij) {
                $journalIdsToDelete->push($ij->id);
            }
        }

        // Delete all collected journals and entries
        $uniqueJournalIds = $journalIdsToDelete->unique()->filter()->values();
        foreach ($uniqueJournalIds as $jId) {
            JournalEntry::where('journal_id', $jId)->delete();
            Journal::where('id', $jId)->delete();
        }

        // 4. Delete Payments
        SalePayment::where('sale_id', $sale->id)->delete();

        // 5. Delete Sale Items
        SaleItem::where('sale_id', $sale->id)->delete();

        // 6. Recalculate and sync Customer balances
        $customer = Customer::find($sale->customer_id);
        if ($customer) {
            $customer->recalculateBalances($sale->id);
        }
    }

    public static function consumeStockForSale(Sale $sale)
    {
        $hasTransactions = InventoryTransaction::where('reference_type', Sale::class)->where('reference_id', $sale->id)->exists();
        if ($hasTransactions) return;

        $totalCogs = 0;
        $items = SaleItem::where('sale_id', $sale->id)->get();
        
        $groupedItems = [];
        foreach ($items as $item) {
            $vid = $item->product_variant_id;
            if (!isset($groupedItems[$vid])) {
                $groupedItems[$vid] = [
                    'qty' => 0,
                    'unit_price' => $item->unit_price,
                    'total_weight' => 0,
                    'total_price' => 0,
                ];
            }
            $groupedItems[$vid]['qty'] += (float)$item->qty;
            $groupedItems[$vid]['total_weight'] += (float)$item->total_weight;
            $groupedItems[$vid]['total_price'] += (float)$item->total_price;
        }

        // Keep sale items unified as a single row per variant (NEVER split into batch rows on invoice)
        SaleItem::where('sale_id', $sale->id)->delete();
        foreach ($groupedItems as $variantId => $data) {
            SaleItem::create([
                'sale_id' => $sale->id,
                'product_variant_id' => $variantId,
                'batch_id' => null,
                'qty' => $data['qty'],
                'unit_price' => $data['unit_price'],
                'total_price' => $data['total_price'],
                'total_weight' => $data['total_weight'],
            ]);
        }

        foreach ($groupedItems as $variantId => $data) {
            $itemQty = $data['qty'];
            $variant = ProductVariant::find($variantId);
            
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
                // Attempt auto-repackaging from raw product stock
                $autoBatch = \App\Services\StockReconciliationService::autoRepackageRawToVariant(
                    $sale->warehouse_id,
                    $variantId,
                    $remainingToConsume,
                    $sale->dispatched_at ?? $sale->date,
                    'Auto-repackaged for Sale #' . ($sale->invoice_no ?? $sale->id)
                );

                if ($autoBatch && $autoBatch->remaining_qty > 0) {
                    $takeQty = min((float)$autoBatch->remaining_qty, $remainingToConsume);
                    $cogsForThisTake = $takeQty * $autoBatch->cost_per_unit;

                    $autoBatch->qty_out += $takeQty;
                    $autoBatch->remaining_qty -= $takeQty;
                    $autoBatch->save();

                    $totalCogs += $cogsForThisTake;
                    $remainingToConsume -= $takeQty;

                    InventoryTransaction::create([
                        'warehouse_id' => $sale->warehouse_id,
                        'product_id' => $autoBatch->product_id,
                        'product_variant_id' => $variantId,
                        'batch_id' => $autoBatch->id,
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
            }

            if (round($remainingToConsume, 4) > 0) {
                $batch = Batch::where('product_variant_id', $variantId)
                    ->where('warehouse_id', $sale->warehouse_id)
                    ->latest()
                    ->first();

                if (!$batch) {
                    $productId = $variant ? $variant->product_id : 1;
                    $batch = Batch::create([
                        'batch_no' => 'B-POS-' . $sale->id . '-' . $variantId,
                        'product_id' => $productId,
                        'product_variant_id' => $variantId,
                        'warehouse_id' => $sale->warehouse_id,
                        'qty_in' => 0,
                        'qty_out' => 0,
                        'remaining_qty' => 0,
                        'cost_per_unit' => 0,
                    ]);
                }

                $takeQty = $remainingToConsume;
                $cogsForThisTake = $takeQty * $batch->cost_per_unit;

                $batch->qty_out += $takeQty;
                $batch->remaining_qty -= $takeQty;
                $batch->save();

                $totalCogs += $cogsForThisTake;

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

    public static function revertStockForSale(Sale $sale)
    {
        $txns = InventoryTransaction::where('reference_type', Sale::class)->where('reference_id', $sale->id)->get();
        foreach ($txns as $txn) {
            if ($txn->batch_id) {
                $batch = Batch::find($txn->batch_id);
                if ($batch) {
                    $batch->qty_out -= $txn->qty_out;
                    $batch->remaining_qty += $txn->qty_out;
                    $batch->save();
                }
            }
            $txn->delete();
        }

        $journal = Journal::where('reference_type', Sale::class)->where('reference_id', $sale->id)->first();
        if ($journal) {
            $inventoryFinAcc = ChartOfAccount::firstOrCreate(['name' => 'Inventory (Finished)', 'type' => 'asset']);
            $cogsAcc = ChartOfAccount::firstOrCreate(['name' => 'Cost of Goods Sold', 'type' => 'expense']);
            
            JournalEntry::where('journal_id', $journal->id)
                ->whereIn('account_id', [$inventoryFinAcc->id, $cogsAcc->id])
                ->delete();
        }

        $items = SaleItem::where('sale_id', $sale->id)->get();
        $groupedItems = [];
        foreach ($items as $item) {
            $vid = $item->product_variant_id;
            if (!isset($groupedItems[$vid])) {
                $groupedItems[$vid] = [
                    'qty' => 0,
                    'unit_price' => $item->unit_price,
                    'total_weight' => 0,
                    'total_price' => 0,
                ];
            }
            $groupedItems[$vid]['qty'] += (float)$item->qty;
            $groupedItems[$vid]['total_weight'] += (float)$item->total_weight;
            $groupedItems[$vid]['total_price'] += (float)$item->total_price;
        }

        SaleItem::where('sale_id', $sale->id)->delete();

        foreach ($groupedItems as $variantId => $data) {
            SaleItem::create([
                'sale_id' => $sale->id,
                'product_variant_id' => $variantId,
                'batch_id' => null,
                'qty' => $data['qty'],
                'unit_price' => $data['unit_price'],
                'total_price' => $data['total_price'],
                'total_weight' => $data['total_weight'],
            ]);
        }
    }
}
