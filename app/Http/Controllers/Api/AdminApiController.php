<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\Sale;
use App\Models\Customer;
use App\Models\Supplier;
use App\Models\Import;
use App\Models\Expense;
use App\Models\Warehouse;
use App\Models\StockTransfer;
use App\Models\StockAdjustment;
use App\Models\RepackagingOrder;
use App\Models\Journal;
use App\Models\JournalEntry;
use App\Models\ActivityLog;
use App\Models\SaleItem;
use App\Models\SalePayment;
use App\Models\Batch;
use App\Models\InventoryTransaction;
use App\Models\Investment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Models\ChartOfAccount;

class AdminApiController extends Controller
{
    /**
     * Get dashboard statistics.
     */
    public function dashboard(Request $request)
    {
        $today = \Carbon\Carbon::today()->format('Y-m-d');

        $totalSales = Sale::sum('total');
        $totalCustomers = Customer::count();
        $totalProducts = Product::count();
        $totalExpenses = Expense::sum('amount');

        $todaySales = Sale::whereDate('date', $today)->sum('total');
        $todayExpenses = Expense::whereDate('date', $today)->sum('amount');
        $todayPayments = \App\Models\SalePayment::whereDate('date', $today)->sum('amount');
        $todayDues = Sale::whereDate('date', $today)->sum('due_amount');
        
        $recentSales = Sale::with('customer')->orderBy('id', 'desc')->take(5)->get();
        $recentLogs = ActivityLog::with('user')->orderBy('id', 'desc')->take(5)->get();
        
        $unreadCount = 0;
        if ($request->user()) {
            $unreadCount = $request->user()->unreadNotifications()->count();
        }

        // 7-day revenue/expense chart data
        $chartData = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = \Carbon\Carbon::now()->subDays($i)->format('Y-m-d');
            
            $revenue = Sale::whereDate('date', $date)->sum('total');
            $expense = Expense::whereDate('date', $date)->sum('amount');
            
            $chartData[] = [
                'date' => \Carbon\Carbon::now()->subDays($i)->format('M d'),
                'revenue' => $revenue,
                'expense' => $expense,
            ];
        }

        // Low stock alerts (< 10)
        $lowStock = \App\Models\WarehouseStock::with(['productVariant.product', 'warehouse'])
            ->where('stock', '<', 10)
            ->where('stock', '>', 0) // exclude completely out of stock if desired, but let's include 0 as well, wait let's just do < 10
            ->take(10)
            ->get();

        return response()->json([
            'today_sales' => $todaySales,
            'today_expenses' => $todayExpenses,
            'today_payments' => $todayPayments,
            'today_dues' => $todayDues,
            
            'total_sales' => $totalSales,
            'total_customers' => $totalCustomers,
            'total_products' => $totalProducts,
            'total_expenses' => $totalExpenses,
            'recent_sales' => $recentSales,
            'recent_logs' => $recentLogs,
            'chart_data' => $chartData,
            'low_stock' => $lowStock,
            'unread_notifications' => $unreadCount,
        ]);
    }

    /**
     * Get all products.
     */
        public function products(Request $request)
    {
        $products = Product::with(['variants' => function($q) {
            $q->with('priceHistory');
        }])->orderBy('id', 'desc')->get();
        return response()->json(['products' => $products]);
    }

    public function storeProduct(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'sku' => 'required|string|unique:products,sku|max:255',
            'type' => 'required|in:raw,finished',
            'base_unit' => 'required|string|max:50',
            'status' => 'nullable|boolean',
        ]);
        $validated['status'] = $request->has('status') && $request->status;

        $product = Product::create($validated);
        return response()->json(['message' => 'Product created', 'product' => $product], 201);
    }

    public function updateProduct(Request $request, $id)
    {
        $product = Product::findOrFail($id);
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'sku' => 'required|string|unique:products,sku,' . $product->id . '|max:255',
            'type' => 'required|in:raw,finished',
            'base_unit' => 'required|string|max:50',
            'status' => 'nullable|boolean',
        ]);
        $validated['status'] = $request->has('status') && $request->status;

        $product->update($validated);
        return response()->json(['message' => 'Product updated', 'product' => $product]);
    }

    public function destroyProduct($id)
    {
        Product::destroy($id);
        return response()->json(['message' => 'Product deleted']);
    }

    public function generateProductVariantSku(Request $request)
    {
        $prefix = 'VAR';
        if ($request->has('product_id') && $request->product_id != '') {
            $product = \App\Models\Product::find($request->product_id);
            if ($product) {
                $prefix = $product->sku;
            }
        }
        
        do {
            $sku = $prefix . '-' . strtoupper(\Illuminate\Support\Str::random(4));
        } while (\App\Models\ProductVariant::where('sku', $sku)->exists());
        
        return response()->json(['sku' => $sku]);
    }

    public function storeProductVariant(Request $request)
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'name' => 'required|string|max:255',
            'sku' => 'required|string|unique:product_variants,sku|max:255',
            'barcode' => 'nullable|string|max:255',
            'unit_qty' => 'required|numeric|min:0',
            'unit_type' => 'required|string|max:50',
            'price' => 'nullable|numeric|min:0',
            'status' => 'nullable|boolean',
        ]);

        $validated['status'] = $request->has('status') && $request->status;
        $validated['price'] = $validated['price'] ?? 0;

        $variant = \App\Models\ProductVariant::create($validated);

        if ($variant->price > 0) {
            \App\Models\PriceHistory::create([
                'product_variant_id' => $variant->id,
                'old_price' => 0,
                'new_price' => $variant->price,
                'changed_by' => $request->user()->id ?? 1,
            ]);
        }

        return response()->json(['message' => 'Variant created', 'variant' => $variant], 201);
    }

    public function updateProductVariant(Request $request, $id)
    {
        $variant = \App\Models\ProductVariant::findOrFail($id);
        
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'name' => 'required|string|max:255',
            'sku' => 'required|string|unique:product_variants,sku,' . $variant->id . '|max:255',
            'barcode' => 'nullable|string|max:255',
            'unit_qty' => 'required|numeric|min:0',
            'unit_type' => 'required|string|max:50',
            'price' => 'nullable|numeric|min:0',
            'status' => 'nullable|boolean',
        ]);

        $validated['status'] = $request->has('status') && $request->status;
        $validated['price'] = $validated['price'] ?? 0;

        $oldPrice = $variant->price;
        $variant->update($validated);

        if ($oldPrice != $variant->price) {
            \App\Models\PriceHistory::create([
                'product_variant_id' => $variant->id,
                'old_price' => $oldPrice,
                'new_price' => $variant->price,
                'changed_by' => $request->user()->id ?? 1,
            ]);
        }

        return response()->json(['message' => 'Variant updated', 'variant' => $variant]);
    }

    public function destroyProductVariant($id)
    {
        \App\Models\ProductVariant::destroy($id);
        return response()->json(['message' => 'Variant deleted']);
    }

    /**
     * Get all sales.
     */
    public function sales(Request $request)
    {
        $query = Sale::with(['customer', 'items.productVariant.product', 'warehouse', 'creator']);

        if ($request->filled('invoice_no')) {
            $query->where('invoice_no', 'LIKE', '%' . $request->invoice_no . '%');
        }
        if ($request->filled('customer_id')) {
            $query->where('customer_id', $request->customer_id);
        }
        if ($request->filled('payment_status')) {
            $query->where('payment_status', $request->payment_status);
        }
        if ($request->filled('delivery_status')) {
            $query->where('delivery_status', $request->delivery_status);
        }
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('date', [$request->start_date . ' 00:00:00', $request->end_date . ' 23:59:59']);
        } elseif ($request->filled('start_date')) {
            $query->where('date', '>=', $request->start_date . ' 00:00:00');
        } elseif ($request->filled('end_date')) {
            $query->where('date', '<=', $request->end_date . ' 23:59:59');
        }

        $sales = $query->orderBy('id', 'desc')->paginate(20);
        return response()->json(['sales' => $sales]);
    }

    public function storeSale(Request $request)
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
            'payment_details' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.product_variant_id' => 'required|exists:product_variants,id',
            'items.*.qty' => 'required|numeric|min:0.001',
            'items.*.unit_price' => 'required|numeric|min:1',
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
                $walletUsed = max(0, $total - $paidAmount);
                $newAdvance = max(0, $paidAmount - $total);
            } else {
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
                'payment_details' => $validated['payment_details'] ?? null,
                'estimate_delivery_date' => $request->input('estimate_delivery_date'),
                'delivery_status' => $request->input('delivery_status'),
                'delivery_method' => $request->input('delivery_method', 'manual'),
                'shipping_address' => $request->input('shipping_address'),
                'created_by' => $request->user()->id ?? 1,
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
                    'reference' => 'POS Payment (Mobile)',
                ]);
            }

            $totalCogs = 0;
            $grandTotalWeight = 0;

            foreach ($validated['items'] as $item) {
                $variantId = $item['product_variant_id'];
                $itemQty = $item['qty'];
                $unitPrice = $item['unit_price'];

                $variant = \App\Models\ProductVariant::find($variantId);
                $unitQty = $variant ? $variant->unit_qty : 1;
                $grandTotalWeight += ($itemQty * $unitQty);

                // FIFO Batch Consumption for this variant
                $batches = Batch::where('product_variant_id', $variantId)
                    ->where('warehouse_id', $warehouseId)
                    ->where('remaining_qty', '>', 0)
                    ->orderBy('id', 'asc')
                    ->lockForUpdate()
                    ->get();

                $remainingToConsume = $itemQty;

                foreach ($batches as $batch) {
                    if ($remainingToConsume <= 0) break;

                    $takeQty = min($batch->remaining_qty, $remainingToConsume);
                    $cogsForThisTake = $takeQty * $batch->cost_per_unit;

                    // Update batch
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
                        'warehouse_id' => $warehouseId,
                        'product_id' => $batch->product_id,
                        'product_variant_id' => $variantId,
                        'batch_id' => $batch->id,
                        'type' => 'sale',
                        'qty_in' => 0,
                        'qty_out' => $takeQty,
                        'cost' => $cogsForThisTake,
                        'reference_type' => Sale::class,
                        'reference_id' => $sale->id,
                        'date' => $validated['date'],
                        'created_by' => $request->user()->id ?? 1,
                    ]);
                }

                if (round($remainingToConsume, 4) > 0) {
                    throw new \Exception("Insufficient stock for variant ID: {$variantId}. Shortfall: " . $remainingToConsume);
                }
            }

            $sale->total_weight = $grandTotalWeight;
            $sale->save();

            // Accounting Entries
            $inventoryFinAcc = ChartOfAccount::firstOrCreate(['name' => 'Inventory (Finished)', 'type' => 'asset']);
            $cogsAcc = ChartOfAccount::firstOrCreate(['name' => 'Cost of Goods Sold', 'type' => 'expense']);
            $salesRevAcc = ChartOfAccount::firstOrCreate(['name' => 'Sales Revenue', 'type' => 'income']);
            
            $cashAcc = isset($validated['payment_method']) ? ChartOfAccount::find($validated['payment_method']) : ChartOfAccount::firstOrCreate(['name' => 'Cash', 'type' => 'asset']);
            $arAcc = ChartOfAccount::firstOrCreate(['name' => 'Accounts Receivable', 'type' => 'asset']);
            $advAcc = ChartOfAccount::firstOrCreate(['name' => 'Customer Advance', 'type' => 'liability']);

            $journal = Journal::create([
                'journal_no' => 'JNL-' . strtoupper(Str::random(6)),
                'date' => $validated['date'],
                'reference_type' => Sale::class,
                'reference_id' => $sale->id,
                'notes' => 'POS Sale ' . $sale->invoice_no . ' (Mobile)',
                'created_by' => $request->user()->id ?? 1,
            ]);

            // 1. Revenue & Payment
            if ($paidAmount > 0) {
                JournalEntry::create(['journal_id' => $journal->id, 'account_id' => $cashAcc->id, 'type' => 'debit', 'amount' => $paidAmount]);
            }
            if ($walletUsed > 0) {
                JournalEntry::create(['journal_id' => $journal->id, 'account_id' => $advAcc->id, 'type' => 'debit', 'amount' => $walletUsed]);
            }
            if ($dueAmount > 0) {
                JournalEntry::create(['journal_id' => $journal->id, 'account_id' => $arAcc->id, 'type' => 'debit', 'amount' => $dueAmount]);
            }
            JournalEntry::create(['journal_id' => $journal->id, 'account_id' => $salesRevAcc->id, 'type' => 'credit', 'amount' => $total]);
            if ($newAdvance > 0) {
                JournalEntry::create(['journal_id' => $journal->id, 'account_id' => $advAcc->id, 'type' => 'credit', 'amount' => $newAdvance]);
            }

            // 2. COGS & Inventory Reduction
            JournalEntry::create(['journal_id' => $journal->id, 'account_id' => $cogsAcc->id, 'type' => 'debit', 'amount' => $totalCogs]);
            JournalEntry::create(['journal_id' => $journal->id, 'account_id' => $inventoryFinAcc->id, 'type' => 'credit', 'amount' => $totalCogs]);

            DB::commit();

            if ($paidAmount > 0 && $customer && !empty($customer->phone)) {
                \App\Services\SmsService::sendSms($customer->phone, "Dear {$customer->name}, we have received your payment of BDT {$paidAmount} for order {$sale->invoice_no}. Thank you!");
            }

            return response()->json(['message' => 'Sale created', 'sale' => $sale->load(['items', 'customer'])], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    public function updateSale(Request $request, $id)
    {
        $sale = Sale::with(['items.batch', 'customer'])->findOrFail($id);

        $validated = $request->validate([
            'warehouse_id' => 'required|exists:warehouses,id',
            'discount' => 'nullable|numeric|min:0',
            'delivery_charge' => 'nullable|numeric|min:0',
            'items' => 'required|array|min:1',
            'items.*.product_variant_id' => 'required|exists:product_variants,id',
            'items.*.qty' => 'required|numeric|min:0.001',
            'items.*.unit_price' => 'required|numeric|min:1',
            'dispatched_at' => 'nullable|date',
            'dispatched_by' => 'nullable|exists:users,id',
        ]);

        try {
            DB::beginTransaction();

            $customer = $sale->customer;
            $oldPaid = $sale->paid_amount;
            $oldDeliveryStatus = $sale->delivery_status;

            $this->reverseSale($sale);

            $warehouseId = $validated['warehouse_id'];

            // Calculate Totals
            $subtotal = 0;
            foreach ($validated['items'] as $item) {
                $subtotal += $item['qty'] * $item['unit_price'];
            }

            $discount = $validated['discount'] ?? 0;
            $deliveryCharge = $validated['delivery_charge'] ?? 0;
            $total = max(0, $subtotal + $deliveryCharge - $discount);

            $paidAmount = $request->has('paid_amount') ? $request->input('paid_amount') : $oldPaid;
            $totalPaymentAvailable = $paidAmount + ($customer ? $customer->wallet_balance : 0);

            $walletUsed = 0;
            $newAdvance = 0;
            $dueAmount = 0;

            if ($totalPaymentAvailable >= $total) {
                $walletUsed = max(0, $total - $paidAmount);
                $newAdvance = max(0, $paidAmount - $total);
            } else {
                $walletUsed = $customer ? $customer->wallet_balance : 0;
                $dueAmount = $total - $totalPaymentAvailable;
                $newAdvance = 0;
            }

            $paymentStatus = $dueAmount > 0 ? ($paidAmount > 0 || $walletUsed > 0 ? 'partial' : 'due') : 'paid';
            $paymentMethod = $request->input('payment_method', $sale->payment_method);
            
            $dispatchedAt = $request->input('dispatched_at', $sale->dispatched_at);
            $dispatchedBy = $request->input('dispatched_by', $sale->dispatched_by);
            $newDeliveryStatus = $request->input('delivery_status', $sale->delivery_status);
            $deliveryMethod = $request->input('delivery_method', $sale->delivery_method);
            $consignmentId = $sale->consignment_id;

            // Trigger Steadfast API if method is steadfast and status becomes processing
            if ($deliveryMethod === 'steadfast' && $newDeliveryStatus === 'processing' && empty($consignmentId)) {
                if ($customer) {
                    $steadfastData = [
                        'invoice' => $sale->invoice_no,
                        'recipient_name' => $customer->name,
                        'recipient_phone' => $customer->phone,
                        'recipient_address' => $customer->address ?? 'N/A',
                        'cod_amount' => $dueAmount,
                    ];
                    $response = \App\Services\SteadfastService::createOrder($steadfastData);
                    if ($response && isset($response['consignment']['consignment_id'])) {
                        $consignmentId = $response['consignment']['consignment_id'];
                    }
                }
            }

            // Update Sale
            $sale->update([
                'warehouse_id' => $warehouseId,
                'subtotal' => $subtotal,
                'discount' => $discount,
                'delivery_charge' => $deliveryCharge,
                'total' => $total,
                'paid_amount' => $paidAmount,
                'due_amount' => $dueAmount,
                'payment_status' => $paymentStatus,
                'payment_method' => $paymentMethod,
                'payment_details' => $request->input('payment_details', $sale->payment_details),
                'dispatched_at' => $dispatchedAt,
                'dispatched_by' => $dispatchedBy,
                'estimate_delivery_date' => $request->input('estimate_delivery_date', $sale->estimate_delivery_date),
                'delivery_status' => $newDeliveryStatus,
                'delivery_method' => $deliveryMethod,
                'shipping_address' => $request->input('shipping_address', $sale->shipping_address),
                'consignment_id' => $consignmentId,
            ]);

            // Update Customer Due and Wallet
            if ($customer) {
                $customer->wallet_balance = $customer->wallet_balance - $walletUsed + $newAdvance;
                if ($dueAmount > 0) {
                    $customer->total_due += $dueAmount;
                }
                $customer->save();
            }

            // Record Payment
            if ($paidAmount > 0) {
                \App\Models\SalePayment::create([
                    'sale_id' => $sale->id,
                    'amount' => $paidAmount,
                    'method' => 'cash',
                    'date' => $sale->date,
                    'reference' => 'POS Payment (Mobile Updated)',
                ]);
                
                if ($customer) {
                    \Illuminate\Support\Facades\Notification::send($customer, new \App\Notifications\CustomerAlertNotification(
                        'Payment Received',
                        "We have received a payment of BDT " . number_format($paidAmount, 2) . " for your order #{$sale->invoice_no}.",
                        'payment',
                        ['sale_id' => $sale->id],
                        $customer->id
                    ));
                }
            }

            $totalCogs = 0;
            $grandTotalWeight = 0;
            $shouldConsumeStock = ($sale->source === 'admin') || ($dispatchedAt !== null && $dispatchedBy !== null);

            foreach ($validated['items'] as $item) {
                $variantId = $item['product_variant_id'];
                $itemQty = $item['qty'];
                $unitPrice = $item['unit_price'];

                $variant = \App\Models\ProductVariant::find($variantId);
                $unitQty = $variant ? $variant->unit_qty : 1;
                $grandTotalWeight += ($itemQty * $unitQty);

                if ($shouldConsumeStock) {
                    $batches = \App\Models\Batch::where('product_variant_id', $variantId)
                        ->where('warehouse_id', $warehouseId)
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

                        \App\Models\SaleItem::create([
                            'sale_id' => $sale->id,
                            'product_variant_id' => $variantId,
                            'batch_id' => $batch->id,
                            'qty' => $takeQty,
                            'unit_price' => $unitPrice,
                            'total_price' => $takeQty * $unitPrice,
                            'total_weight' => $takeQty * $unitQty,
                        ]);

                        \App\Models\InventoryTransaction::create([
                            'warehouse_id' => $warehouseId,
                            'product_id' => $batch->product_id,
                            'product_variant_id' => $variantId,
                            'batch_id' => $batch->id,
                            'type' => 'sale',
                            'qty_in' => 0,
                            'qty_out' => $takeQty,
                            'cost' => $cogsForThisTake,
                            'reference_type' => Sale::class,
                            'reference_id' => $sale->id,
                            'date' => $sale->date,
                            'created_by' => $request->user()->id ?? 1,
                        ]);
                    }

                    if (round($remainingToConsume, 4) > 0) {
                        throw new \Exception("Insufficient stock for variant ID: {$variantId}. Shortfall: " . $remainingToConsume);
                    }
                    
                    // Check Low Stock
                    $currentStock = \App\Models\WarehouseStock::where('product_variant_id', $variantId)
                        ->where('warehouse_id', $warehouseId)
                        ->value('stock');
                    
                    if ($currentStock !== null && $currentStock < 10) {
                        try {
                            $admins = \App\Models\User::all();
                            $varName = $variant ? $variant->name : 'Item';
                            \Illuminate\Support\Facades\Notification::send($admins, new \App\Notifications\AdminAlertNotification(
                                'Low Stock Alert',
                                "Product {$varName} is low on stock ({$currentStock} remaining).",
                                'stock',
                                ['product_variant_id' => $variantId]
                            ));
                        } catch (\Exception $e) {}
                    }
                } else {
                    \App\Models\SaleItem::create([
                        'sale_id' => $sale->id,
                        'product_variant_id' => $variantId,
                        'batch_id' => null,
                        'qty' => $itemQty,
                        'unit_price' => $unitPrice,
                        'total_price' => $itemQty * $unitPrice,
                        'total_weight' => $itemQty * $unitQty,
                    ]);
                }
            }

            $sale->total_weight = $grandTotalWeight;
            $sale->save();

            // Accounting Entries
            $inventoryFinAcc = \App\Models\ChartOfAccount::firstOrCreate(['name' => 'Inventory (Finished)', 'type' => 'asset']);
            $cogsAcc = \App\Models\ChartOfAccount::firstOrCreate(['name' => 'Cost of Goods Sold', 'type' => 'expense']);
            $salesRevAcc = \App\Models\ChartOfAccount::firstOrCreate(['name' => 'Sales Revenue', 'type' => 'income']);
            $cashAcc = $paymentMethod ? \App\Models\ChartOfAccount::find($paymentMethod) : \App\Models\ChartOfAccount::firstOrCreate(['name' => 'Cash', 'type' => 'asset']);
            $arAcc = \App\Models\ChartOfAccount::firstOrCreate(['name' => 'Accounts Receivable', 'type' => 'asset']);
            $advAcc = \App\Models\ChartOfAccount::firstOrCreate(['name' => 'Customer Advance', 'type' => 'liability']);

            $journal = \App\Models\Journal::create([
                'journal_no' => 'JNL-' . strtoupper(\Illuminate\Support\Str::random(6)),
                'date' => $sale->date,
                'reference_type' => Sale::class,
                'reference_id' => $sale->id,
                'notes' => 'POS Sale ' . $sale->invoice_no . ' (Mobile Updated)',
                'created_by' => $request->user()->id ?? 1,
            ]);

            if ($paidAmount > 0) {
                \App\Models\JournalEntry::create(['journal_id' => $journal->id, 'account_id' => $cashAcc->id, 'type' => 'debit', 'amount' => $paidAmount]);
            }
            if ($walletUsed > 0) {
                \App\Models\JournalEntry::create(['journal_id' => $journal->id, 'account_id' => $advAcc->id, 'type' => 'debit', 'amount' => $walletUsed]);
            }
            if ($dueAmount > 0) {
                \App\Models\JournalEntry::create(['journal_id' => $journal->id, 'account_id' => $arAcc->id, 'type' => 'debit', 'amount' => $dueAmount]);
            }
            \App\Models\JournalEntry::create(['journal_id' => $journal->id, 'account_id' => $salesRevAcc->id, 'type' => 'credit', 'amount' => $total]);
            if ($newAdvance > 0) {
                \App\Models\JournalEntry::create(['journal_id' => $journal->id, 'account_id' => $advAcc->id, 'type' => 'credit', 'amount' => $newAdvance]);
            }

            \App\Models\JournalEntry::create(['journal_id' => $journal->id, 'account_id' => $cogsAcc->id, 'type' => 'debit', 'amount' => $totalCogs]);
            \App\Models\JournalEntry::create(['journal_id' => $journal->id, 'account_id' => $inventoryFinAcc->id, 'type' => 'credit', 'amount' => $totalCogs]);

            if ($oldDeliveryStatus !== 'processing' && $newDeliveryStatus === 'processing') {
                if ($customer) {
                    \Illuminate\Support\Facades\Notification::send($customer, new \App\Notifications\CustomerAlertNotification(
                        'Order Accepted',
                        "Your order #{$sale->invoice_no} has been accepted and is now processing.",
                        'order_processing',
                        ['sale_id' => $sale->id],
                        $customer->id
                    ));
                }
            }

            if ($oldDeliveryStatus !== 'shipped' && $newDeliveryStatus === 'shipped') {
                try {
                    $admins = \App\Models\User::all();
                    \Illuminate\Support\Facades\Notification::send($admins, new \App\Notifications\AdminAlertNotification(
                        'Order Dispatched',
                        "Order #{$sale->invoice_no} has been dispatched.",
                        'dispatch',
                        ['sale_id' => $sale->id]
                    ));
                    
                    if ($customer) {
                        \Illuminate\Support\Facades\Notification::send($customer, new \App\Notifications\CustomerAlertNotification(
                            'Order Dispatched',
                            "Your order #{$sale->invoice_no} has been dispatched and is on its way!",
                            'order_shipped',
                            ['sale_id' => $sale->id],
                            $customer->id
                        ));
                    }
                } catch (\Exception $e) {}
            }

            if ($oldDeliveryStatus !== 'delivered' && $newDeliveryStatus === 'delivered') {
                try {
                    $admins = \App\Models\User::all();
                    \Illuminate\Support\Facades\Notification::send($admins, new \App\Notifications\AdminAlertNotification(
                        'Order Delivered',
                        "Order #{$sale->invoice_no} has been delivered.",
                        'deliver',
                        ['sale_id' => $sale->id]
                    ));
                    
                    if ($customer) {
                        \Illuminate\Support\Facades\Notification::send($customer, new \App\Notifications\CustomerAlertNotification(
                            'Order Delivered',
                            "Your order #{$sale->invoice_no} has been delivered successfully.",
                            'order_delivered',
                            ['sale_id' => $sale->id],
                            $customer->id
                        ));
                    }
                } catch (\Exception $e) {}
            }

            DB::commit();

            if ($customer && !empty($customer->phone)) {
                if ($oldDeliveryStatus !== 'processing' && $newDeliveryStatus === 'processing') {
                    \App\Services\SmsService::sendSms($customer->phone, "Dear {$customer->name}, your order #{$sale->invoice_no} has been accepted and is now processing.");
                }

                if ($paidAmount > $oldPaid) {
                    $paidDiff = $paidAmount - $oldPaid;
                    \App\Services\SmsService::sendSms($customer->phone, "Dear {$customer->name}, we have received your payment of BDT {$paidDiff} for order #{$sale->invoice_no}. Thank you!");
                }
            }

            return response()->json(['message' => 'Sale updated', 'sale' => $sale->load(['items', 'customer'])]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 400);
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
        \App\Models\InventoryTransaction::where('reference_type', Sale::class)->where('reference_id', $sale->id)->delete();

        // 3 & 5. Revert Customer Due, Wallet Balance, and Accounting Entries
        $journal = \App\Models\Journal::where('reference_type', Sale::class)->where('reference_id', $sale->id)->first();
        
        $customer = \App\Models\Customer::find($sale->customer_id);
        if ($customer) {
            $walletUsed = 0;
            $newAdvance = 0;
            $advAcc = \App\Models\ChartOfAccount::where('name', 'Customer Advance')->first();
            
            if ($advAcc && $journal) {
                $walletUsed = \App\Models\JournalEntry::where('journal_id', $journal->id)->where('account_id', $advAcc->id)->where('type', 'debit')->sum('amount');
                $newAdvance = \App\Models\JournalEntry::where('journal_id', $journal->id)->where('account_id', $advAcc->id)->where('type', 'credit')->sum('amount');
            }

            $customer->wallet_balance = $customer->wallet_balance + $walletUsed - $newAdvance;
            
            if ($sale->due_amount > 0) {
                $customer->total_due = max(0, $customer->total_due - $sale->due_amount);
            }
            $customer->save();
        }

        // 4. Delete Payments
        \App\Models\SalePayment::where('sale_id', $sale->id)->delete();

        if ($journal) {
            \App\Models\JournalEntry::where('journal_id', $journal->id)->delete();
            $journal->delete();
        }

        // 6. Delete Sale Items
        \App\Models\SaleItem::where('sale_id', $sale->id)->delete();
    }

    public function destroySale($id)
    {
        try {
            DB::beginTransaction();
            $sale = Sale::findOrFail($id);
            $this->reverseSale($sale);
            $sale->delete();
            DB::commit();
            return response()->json(['message' => 'Sale deleted and reversed successfully']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Failed to delete sale: ' . $e->getMessage()], 400);
        }
    }

    /**
     * Get all customers.
     */
    public function customers(Request $request)
    {
        $query = Customer::orderBy('id', 'desc');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                  ->orWhere('phone', 'LIKE', "%{$search}%");
            });
        }

        $customers = $query->paginate(20);
        return response()->json(['customers' => $customers]);
    }

    public function storeCustomer(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'customer_type' => 'required|in:customer,dealer,special_dealer',
            'phone' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'password' => 'nullable|string|min:6',
            'address' => 'nullable|string',
            'credit_limit' => 'nullable|numeric|min:0',
            'opening_balance' => 'nullable|numeric|min:0',
        ]);

        $validated['credit_limit'] = $validated['credit_limit'] ?? 0;
        $validated['opening_balance'] = $validated['opening_balance'] ?? 0;
        $validated['total_due'] = $validated['opening_balance'];

        if (!empty($validated['password'])) {
            $validated['password'] = \Illuminate\Support\Facades\Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }

        DB::beginTransaction();
        try {
            $customer = Customer::create($validated);

            if ($customer->opening_balance > 0) {
                $this->createOpeningBalanceJournal($customer);
            }

            DB::commit();
            return response()->json(['message' => 'Customer created', 'customer' => $customer], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    public function showCustomer($id)
    {
        $customer = Customer::findOrFail($id);

        return response()->json([
            'customer' => $customer
        ]);
    }

    public function customerSales($id)
    {
        $sales = \App\Models\Sale::with('items.productVariant.product', 'warehouse')
            ->where('customer_id', $id)
            ->orderBy('date', 'desc')
            ->paginate(20);

        return response()->json(['sales' => $sales]);
    }

    public function customerPayments($id)
    {
        $payments = \App\Models\SalePayment::whereHas('sale', function($q) use ($id) {
            $q->where('customer_id', $id);
        })->with('sale')->orderBy('date', 'desc')->paginate(20);

        return response()->json(['payments' => $payments]);
    }

    public function updateCustomer(Request $request, $id)
    {
        $customer = Customer::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'customer_type' => 'required|in:customer,dealer,special_dealer',
            'phone' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'password' => 'nullable|string|min:6',
            'address' => 'nullable|string',
            'credit_limit' => 'nullable|numeric|min:0',
            'opening_balance' => 'nullable|numeric|min:0',
        ]);

        $validated['credit_limit'] = $validated['credit_limit'] ?? $customer->credit_limit;
        $newOpeningBalance = $validated['opening_balance'] ?? 0;

        if (!empty($validated['password'])) {
            $validated['password'] = \Illuminate\Support\Facades\Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }

        DB::beginTransaction();
        try {
            $oldOpeningBalance = (float) $customer->opening_balance;
            
            $diff = $newOpeningBalance - $oldOpeningBalance;
            $validated['total_due'] = $customer->total_due + $diff;

            $customer->update($validated);

            if ($oldOpeningBalance !== (float) $newOpeningBalance) {
                $journal = Journal::where('reference_type', Customer::class)
                                  ->where('reference_id', $customer->id)
                                  ->where('notes', 'Opening Balance')
                                  ->first();

                if ($newOpeningBalance > 0) {
                    if ($journal) {
                        $this->updateOpeningBalanceJournal($journal, $customer);
                    } else {
                        $this->createOpeningBalanceJournal($customer);
                    }
                } else {
                    if ($journal) {
                        $journal->entries()->delete();
                        $journal->delete();
                    }
                }
            }

            DB::commit();
            return response()->json(['message' => 'Customer updated', 'customer' => $customer]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    public function destroyCustomer($id)
    {
        Customer::destroy($id);
        return response()->json(['message' => 'Customer deleted']);
    }

    private function createOpeningBalanceJournal($customer)
    {
        $journal = Journal::create([
            'journal_no' => 'OB-CUST-' . strtoupper(Str::random(6)),
            'date' => date('Y-m-d'),
            'reference_type' => Customer::class,
            'reference_id' => $customer->id,
            'notes' => 'Opening Balance',
            'created_by' => request()->user()->id ?? 1,
        ]);

        $this->updateOpeningBalanceJournal($journal, $customer);
    }

    private function updateOpeningBalanceJournal($journal, $customer)
    {
        $equityAcc = ChartOfAccount::firstOrCreate(['name' => 'Opening Balance Equity', 'type' => 'equity']);
        $arAcc = ChartOfAccount::firstOrCreate(['name' => 'Accounts Receivable', 'type' => 'asset']);

        JournalEntry::where('journal_id', $journal->id)->delete();

        JournalEntry::create(['journal_id' => $journal->id, 'account_id' => $arAcc->id, 'type' => 'debit', 'amount' => $customer->opening_balance]);
        JournalEntry::create(['journal_id' => $journal->id, 'account_id' => $equityAcc->id, 'type' => 'credit', 'amount' => $customer->opening_balance]);
    }

    /**
     * Get all suppliers.
     */
    public function suppliers(Request $request)
    {
        $suppliers = Supplier::orderBy('id', 'desc')->get();
        return response()->json(['suppliers' => $suppliers]);
    }

    public function storeSupplier(Request $request)
    {
        $supplier = Supplier::create($request->all());
        return response()->json(['message' => 'Supplier created', 'supplier' => $supplier], 201);
    }

    public function updateSupplier(Request $request, $id)
    {
        $supplier = Supplier::findOrFail($id);
        $supplier->update($request->all());
        return response()->json(['message' => 'Supplier updated', 'supplier' => $supplier]);
    }

    public function destroySupplier($id)
    {
        Supplier::destroy($id);
        return response()->json(['message' => 'Supplier deleted']);
    }

    public function showSupplier($id)
    {
        $supplier = Supplier::findOrFail($id);
        return response()->json(['supplier' => $supplier]);
    }

    public function supplierPurchases($id)
    {
        $purchases = \App\Models\Import::where('supplier_id', $id)
            ->with('warehouse')
            ->orderBy('date', 'desc')
            ->paginate(20);
        return response()->json(['purchases' => $purchases]);
    }

    public function supplierPayments($id)
    {
        $journals = \App\Models\Journal::with('entries.account')
            ->where('reference_type', \App\Models\Supplier::class)
            ->where('reference_id', $id)
            ->whereHas('entries', function($q) {
                $q->where('type', 'debit')->whereHas('account', function($q2) {
                    $q2->where('name', 'Accounts Payable');
                });
            })
            ->orderBy('date', 'desc')
            ->paginate(20);

        $journals->getCollection()->transform(function($journal) {
            $amount = $journal->entries->where('type', 'debit')->where('account.name', 'Accounts Payable')->sum('amount');
            return [
                'id' => $journal->id,
                'amount' => $amount,
                'date' => $journal->date,
                'reference' => $journal->notes,
                'sale' => ['invoice_no' => $journal->journal_no]
            ];
        });

        return response()->json(['payments' => $journals]);
    }

    public function supplierLedger(Request $request, $id)
    {
        $supplier = Supplier::findOrFail($id);
        $apAcc = ChartOfAccount::where('name', 'Accounts Payable')->first();
        
        if (!$apAcc) {
            return response()->json(['ledger' => ['data' => []], 'total_payable' => 0]);
        }

        $importIds = \App\Models\Import::where('supplier_id', $id)->pluck('id');

        $supplierJournalIds = \App\Models\Journal::where(function($q) use ($supplier) {
            $q->where('reference_type', \App\Models\Supplier::class)->where('reference_id', $supplier->id);
        })->orWhere(function($q) use ($importIds) {
            $q->where('reference_type', \App\Models\Import::class)->whereIn('reference_id', $importIds);
        })->pluck('id');

        $query = \App\Models\JournalEntry::with('journal')
            ->whereIn('journal_id', $supplierJournalIds)
            ->where('account_id', $apAcc->id)
            ->orderBy('id', 'asc');

        $paginatedEntries = $query->paginate(20);

        $initialBalance = 0;
        if ($paginatedEntries->currentPage() > 1 && $paginatedEntries->first()) {
            $firstEntryIdOnPage = $paginatedEntries->first()->id;
            
            $priorCredits = \App\Models\JournalEntry::whereIn('journal_id', $supplierJournalIds)
                ->where('account_id', $apAcc->id)
                ->where('id', '<', $firstEntryIdOnPage)
                ->where('type', 'credit')
                ->sum('amount');
                
            $priorDebits = \App\Models\JournalEntry::whereIn('journal_id', $supplierJournalIds)
                ->where('account_id', $apAcc->id)
                ->where('id', '<', $firstEntryIdOnPage)
                ->where('type', 'debit')
                ->sum('amount');
                
            $initialBalance = $priorCredits - $priorDebits;
        }

        $runningBalance = $initialBalance;
        $paginatedEntries->getCollection()->transform(function ($entry) use (&$runningBalance) {
            if ($entry->type === 'credit') {
                $runningBalance += $entry->amount;
            } else {
                $runningBalance -= $entry->amount;
            }
            $entry->balance = $runningBalance;
            $entry->date = $entry->journal->date;
            $entry->description = $entry->journal->notes ?? 'N/A';
            return $entry;
        });

        return response()->json([
            'ledger' => $paginatedEntries,
            'total_payable' => $supplier->total_payable
        ]);
    }

    /**
     * Get all imports (purchases).
     */
        public function importFormData()
    {
        $suppliers = \App\Models\Supplier::all();
        $warehouses = \App\Models\Warehouse::all();
        $products = \App\Models\Product::all();
        return response()->json([
            'suppliers' => $suppliers,
            'warehouses' => $warehouses,
            'products' => $products,
        ]);
    }

    public function imports(Request $request)
    {
        $imports = Import::with(['supplier', 'warehouse', 'items.product'])->orderBy('date', 'desc')->get();
        return response()->json(['imports' => $imports]);
    }

    public function showImport($id)
    {
        $import = Import::with(['supplier', 'warehouse', 'items.product'])->findOrFail($id);
        return response()->json(['import' => $import]);
    }

    public function storeImport(Request $request)
    {
        $validated = $request->validate([
            'supplier_id' => 'required|exists:suppliers,id',
            'warehouse_id' => 'required|exists:warehouses,id',
            'date' => 'required|date',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.qty' => 'required|numeric|min:0.001',
            'items.*.unit_cost' => 'required|numeric|min:1',
        ]);

        try {
            DB::beginTransaction();

            $totalCost = 0;
            foreach ($validated['items'] as $item) {
                $totalCost += $item['qty'] * $item['unit_cost'];
            }

            $import = Import::create([
                'import_no' => 'IMP-' . strtoupper(Str::random(6)),
                'supplier_id' => $validated['supplier_id'],
                'warehouse_id' => $validated['warehouse_id'],
                'date' => $validated['date'],
                'total_cost' => $totalCost,
            ]);

            $supplier = \App\Models\Supplier::find($validated['supplier_id']);
            $supplier->increment('total_payable', $totalCost);

            foreach ($validated['items'] as $item) {
                $lineTotal = $item['qty'] * $item['unit_cost'];

                \App\Models\ImportItem::create([
                    'import_id' => $import->id,
                    'product_id' => $item['product_id'],
                    'qty' => $item['qty'],
                    'unit_cost' => $item['unit_cost'],
                    'total_cost' => $lineTotal,
                ]);

                $batch = \App\Models\Batch::create([
                    'batch_no' => 'B-' . $import->id . '-' . $item['product_id'] . '-' . strtoupper(Str::random(4)),
                    'product_id' => $item['product_id'],
                    'warehouse_id' => $validated['warehouse_id'],
                    'import_id' => $import->id,
                    'qty_in' => $item['qty'],
                    'qty_out' => 0,
                    'remaining_qty' => $item['qty'],
                    'cost_per_unit' => $item['unit_cost'],
                    'expiry_date' => null,
                ]);

                \App\Models\InventoryTransaction::create([
                    'warehouse_id' => $validated['warehouse_id'],
                    'product_id' => $item['product_id'],
                    'batch_id' => $batch->id,
                    'type' => 'import',
                    'qty_in' => $item['qty'],
                    'qty_out' => 0,
                    'cost' => $lineTotal,
                    'reference_type' => Import::class,
                    'reference_id' => $import->id,
                    'date' => $validated['date'],
                    'created_by' => $request->user()->id ?? 1,
                ]);
            }

            $inventoryAcc = ChartOfAccount::firstOrCreate(['name' => 'Inventory (Raw)', 'type' => 'asset'], ['parent_id' => null]);
            $payableAcc = ChartOfAccount::firstOrCreate(['name' => 'Accounts Payable', 'type' => 'liability'], ['parent_id' => null]);

            $journal = Journal::create([
                'journal_no' => 'JNL-' . strtoupper(Str::random(6)),
                'date' => $validated['date'],
                'reference_type' => Import::class,
                'reference_id' => $import->id,
                'notes' => 'Import Shipment ' . $import->import_no,
                'created_by' => $request->user()->id ?? 1,
            ]);

            JournalEntry::create(['journal_id' => $journal->id, 'account_id' => $inventoryAcc->id, 'type' => 'debit', 'amount' => $totalCost]);
            JournalEntry::create(['journal_id' => $journal->id, 'account_id' => $payableAcc->id, 'type' => 'credit', 'amount' => $totalCost]);

            DB::commit();
            return response()->json(['message' => 'Import confirmed successfully', 'import' => $import], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    public function updateImport(Request $request, $id)
    {
        $validated = $request->validate([
            'supplier_id' => 'required|exists:suppliers,id',
            'warehouse_id' => 'required|exists:warehouses,id',
            'date' => 'required|date',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.qty' => 'required|numeric|min:0.001',
            'items.*.unit_cost' => 'required|numeric|min:1',
        ]);

        try {
            DB::beginTransaction();

            $import = Import::findOrFail($id);

            // Reverse existing import
            $batches = \App\Models\Batch::where('import_id', $import->id)->get();
            foreach ($batches as $batch) {
                if ($batch->qty_out > 0) {
                    throw new \Exception("Cannot update import. Stock from batch {$batch->batch_no} has already been consumed.");
                }
            }

            $supplier = \App\Models\Supplier::find($import->supplier_id);
            if ($supplier) {
                $supplier->decrement('total_payable', $import->total_cost);
            }

            \App\Models\Batch::where('import_id', $import->id)->delete();
            \App\Models\InventoryTransaction::where('reference_type', Import::class)->where('reference_id', $import->id)->delete();
            $import->items()->delete();

            // Apply new data
            $totalCost = 0;
            foreach ($validated['items'] as $item) {
                $totalCost += $item['qty'] * $item['unit_cost'];
            }

            $import->update([
                'supplier_id' => $validated['supplier_id'],
                'warehouse_id' => $validated['warehouse_id'],
                'date' => $validated['date'],
                'total_cost' => $totalCost,
            ]);

            $supplier = \App\Models\Supplier::find($validated['supplier_id']);
            $supplier->increment('total_payable', $totalCost);

            foreach ($validated['items'] as $item) {
                $lineTotal = $item['qty'] * $item['unit_cost'];

                \App\Models\ImportItem::create([
                    'import_id' => $import->id,
                    'product_id' => $item['product_id'],
                    'qty' => $item['qty'],
                    'unit_cost' => $item['unit_cost'],
                    'total_cost' => $lineTotal,
                ]);

                $batch = \App\Models\Batch::create([
                    'batch_no' => 'B-' . $import->id . '-' . $item['product_id'] . '-' . strtoupper(Str::random(4)),
                    'product_id' => $item['product_id'],
                    'warehouse_id' => $validated['warehouse_id'],
                    'import_id' => $import->id,
                    'qty_in' => $item['qty'],
                    'qty_out' => 0,
                    'remaining_qty' => $item['qty'],
                    'cost_per_unit' => $item['unit_cost'],
                    'expiry_date' => null,
                ]);

                \App\Models\InventoryTransaction::create([
                    'warehouse_id' => $validated['warehouse_id'],
                    'product_id' => $item['product_id'],
                    'batch_id' => $batch->id,
                    'type' => 'import',
                    'qty_in' => $item['qty'],
                    'qty_out' => 0,
                    'reference_type' => Import::class,
                    'reference_id' => $import->id,
                    'date' => $validated['date'],
                ]);
            }

            DB::commit();
            return response()->json(['message' => 'Import updated successfully', 'import' => $import], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    public function destroyImport($id)
    {
        try {
            DB::beginTransaction();
            $import = Import::findOrFail($id);

            $batches = \App\Models\Batch::where('import_id', $import->id)->get();
            foreach ($batches as $batch) {
                if ($batch->qty_out > 0) {
                    throw new \Exception("Cannot reverse import. Stock from batch {$batch->batch_no} has already been consumed.");
                }
            }

            $supplier = \App\Models\Supplier::find($import->supplier_id);
            if ($supplier) {
                $supplier->decrement('total_payable', $import->total_cost);
            }

            \App\Models\Batch::where('import_id', $import->id)->delete();
            \App\Models\InventoryTransaction::where('reference_type', Import::class)->where('reference_id', $import->id)->delete();

            $journal = Journal::where('reference_type', Import::class)->where('reference_id', $import->id)->first();
            if ($journal) {
                JournalEntry::where('journal_id', $journal->id)->delete();
                $journal->delete();
            }

            \App\Models\ImportItem::where('import_id', $import->id)->delete();
            $import->delete();

            DB::commit();
            return response()->json(['message' => 'Import deleted successfully']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    /**
     * Get all expenses.
     */
        public function expenseFormData()
    {
        $categories = \App\Models\ExpenseCategory::all();
        $paymentMethods = ChartOfAccount::where('is_payment_method', true)->get();
        return response()->json([
            'categories' => $categories,
            'paymentMethods' => $paymentMethods,
        ]);
    }

    public function expenses(Request $request)
    {
        $expenses = Expense::with(['category', 'paymentMethod'])->orderBy('date', 'desc')->paginate(20);
        return response()->json(['expenses' => $expenses]);
    }

    public function storeExpense(Request $request)
    {
        $validated = $request->validate([
            'expense_category_id' => 'required|exists:expense_categories,id',
            'amount' => 'required|numeric|min:1',
            'date' => 'required|date',
            'payment_method_id' => 'required|exists:chart_of_accounts,id',
            'reference' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        $validated['created_by'] = $request->user()->id ?? 1;

        try {
            DB::beginTransaction();

            $expense = Expense::create($validated);

            $expenseAccId = $expense->category->chart_of_account_id;
            if (!$expenseAccId) {
                $fallbackAcc = ChartOfAccount::firstOrCreate(['name' => 'Operational Expenses', 'type' => 'expense']);
                $expenseAccId = $fallbackAcc->id;
            }
            $paymentAcc = ChartOfAccount::findOrFail($validated['payment_method_id']);

            $journal = Journal::create([
                'journal_no' => 'EXP-' . strtoupper(Str::random(6)),
                'date' => $validated['date'],
                'reference_type' => Expense::class,
                'reference_id' => $expense->id,
                'notes' => 'Expense: ' . ($validated['notes'] ?? 'Operational Expense'),
                'created_by' => $request->user()->id ?? 1,
            ]);

            JournalEntry::create(['journal_id' => $journal->id, 'account_id' => $expenseAccId, 'type' => 'debit', 'amount' => $validated['amount']]);
            JournalEntry::create(['journal_id' => $journal->id, 'account_id' => $paymentAcc->id, 'type' => 'credit', 'amount' => $validated['amount']]);

            DB::commit();
            return response()->json(['message' => 'Expense recorded successfully', 'expense' => $expense], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    public function updateExpense(Request $request, $id)
    {
        $expense = Expense::findOrFail($id);

        $validated = $request->validate([
            'expense_category_id' => 'required|exists:expense_categories,id',
            'amount' => 'required|numeric|min:1',
            'date' => 'required|date',
            'payment_method_id' => 'required|exists:chart_of_accounts,id',
            'reference' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        try {
            DB::beginTransaction();

            $expense->update($validated);

            $journal = Journal::where('reference_type', Expense::class)->where('reference_id', $expense->id)->first();
            if ($journal) {
                $journal->update([
                    'date' => $validated['date'],
                    'notes' => 'Expense: ' . ($validated['notes'] ?? 'Operational Expense'),
                ]);

                $journal->entries()->delete();

                $expenseAccId = $expense->category->chart_of_account_id;
                if (!$expenseAccId) {
                    $fallbackAcc = ChartOfAccount::firstOrCreate(['name' => 'Operational Expenses', 'type' => 'expense']);
                    $expenseAccId = $fallbackAcc->id;
                }
                $paymentAcc = ChartOfAccount::findOrFail($validated['payment_method_id']);

                JournalEntry::create(['journal_id' => $journal->id, 'account_id' => $expenseAccId, 'type' => 'debit', 'amount' => $validated['amount']]);
                JournalEntry::create(['journal_id' => $journal->id, 'account_id' => $paymentAcc->id, 'type' => 'credit', 'amount' => $validated['amount']]);
            }

            DB::commit();
            return response()->json(['message' => 'Expense updated', 'expense' => $expense]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    public function destroyExpense($id)
    {
        try {
            DB::beginTransaction();
            $expense = Expense::findOrFail($id);

            $journal = Journal::where('reference_type', Expense::class)->where('reference_id', $expense->id)->first();
            if ($journal) {
                $journal->entries()->delete();
                $journal->delete();
            }

            $expense->delete();
            DB::commit();
            return response()->json(['message' => 'Expense deleted']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    /**
     * Get all warehouses.
     */
    public function warehouses(Request $request)
    {
        $warehouses = Warehouse::orderBy('id', 'desc')->get();
        return response()->json(['warehouses' => $warehouses]);
    }

    public function storeWarehouse(Request $request)
    {
        $data = $request->all();
        if (empty($data['code'])) {
            $data['code'] = 'W-' . strtoupper(\Illuminate\Support\Str::random(4));
        }
        $warehouse = Warehouse::create($data);
        return response()->json(['message' => 'Warehouse created', 'warehouse' => $warehouse], 201);
    }

    public function updateWarehouse(Request $request, $id)
    {
        $warehouse = Warehouse::findOrFail($id);
        $data = $request->all();
        $warehouse->update($data);
        return response()->json(['message' => 'Warehouse updated', 'warehouse' => $warehouse]);
    }

    public function destroyWarehouse($id)
    {
        Warehouse::destroy($id);
        return response()->json(['message' => 'Warehouse deleted']);
    }
    /**
     * Get all settlements data (Customer Dues and Supplier Payables).
     */
    public function settlements(Request $request)
    {
        $customerDues = Customer::where('total_due', '>', 0)->paginate(20, ['*'], 'customer_page');
        $supplierPayables = Supplier::where('total_payable', '>', 0)->paginate(20, ['*'], 'supplier_page');
        $paymentMethods = ChartOfAccount::where('is_payment_method', true)->get();

        return response()->json([
            'customer_dues' => $customerDues,
            'supplier_payables' => $supplierPayables,
            'payment_methods' => $paymentMethods,
        ]);
    }

    public function payCustomer(Request $request)
    {
        $validated = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'amount' => 'required|numeric|min:1',
            'date' => 'required|date',
            'reference' => 'nullable|string',
        ]);

        $customer = Customer::findOrFail($validated['customer_id']);
        $amount = (float) $validated['amount'];
        $duePayment = min($amount, $customer->total_due);
        $newAdvance = max(0, $amount - $customer->total_due);

        try {
            \Illuminate\Support\Facades\DB::beginTransaction();

            if ($duePayment > 0) {
                $customer->decrement('total_due', $duePayment);
            }
            if ($newAdvance > 0) {
                $customer->increment('wallet_balance', $newAdvance);
            }

            $cashAcc = ChartOfAccount::firstOrCreate(['name' => 'Cash', 'type' => 'asset']);
            $arAcc = ChartOfAccount::firstOrCreate(['name' => 'Accounts Receivable', 'type' => 'asset']);

            $journal = Journal::create([
                'journal_no' => 'RCV-' . strtoupper(\Illuminate\Support\Str::random(6)),
                'date' => $validated['date'],
                'reference_type' => Customer::class,
                'reference_id' => $customer->id,
                'notes' => 'API Payment received from Customer: ' . $customer->name . ($validated['reference'] ? ' (Ref: ' . $validated['reference'] . ')' : ''),
                'created_by' => $request->user()->id ?? 1,
            ]);

            JournalEntry::create([
                'journal_id' => $journal->id,
                'account_id' => $cashAcc->id,
                'type' => 'debit',
                'amount' => $amount,
            ]);

            if ($duePayment > 0) {
                JournalEntry::create([
                    'journal_id' => $journal->id,
                    'account_id' => $arAcc->id,
                    'type' => 'credit',
                    'amount' => $duePayment,
                ]);

                $unpaidSales = \App\Models\Sale::where('customer_id', $customer->id)->where('due_amount', '>', 0)->orderBy('date', 'asc')->get();
                $remainingPayment = $duePayment;
                foreach ($unpaidSales as $sale) {
                    if ($remainingPayment <= 0) break;
                    $payThisSale = min($sale->due_amount, $remainingPayment);
                    $sale->paid_amount += $payThisSale;
                    $sale->due_amount -= $payThisSale;
                    $sale->payment_status = $sale->due_amount > 0 ? 'partial' : 'paid';
                    $sale->save();
                    \App\Models\SalePayment::create([
                        'sale_id' => $sale->id,
                        'amount' => $payThisSale,
                        'method' => 'cash',
                        'date' => $validated['date'],
                        'reference' => 'API Settlement ' . ($validated['reference'] ?? ''),
                    ]);
                    $remainingPayment -= $payThisSale;
                }
            }

            if ($newAdvance > 0) {
                $advAcc = ChartOfAccount::firstOrCreate(['name' => 'Customer Advance', 'type' => 'liability']);
                JournalEntry::create([
                    'journal_id' => $journal->id,
                    'account_id' => $advAcc->id,
                    'type' => 'credit',
                    'amount' => $newAdvance,
                ]);
            }

            \Illuminate\Support\Facades\DB::commit();
            return response()->json(['message' => 'Customer payment recorded successfully']);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function paySupplier(Request $request)
    {
        $validated = $request->validate([
            'supplier_id' => 'required|exists:suppliers,id',
            'amount' => 'required|numeric|min:1',
            'date' => 'required|date',
            'reference' => 'nullable|string',
        ]);

        $supplier = Supplier::findOrFail($validated['supplier_id']);

        try {
            \Illuminate\Support\Facades\DB::beginTransaction();

            $supplier->decrement('total_payable', $validated['amount']);

            $cashAcc = ChartOfAccount::firstOrCreate(['name' => 'Cash', 'type' => 'asset']);
            $apAcc = ChartOfAccount::firstOrCreate(['name' => 'Accounts Payable', 'type' => 'liability']);

            $journal = Journal::create([
                'journal_no' => 'PAY-' . strtoupper(\Illuminate\Support\Str::random(6)),
                'date' => $validated['date'],
                'reference_type' => Supplier::class,
                'reference_id' => $supplier->id,
                'notes' => 'API Payment made to Supplier: ' . $supplier->name . ($validated['reference'] ? ' (Ref: ' . $validated['reference'] . ')' : ''),
                'created_by' => $request->user()->id ?? 1,
            ]);

            JournalEntry::create([
                'journal_id' => $journal->id,
                'account_id' => $apAcc->id,
                'type' => 'debit',
                'amount' => $validated['amount'],
            ]);

            JournalEntry::create([
                'journal_id' => $journal->id,
                'account_id' => $cashAcc->id,
                'type' => 'credit',
                'amount' => $validated['amount'],
            ]);

            \Illuminate\Support\Facades\DB::commit();
            return response()->json(['message' => 'Supplier payment recorded successfully']);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Get all stock transfers.
     */
        public function transferFormData()
    {
        $warehouses = \App\Models\Warehouse::all();
        $variants = \App\Models\ProductVariant::with('product')->get();
        return response()->json(['warehouses' => $warehouses, 'variants' => $variants]);
    }

    public function stockTransfers(Request $request)
    {
        $query = StockTransfer::with(['fromWarehouse', 'toWarehouse', 'creator'])->orderBy('id', 'desc');
        
        if ($request->filled('transfer_no')) {
            $query->where('transfer_no', 'like', '%' . $request->transfer_no . '%');
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $transfers = $query->orderBy('id', 'desc')->paginate(20);
        return response()->json(['stock_transfers' => $transfers]);
    }

    public function showStockTransfer($id)
    {
        $transfer = StockTransfer::with(['fromWarehouse', 'toWarehouse', 'creator', 'items.productVariant.product'])->findOrFail($id);
        return response()->json(['stock_transfer' => $transfer]);
    }

    public function storeStockTransfer(Request $request)
    {
        $validated = $request->validate([
            'from_warehouse_id' => 'required|exists:warehouses,id',
            'to_warehouse_id' => 'required|exists:warehouses,id|different:from_warehouse_id',
            'items' => 'required|array|min:1',
            'items.*.product_variant_id' => 'required|exists:product_variants,id',
            'items.*.qty' => 'required|numeric|min:0.001',
        ]);

        try {
            \Illuminate\Support\Facades\DB::beginTransaction();

            $transfer = StockTransfer::create([
                'transfer_no' => 'TRF-' . strtoupper(\Illuminate\Support\Str::random(6)),
                'from_warehouse_id' => $validated['from_warehouse_id'],
                'to_warehouse_id' => $validated['to_warehouse_id'],
                'status' => 'draft',
                'created_by' => $request->user()->id ?? 1,
            ]);

            foreach ($validated['items'] as $item) {
                \App\Models\StockTransferItem::create([
                    'stock_transfer_id' => $transfer->id,
                    'product_variant_id' => $item['product_variant_id'],
                    'batch_id' => null,
                    'qty' => $item['qty'],
                ]);
            }

            \Illuminate\Support\Facades\DB::commit();
            return response()->json(['message' => 'Transfer Draft created successfully', 'stock_transfer' => $transfer], 201);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    public function updateTransferStatus(Request $request, $id)
    {
        $transfer = StockTransfer::with('items')->findOrFail($id);
        $action = $request->input('action');

        try {
            \Illuminate\Support\Facades\DB::beginTransaction();

            if ($action === 'send' && $transfer->status === 'draft') {
                foreach ($transfer->items as $item) {
                    $batches = \App\Models\Batch::where('product_variant_id', $item->product_variant_id)
                        ->where('warehouse_id', $transfer->from_warehouse_id)
                        ->where('remaining_qty', '>', 0)
                        ->orderBy('id', 'asc')
                        ->lockForUpdate()
                        ->get();

                    $remainingToConsume = $item->qty;

                    foreach ($batches as $batch) {
                        if ($remainingToConsume <= 0) break;
                        $takeQty = min($batch->remaining_qty, $remainingToConsume);
                        
                        $batch->qty_out += $takeQty;
                        $batch->remaining_qty -= $takeQty;
                        $batch->save();

                        \App\Models\InventoryTransaction::create([
                            'warehouse_id' => $transfer->from_warehouse_id,
                            'product_id' => $batch->product_id,
                            'product_variant_id' => $item->product_variant_id,
                            'batch_id' => $batch->id,
                            'type' => 'transfer_out',
                            'qty_in' => 0,
                            'qty_out' => $takeQty,
                            'cost' => $batch->cost_per_unit * $takeQty,
                            'reference_type' => StockTransfer::class,
                            'reference_id' => $transfer->id,
                            'date' => now(),
                            'created_by' => $request->user()->id ?? 1,
                        ]);

                        $remainingToConsume -= $takeQty;
                    }

                    if (round($remainingToConsume, 4) > 0) {
                        throw new \Exception("Insufficient stock in source warehouse for variant ID: {$item->product_variant_id}");
                    }
                }
                $transfer->update(['status' => 'sent']);
            } 
            elseif ($action === 'receive' && $transfer->status === 'sent') {
                foreach ($transfer->items as $item) {
                    $variant = \App\Models\ProductVariant::find($item->product_variant_id);
                    $latestBatch = \App\Models\Batch::where('product_variant_id', $variant->id)->latest()->first();
                    $costPerUnit = $latestBatch ? $latestBatch->cost_per_unit : 0;

                    $newBatch = \App\Models\Batch::create([
                        'batch_no' => 'B-TRF-' . $transfer->id . '-' . $variant->id . '-' . strtoupper(\Illuminate\Support\Str::random(4)),
                        'product_id' => $variant->product_id,
                        'product_variant_id' => $variant->id,
                        'warehouse_id' => $transfer->to_warehouse_id,
                        'qty_in' => $item->qty,
                        'qty_out' => 0,
                        'remaining_qty' => $item->qty,
                        'cost_per_unit' => $costPerUnit,
                    ]);

                    \App\Models\InventoryTransaction::create([
                        'warehouse_id' => $transfer->to_warehouse_id,
                        'product_id' => $variant->product_id,
                        'product_variant_id' => $variant->id,
                        'batch_id' => $newBatch->id,
                        'type' => 'transfer_in',
                        'qty_in' => $item->qty,
                        'qty_out' => 0,
                        'cost' => $costPerUnit * $item->qty,
                        'reference_type' => StockTransfer::class,
                        'reference_id' => $transfer->id,
                        'date' => now(),
                        'created_by' => $request->user()->id ?? 1,
                    ]);
                }
                $transfer->update(['status' => 'received']);
            } else {
                 throw new \Exception("Invalid action or status mismatch.");
            }

            \Illuminate\Support\Facades\DB::commit();
            return response()->json(['message' => 'Transfer status updated to ' . ucfirst($transfer->status)]);

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    public function updateStockTransfer(Request $request, $id)
    {
        $transfer = StockTransfer::findOrFail($id);

        if ($transfer->status !== 'draft') {
            return response()->json(['error' => 'Only draft transfers can be edited.'], 400);
        }

        $validated = $request->validate([
            'from_warehouse_id' => 'required|exists:warehouses,id',
            'to_warehouse_id' => 'required|exists:warehouses,id|different:from_warehouse_id',
            'items' => 'required|array|min:1',
            'items.*.product_variant_id' => 'required|exists:product_variants,id',
            'items.*.qty' => 'required|numeric|min:0.001',
        ]);

        try {
            \Illuminate\Support\Facades\DB::beginTransaction();

            $transfer->update([
                'from_warehouse_id' => $validated['from_warehouse_id'],
                'to_warehouse_id' => $validated['to_warehouse_id'],
            ]);

            \App\Models\StockTransferItem::where('stock_transfer_id', $transfer->id)->delete();

            foreach ($validated['items'] as $item) {
                \App\Models\StockTransferItem::create([
                    'stock_transfer_id' => $transfer->id,
                    'product_variant_id' => $item['product_variant_id'],
                    'batch_id' => null,
                    'qty' => $item['qty'],
                ]);
            }

            \Illuminate\Support\Facades\DB::commit();
            return response()->json(['message' => 'Transfer updated successfully', 'stock_transfer' => $transfer], 200);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    public function destroyStockTransfer($id)
    {
        StockTransfer::destroy($id);
        return response()->json(['message' => 'Transfer deleted']);
    }

    /**
     * Get all stock adjustments.
     */
    public function adjustmentFormData()
    {
        $warehouses = \App\Models\Warehouse::all();
        $products = \App\Models\Product::all();
        $variants = \App\Models\ProductVariant::all();
        $batches = \App\Models\Batch::with(['product', 'productVariant'])->where('remaining_qty', '>', 0)->get();

        return response()->json([
            'warehouses' => $warehouses,
            'products' => $products,
            'variants' => $variants,
            'batches' => $batches,
        ]);
    }

    public function stockAdjustments(Request $request)
    {
        $query = StockAdjustment::with(['warehouse', 'product', 'productVariant', 'batch', 'creator', 'approver'])->orderBy('id', 'desc');
        
        if ($request->filled('warehouse_id')) {
            $query->where('warehouse_id', $request->warehouse_id);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $adjustments = $query->orderBy('id', 'desc')->paginate(20);
        return response()->json(['adjustments' => $adjustments]);
    }

    public function storeStockAdjustment(Request $request)
    {
        $validated = $request->validate([
            'warehouse_id' => 'required|exists:warehouses,id',
            'product_id' => 'nullable|exists:products,id',
            'product_variant_id' => 'nullable|exists:product_variants,id',
            'batch_id' => 'required|exists:batches,id',
            'type' => 'required|in:add,remove',
            'qty' => 'required|numeric|min:0.001',
            'reason' => 'required|string',
        ]);

        $batch = \App\Models\Batch::findOrFail($validated['batch_id']);
        
        if ($validated['type'] === 'remove' && $batch->remaining_qty < $validated['qty']) {
            return response()->json(['error' => 'Cannot remove more than the batch remaining quantity.'], 400);
        }

        $validated['status'] = 'pending';
        $validated['created_by'] = $request->user()->id ?? 1;
        $validated['product_id'] = $batch->product_id;
        $validated['product_variant_id'] = $batch->product_variant_id;

        $adjustment = StockAdjustment::create($validated);
        return response()->json(['message' => 'Adjustment created', 'stock_adjustment' => $adjustment], 201);
    }

    public function updateAdjustmentStatus(Request $request, $id)
    {
        $adjustment = StockAdjustment::with('batch')->findOrFail($id);
        $action = $request->input('action');

        if ($adjustment->status !== 'pending') {
            return response()->json(['error' => 'Adjustment is already processed.'], 400);
        }

        try {
            \Illuminate\Support\Facades\DB::beginTransaction();

            if ($action === 'approve') {
                $batch = $adjustment->batch;

                if ($adjustment->type === 'remove' && $batch->remaining_qty < $adjustment->qty) {
                    throw new \Exception("Batch remaining quantity is less than requested removal.");
                }

                if ($adjustment->type === 'add') {
                    $batch->qty_in += $adjustment->qty;
                    $batch->remaining_qty += $adjustment->qty;
                } else {
                    $batch->qty_out += $adjustment->qty;
                    $batch->remaining_qty -= $adjustment->qty;
                }
                $batch->save();

                \App\Models\InventoryTransaction::create([
                    'warehouse_id' => $adjustment->warehouse_id,
                    'product_id' => $adjustment->product_id,
                    'product_variant_id' => $adjustment->product_variant_id,
                    'batch_id' => $adjustment->batch_id,
                    'type' => 'adjustment',
                    'qty_in' => $adjustment->type === 'add' ? $adjustment->qty : 0,
                    'qty_out' => $adjustment->type === 'remove' ? $adjustment->qty : 0,
                    'cost' => $batch->cost_per_unit * $adjustment->qty,
                    'reference_type' => StockAdjustment::class,
                    'reference_id' => $adjustment->id,
                    'date' => now(),
                    'created_by' => $request->user()->id ?? 1,
                ]);

                $adjustment->update([
                    'status' => 'approved',
                    'approved_by' => $request->user()->id ?? 1,
                ]);
            } elseif ($action === 'reject') {
                $adjustment->update([
                    'status' => 'rejected',
                    'approved_by' => $request->user()->id ?? 1,
                ]);
            } else {
                throw new \Exception("Invalid action.");
            }

            \Illuminate\Support\Facades\DB::commit();
            return response()->json(['message' => 'Adjustment ' . ucfirst($action) . 'd']);

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    public function updateStockAdjustment(Request $request, $id)
    {
        $adjustment = StockAdjustment::findOrFail($id);
        
        if ($adjustment->status !== 'pending') {
            return response()->json(['error' => 'Only pending adjustments can be edited.'], 400);
        }

        $validated = $request->validate([
            'warehouse_id' => 'required|exists:warehouses,id',
            'batch_id' => 'required|exists:batches,id',
            'type' => 'required|in:add,remove',
            'qty' => 'required|numeric|min:0.001',
            'reason' => 'required|string',
        ]);

        $batch = \App\Models\Batch::findOrFail($validated['batch_id']);
        
        if ($validated['type'] === 'remove' && $batch->remaining_qty < $validated['qty']) {
            return response()->json(['error' => 'Cannot remove more than the batch remaining quantity.'], 400);
        }

        $validated['product_id'] = $batch->product_id;
        $validated['product_variant_id'] = $batch->product_variant_id;

        $adjustment->update($validated);
        return response()->json(['message' => 'Adjustment updated', 'stock_adjustment' => $adjustment], 200);
    }

    public function destroyStockAdjustment($id)
    {
        StockAdjustment::destroy($id);
        return response()->json(['message' => 'Adjustment deleted']);
    }

    /**
     * Get all repackaging orders.
     */
        public function repackagingFormData()
    {
        $warehouses = \App\Models\Warehouse::all();
        $rawProducts = \App\Models\Product::where('type', 'raw')->get();
        $variants = \App\Models\ProductVariant::with('product')->get();

        return response()->json([
            'warehouses' => $warehouses,
            'raw_products' => $rawProducts,
            'variants' => $variants,
        ]);
    }

    public function repackaging(Request $request)
    {
        $query = RepackagingOrder::with(['warehouse', 'creator', 'inputs.product', 'outputs.productVariant'])->orderBy('id', 'desc');
        
        if ($request->filled('ref_no')) {
            $query->where('ref_no', 'like', '%' . $request->ref_no . '%');
        }

        $orders = $query->orderBy('id', 'desc')->paginate(20);
        return response()->json(['repackaging_orders' => $orders]);
    }

    public function showRepackaging($id)
    {
        $order = RepackagingOrder::with(['warehouse', 'creator', 'inputs.product', 'outputs.productVariant.product', 'adjustments'])->findOrFail($id);
        return response()->json(['repackaging_order' => $order]);
    }

    public function storeRepackaging(Request $request)
    {
        $validated = $request->validate([
            'warehouse_id' => 'required|exists:warehouses,id',
            'date' => 'required|date',
            'input_product_id' => 'required|exists:products,id',
            'input_qty' => 'required|numeric|min:0.001',
            'output_variant_id' => 'required|exists:product_variants,id',
            'output_qty' => 'required|numeric|min:0.001',
            'expenses' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        $expenses = $validated['expenses'] ?? 0;

        try {
            \Illuminate\Support\Facades\DB::beginTransaction();

            $warehouseId = $validated['warehouse_id'];
            $inputProductId = $validated['input_product_id'];
            $inputQty = $validated['input_qty'];

            $batches = \App\Models\Batch::where('product_id', $inputProductId)
                ->where('warehouse_id', $warehouseId)
                ->where('remaining_qty', '>', 0)
                ->orderBy('id', 'asc')
                ->lockForUpdate()
                ->get();

            $totalRawCost = 0;
            $remainingToConsume = $inputQty;
            $consumedBatches = [];

            foreach ($batches as $batch) {
                if ($remainingToConsume <= 0) break;

                $takeQty = min($batch->remaining_qty, $remainingToConsume);
                $costForThisTake = $takeQty * $batch->cost_per_unit;

                $batch->qty_out += $takeQty;
                $batch->remaining_qty -= $takeQty;
                $batch->save();

                $totalRawCost += $costForThisTake;
                $remainingToConsume -= $takeQty;

                $consumedBatches[] = [
                    'batch_id' => $batch->id,
                    'qty_used' => $takeQty,
                    'cost' => $costForThisTake
                ];
            }

            if (round($remainingToConsume, 4) > 0) {
                throw new \Exception("Insufficient raw stock in the selected warehouse. Shortfall: " . $remainingToConsume);
            }

            $totalCost = $totalRawCost + $expenses;
            $outputUnitCost = $totalCost / $validated['output_qty'];

            $order = RepackagingOrder::create([
                'ref_no' => 'RPK-' . strtoupper(\Illuminate\Support\Str::random(6)),
                'warehouse_id' => $warehouseId,
                'date' => $validated['date'],
                'created_by' => $request->user()->id ?? 1,
                'notes' => $validated['notes'] ?? '',
            ]);

            foreach ($consumedBatches as $consumed) {
                \App\Models\RepackagingInput::create([
                    'repackaging_order_id' => $order->id,
                    'batch_id' => $consumed['batch_id'],
                    'product_id' => $inputProductId,
                    'qty_used' => $consumed['qty_used'],
                ]);

                \App\Models\InventoryTransaction::create([
                    'warehouse_id' => $warehouseId,
                    'product_id' => $inputProductId,
                    'batch_id' => $consumed['batch_id'],
                    'type' => 'repack_input',
                    'qty_in' => 0,
                    'qty_out' => $consumed['qty_used'],
                    'cost' => $consumed['cost'],
                    'reference_type' => RepackagingOrder::class,
                    'reference_id' => $order->id,
                    'date' => $validated['date'],
                    'created_by' => $request->user()->id ?? 1,
                ]);
            }

            \App\Models\RepackagingOutput::create([
                'repackaging_order_id' => $order->id,
                'product_variant_id' => $validated['output_variant_id'],
                'warehouse_id' => $warehouseId,
                'qty_produced' => $validated['output_qty'],
                'unit_cost' => $outputUnitCost,
                'total_cost' => $totalCost,
            ]);

            $variant = \App\Models\ProductVariant::find($validated['output_variant_id']);
            $outputBatch = \App\Models\Batch::create([
                'batch_no' => 'B-' . $order->id . '-' . $variant->product_id . '-FIN-' . strtoupper(\Illuminate\Support\Str::random(4)),
                'product_id' => $variant->product_id,
                'product_variant_id' => $validated['output_variant_id'],
                'warehouse_id' => $warehouseId,
                'import_id' => null,
                'qty_in' => $validated['output_qty'],
                'qty_out' => 0,
                'remaining_qty' => $validated['output_qty'],
                'cost_per_unit' => $outputUnitCost,
                'expiry_date' => null,
            ]);

            \App\Models\InventoryTransaction::create([
                'warehouse_id' => $warehouseId,
                'product_id' => $variant->product_id,
                'product_variant_id' => $validated['output_variant_id'],
                'batch_id' => $outputBatch->id,
                'type' => 'repack_output',
                'qty_in' => $validated['output_qty'],
                'qty_out' => 0,
                'cost' => $totalCost,
                'reference_type' => RepackagingOrder::class,
                'reference_id' => $order->id,
                'date' => $validated['date'],
                'created_by' => $request->user()->id ?? 1,
            ]);

            $totalOutputKg = $validated['output_qty'] * $variant->unit_qty;
            if ($inputQty != $totalOutputKg) {
                $diff = $totalOutputKg - $inputQty;
                \App\Models\RepackagingAdjustment::create([
                    'repackaging_order_id' => $order->id,
                    'type' => $diff > 0 ? 'gain' : 'loss',
                    'qty' => abs($diff),
                    'reason' => 'Yield mismatch during repackaging',
                ]);
            }

            $inventoryRawAcc = \App\Models\ChartOfAccount::firstOrCreate(['name' => 'Inventory (Raw)', 'type' => 'asset']);
            $inventoryFinAcc = \App\Models\ChartOfAccount::firstOrCreate(['name' => 'Inventory (Finished)', 'type' => 'asset']);

            $journal = \App\Models\Journal::create([
                'journal_no' => 'JNL-' . strtoupper(\Illuminate\Support\Str::random(6)),
                'date' => $validated['date'],
                'reference_type' => RepackagingOrder::class,
                'reference_id' => $order->id,
                'notes' => 'API Repackaging ' . $order->ref_no,
                'created_by' => $request->user()->id ?? 1,
            ]);

            \App\Models\JournalEntry::create([
                'journal_id' => $journal->id,
                'account_id' => $inventoryFinAcc->id,
                'type' => 'debit',
                'amount' => $totalCost,
            ]);

            \App\Models\JournalEntry::create([
                'journal_id' => $journal->id,
                'account_id' => $inventoryRawAcc->id,
                'type' => 'credit',
                'amount' => $totalRawCost,
            ]);

            if ($expenses > 0) {
                $cashAcc = \App\Models\ChartOfAccount::firstOrCreate(['name' => 'Cash', 'type' => 'asset']);
                \App\Models\JournalEntry::create([
                    'journal_id' => $journal->id,
                    'account_id' => $cashAcc->id,
                    'type' => 'credit',
                    'amount' => $expenses,
                ]);
            }

            \Illuminate\Support\Facades\DB::commit();
            return response()->json(['message' => 'Repackaging created successfully', 'repackaging_order' => $order], 201);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    private function reverseRepackaging(RepackagingOrder $repackaging)
    {
        $repackaging->load(['inputs.batch', 'outputs']);

        $outputBatches = \App\Models\Batch::where('batch_no', 'like', 'B-' . $repackaging->id . '-%FIN-%')->get();
        foreach ($outputBatches as $batch) {
            if ($batch->qty_out > 0) {
                throw new \Exception("Cannot update repackaging because the finished stock has already been consumed.");
            }
        }

        \App\Models\InventoryTransaction::where('reference_type', RepackagingOrder::class)->where('reference_id', $repackaging->id)->delete();

        foreach ($outputBatches as $batch) {
            $batch->delete();
        }

        foreach ($repackaging->inputs as $input) {
            if ($input->batch) {
                $input->batch->qty_out -= $input->qty_used;
                $input->batch->remaining_qty += $input->qty_used;
                $input->batch->save();
            }
        }

        $journal = \App\Models\Journal::where('reference_type', RepackagingOrder::class)->where('reference_id', $repackaging->id)->first();
        if ($journal) {
            \App\Models\JournalEntry::where('journal_id', $journal->id)->delete();
            $journal->delete();
        }

        \App\Models\RepackagingInput::where('repackaging_order_id', $repackaging->id)->delete();
        \App\Models\RepackagingOutput::where('repackaging_order_id', $repackaging->id)->delete();
        \App\Models\RepackagingAdjustment::where('repackaging_order_id', $repackaging->id)->delete();
    }

    public function updateRepackaging(Request $request, $id)
    {
        $validated = $request->validate([
            'warehouse_id' => 'required|exists:warehouses,id',
            'date' => 'required|date',
            'input_product_id' => 'required|exists:products,id',
            'input_qty' => 'required|numeric|min:0.001',
            'output_variant_id' => 'required|exists:product_variants,id',
            'output_qty' => 'required|numeric|min:0.001',
            'expenses' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        $expenses = $validated['expenses'] ?? 0;

        try {
            \Illuminate\Support\Facades\DB::beginTransaction();

            $order = RepackagingOrder::findOrFail($id);
            $this->reverseRepackaging($order);

            $warehouseId = $validated['warehouse_id'];
            $inputProductId = $validated['input_product_id'];
            $inputQty = $validated['input_qty'];

            $batches = \App\Models\Batch::where('product_id', $inputProductId)
                ->where('warehouse_id', $warehouseId)
                ->where('remaining_qty', '>', 0)
                ->orderBy('id', 'asc')
                ->lockForUpdate()
                ->get();

            $totalRawCost = 0;
            $remainingToConsume = $inputQty;
            $consumedBatches = [];

            foreach ($batches as $batch) {
                if ($remainingToConsume <= 0) break;

                $takeQty = min($batch->remaining_qty, $remainingToConsume);
                $costForThisTake = $takeQty * $batch->cost_per_unit;

                $batch->qty_out += $takeQty;
                $batch->remaining_qty -= $takeQty;
                $batch->save();

                $totalRawCost += $costForThisTake;
                $remainingToConsume -= $takeQty;

                $consumedBatches[] = [
                    'batch_id' => $batch->id,
                    'qty_used' => $takeQty,
                    'cost' => $costForThisTake
                ];
            }

            if (round($remainingToConsume, 4) > 0) {
                throw new \Exception("Insufficient raw stock in the selected warehouse.");
            }

            $totalCost = $totalRawCost + $expenses;
            $outputUnitCost = $totalCost / $validated['output_qty'];

            $order->update([
                'warehouse_id' => $warehouseId,
                'date' => $validated['date'],
                'notes' => $validated['notes'] ?? '',
            ]);

            foreach ($consumedBatches as $consumed) {
                \App\Models\RepackagingInput::create([
                    'repackaging_order_id' => $order->id,
                    'batch_id' => $consumed['batch_id'],
                    'qty_used' => $consumed['qty_used'],
                    'cost' => $consumed['cost'],
                ]);

                \App\Models\InventoryTransaction::create([
                    'warehouse_id' => $warehouseId,
                    'product_id' => $inputProductId,
                    'batch_id' => $consumed['batch_id'],
                    'type' => 'repack_input',
                    'qty_in' => 0,
                    'qty_out' => $consumed['qty_used'],
                    'cost' => $consumed['cost'],
                    'reference_type' => RepackagingOrder::class,
                    'reference_id' => $order->id,
                    'date' => $validated['date'],
                    'created_by' => $request->user()->id ?? 1,
                ]);
            }

            $variant = \App\Models\ProductVariant::find($validated['output_variant_id']);

            $newBatch = \App\Models\Batch::create([
                'batch_no' => 'B-' . $order->id . '-' . $variant->product_id . '-FIN-' . strtoupper(\Illuminate\Support\Str::random(4)),
                'product_id' => $variant->product_id,
                'product_variant_id' => $variant->id,
                'warehouse_id' => $warehouseId,
                'qty_in' => $validated['output_qty'],
                'qty_out' => 0,
                'remaining_qty' => $validated['output_qty'],
                'cost_per_unit' => $outputUnitCost,
            ]);

            \App\Models\RepackagingOutput::create([
                'repackaging_order_id' => $order->id,
                'product_variant_id' => $variant->id,
                'qty_produced' => $validated['output_qty'],
                'unit_cost' => $outputUnitCost,
                'total_cost' => $totalCost,
            ]);

            \App\Models\InventoryTransaction::create([
                'warehouse_id' => $warehouseId,
                'product_id' => $variant->product_id,
                'batch_id' => $newBatch->id,
                'type' => 'repack_output',
                'qty_in' => $validated['output_qty'],
                'qty_out' => 0,
                'cost' => $totalCost,
                'reference_type' => RepackagingOrder::class,
                'reference_id' => $order->id,
                'date' => $validated['date'],
                'created_by' => $request->user()->id ?? 1,
            ]);

            $totalOutputKg = $validated['output_qty'] * $variant->unit_qty;
            if ($inputQty != $totalOutputKg) {
                $diff = $totalOutputKg - $inputQty;
                \App\Models\RepackagingAdjustment::create([
                    'repackaging_order_id' => $order->id,
                    'type' => $diff > 0 ? 'gain' : 'loss',
                    'qty' => abs($diff),
                    'reason' => 'Yield mismatch during repackaging',
                ]);
            }

            $inventoryRawAcc = \App\Models\ChartOfAccount::firstOrCreate(['name' => 'Inventory (Raw)', 'type' => 'asset']);
            $inventoryFinAcc = \App\Models\ChartOfAccount::firstOrCreate(['name' => 'Inventory (Finished)', 'type' => 'asset']);

            $journal = \App\Models\Journal::create([
                'journal_no' => 'JNL-' . strtoupper(\Illuminate\Support\Str::random(6)),
                'date' => $validated['date'],
                'reference_type' => RepackagingOrder::class,
                'reference_id' => $order->id,
                'notes' => 'API Repackaging ' . $order->ref_no . ' (Updated)',
                'created_by' => $request->user()->id ?? 1,
            ]);

            \App\Models\JournalEntry::create([
                'journal_id' => $journal->id,
                'account_id' => $inventoryFinAcc->id,
                'type' => 'debit',
                'amount' => $totalCost,
            ]);

            \App\Models\JournalEntry::create([
                'journal_id' => $journal->id,
                'account_id' => $inventoryRawAcc->id,
                'type' => 'credit',
                'amount' => $totalRawCost,
            ]);

            if ($expenses > 0) {
                $cashAcc = \App\Models\ChartOfAccount::firstOrCreate(['name' => 'Cash', 'type' => 'asset']);
                \App\Models\JournalEntry::create([
                    'journal_id' => $journal->id,
                    'account_id' => $cashAcc->id,
                    'type' => 'credit',
                    'amount' => $expenses,
                ]);
            }

            \Illuminate\Support\Facades\DB::commit();
            return response()->json(['message' => 'Repackaging updated successfully', 'repackaging_order' => $order], 200);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    public function destroyRepackaging($id)
    {
        // Reversal logic skipped for API simplification unless specifically requested, just deleting order record
        RepackagingOrder::destroy($id);
        return response()->json(['message' => 'Repackaging deleted']);
    }

    /**
     * Get summary reports (Profit & Loss, Balance Sheet basics).
     */
    public function reports(Request $request)
    {
        $incomeAccs = ChartOfAccount::where('type', 'income')->pluck('id');
        $expenseAccs = ChartOfAccount::where('type', 'expense')->pluck('id');
        $assetAccs = ChartOfAccount::where('type', 'asset')->pluck('id');
        $liabilityAccs = ChartOfAccount::where('type', 'liability')->pluck('id');

        $incomeTotal = JournalEntry::whereIn('account_id', $incomeAccs)->where('type', 'credit')->sum('amount') 
                     - JournalEntry::whereIn('account_id', $incomeAccs)->where('type', 'debit')->sum('amount');
                     
        $expenseTotal = JournalEntry::whereIn('account_id', $expenseAccs)->where('type', 'debit')->sum('amount')
                      - JournalEntry::whereIn('account_id', $expenseAccs)->where('type', 'credit')->sum('amount');

        $totalAssets = JournalEntry::whereIn('account_id', $assetAccs)->where('type', 'debit')->sum('amount') 
                     - JournalEntry::whereIn('account_id', $assetAccs)->where('type', 'credit')->sum('amount');
                     
        $totalLiabilities = JournalEntry::whereIn('account_id', $liabilityAccs)->where('type', 'credit')->sum('amount')
                          - JournalEntry::whereIn('account_id', $liabilityAccs)->where('type', 'debit')->sum('amount');

        return response()->json([
            'income' => $incomeTotal,
            'expenses' => $expenseTotal,
            'profit' => $incomeTotal - $expenseTotal,
            'assets' => $totalAssets,
            'liabilities' => $totalLiabilities,
        ]);
    }

    /**
     * Get all journals.
     */
    public function journals(Request $request)
    {
        $journals = Journal::with(['entries.account', 'creator'])->orderBy('id', 'desc')->paginate(20);
        return response()->json(['journals' => $journals]);
    }

    public function storeJournal(Request $request)
    {
        $data = $request->all();
        if (!isset($data['journal_no'])) {
            $data['journal_no'] = 'JRN-' . time();
        }
        $data['created_by'] = $request->user()->id ?? 1;
        $journal = Journal::create($data);
        return response()->json(['message' => 'Journal created', 'journal' => $journal], 201);
    }

    public function updateJournal(Request $request, $id)
    {
        $journal = Journal::findOrFail($id);
        $journal->update($request->all());
        return response()->json(['message' => 'Journal updated']);
    }

    public function destroyJournal($id)
    {
        Journal::destroy($id);
        return response()->json(['message' => 'Journal deleted']);
    }

    /**
     * Get activity logs.
     */
        public function paymentMethods(Request $request)
    {
        $methods = ChartOfAccount::where('is_payment_method', true)->get();
        return response()->json(['payment_methods' => $methods]);
    }

    public function activityLogs(Request $request)
    {
        $logs = ActivityLog::with('user')->orderBy('id', 'desc')->get();
        return response()->json(['activity_logs' => $logs]);
    }

    public function downloadInvoice($id)
    {
        $sale = Sale::findOrFail($id);
        return app(\App\Http\Controllers\SaleController::class)->pdf($sale);
    }

    // ── Investments ─────────────────────────────────────────────────────────

    public function investmentFormData()
    {
        $accounts = ChartOfAccount::where('is_payment_method', true)->get();
        return response()->json([
            'accounts' => $accounts
        ]);
    }

    public function investments(Request $request)
    {
        $query = Investment::with(['account', 'creator'])->latest();

        if ($request->filled('start_date')) {
            $query->whereDate('date', '>=', $request->start_date);
        }
        if ($request->filled('end_date')) {
            $query->whereDate('date', '<=', $request->end_date);
        }
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        $perPage = $request->input('per_page', 15);
        $investments = $query->paginate($perPage);

        return response()->json($investments);
    }

    public function storeInvestment(Request $request)
    {
        $validated = $request->validate([
            'date' => 'required|date',
            'type' => 'required|in:investment,withdraw',
            'amount' => 'required|numeric|min:0.01',
            'payment_method' => 'required|exists:chart_of_accounts,id',
            'investor_name' => 'nullable|string|max:255',
            'reference' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ]);

        try {
            DB::beginTransaction();

            $validated['created_by'] = $request->user()->id;
            $investment = Investment::create($validated);

            $this->createInvestmentJournals($investment, $request->user()->id);

            DB::commit();
            return response()->json(['message' => ucfirst($investment->type) . ' recorded successfully.', 'investment' => $investment], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Error recording transaction: ' . $e->getMessage()], 500);
        }
    }

    public function updateInvestment(Request $request, $id)
    {
        $investment = Investment::findOrFail($id);

        $validated = $request->validate([
            'date' => 'required|date',
            'type' => 'required|in:investment,withdraw',
            'amount' => 'required|numeric|min:0.01',
            'payment_method' => 'required|exists:chart_of_accounts,id',
            'investor_name' => 'nullable|string|max:255',
            'reference' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ]);

        try {
            DB::beginTransaction();

            // Reverse old journals
            if ($investment->journal) {
                $investment->journal->entries()->delete();
                $investment->journal()->delete();
            }

            $investment->update($validated);

            // Create new journals
            $this->createInvestmentJournals($investment, $request->user()->id);

            DB::commit();
            return response()->json(['message' => ucfirst($investment->type) . ' updated successfully.', 'investment' => $investment]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Error updating transaction: ' . $e->getMessage()], 500);
        }
    }

    public function destroyInvestment($id)
    {
        $investment = Investment::findOrFail($id);
        try {
            DB::beginTransaction();
            if ($investment->journal) {
                $investment->journal->entries()->delete();
                $investment->journal()->delete();
            }
            $investment->delete();
            DB::commit();
            return response()->json(['message' => 'Transaction deleted successfully.']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Error deleting transaction: ' . $e->getMessage()], 500);
        }
    }

    private function createInvestmentJournals(Investment $investment, $userId)
    {
        $journal = Journal::create([
            'journal_no' => 'JNL-' . strtoupper(Str::random(6)),
            'date' => $investment->date,
            'reference_type' => Investment::class,
            'reference_id' => $investment->id,
            'notes' => ucfirst($investment->type) . ' - ' . ($investment->reference ?? 'N/A'),
            'created_by' => $userId ?? 1,
        ]);

        $equityAcc = ChartOfAccount::firstOrCreate(['name' => 'Owner\'s Equity / Capital', 'type' => 'equity', 'is_payment_method' => false]);
        $cashAcc = ChartOfAccount::findOrFail($investment->payment_method);

        if ($investment->type === 'investment') {
            // Debit Cash, Credit Equity
            JournalEntry::create(['journal_id' => $journal->id, 'account_id' => $cashAcc->id, 'type' => 'debit', 'amount' => $investment->amount]);
            JournalEntry::create(['journal_id' => $journal->id, 'account_id' => $equityAcc->id, 'type' => 'credit', 'amount' => $investment->amount]);
        } else {
            // Withdrawal: Debit Equity, Credit Cash
            JournalEntry::create(['journal_id' => $journal->id, 'account_id' => $equityAcc->id, 'type' => 'debit', 'amount' => $investment->amount]);
            JournalEntry::create(['journal_id' => $journal->id, 'account_id' => $cashAcc->id, 'type' => 'credit', 'amount' => $investment->amount]);
        }
    }

    /**
     * Get Admin Notifications
     */
    public function notifications(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }

        $notifications = $user->notifications()->take(50)->get();
        $unreadCount = $user->unreadNotifications()->count();

        return response()->json([
            'notifications' => $notifications,
            'unread_count' => $unreadCount
        ]);
    }

    /**
     * Mark all notifications as read
     */
    public function markNotificationsRead(Request $request)
    {
        $user = $request->user();
        if ($user) {
            $user->unreadNotifications->markAsRead();
        }
        return response()->json(['success' => true]);
    }

    /**
     * Get Customer Ledger with true pagination and running balance
     */
    public function customerLedger(Request $request, $id)
    {
        $customer = \App\Models\Customer::findOrFail($id);
        
        $customer->load(['sales' => function ($query) {
            $query->orderBy('date', 'asc');
        }]);

        $arAcc = \App\Models\ChartOfAccount::where('name', 'Accounts Receivable')->first();
        
        if (!$arAcc) {
            return response()->json(['ledger' => ['data' => []], 'total_due' => 0]);
        }

        $customerJournalIds = \App\Models\Journal::where(function($q) use ($customer) {
            $q->where('reference_type', \App\Models\Customer::class)->where('reference_id', $customer->id);
        })->orWhere(function($q) use ($customer) {
            $q->where('reference_type', \App\Models\Sale::class)->whereIn('reference_id', $customer->sales()->pluck('id'));
        })->pluck('id');

        // Build the base query for ledger entries
        $query = \App\Models\JournalEntry::with('journal')
            ->whereIn('journal_id', $customerJournalIds)
            ->where('account_id', $arAcc->id)
            ->orderBy('id', 'asc'); // Must be chronological for running balance

        // Execute pagination
        $paginatedEntries = $query->paginate(20);

        // If not on the first page, calculate the sum of all prior debits and credits
        $initialBalance = 0;
        if ($paginatedEntries->currentPage() > 1 && $paginatedEntries->first()) {
            $firstEntryIdOnPage = $paginatedEntries->first()->id;
            
            $priorDebits = \App\Models\JournalEntry::whereIn('journal_id', $customerJournalIds)
                ->where('account_id', $arAcc->id)
                ->where('id', '<', $firstEntryIdOnPage)
                ->where('type', 'debit')
                ->sum('amount');
                
            $priorCredits = \App\Models\JournalEntry::whereIn('journal_id', $customerJournalIds)
                ->where('account_id', $arAcc->id)
                ->where('id', '<', $firstEntryIdOnPage)
                ->where('type', 'credit')
                ->sum('amount');
                
            $initialBalance = $priorDebits - $priorCredits;
        }

        $formattedLedger = [];
        $runningBalance = $initialBalance;

        foreach ($paginatedEntries as $entry) {
            $journal = $entry->journal;
            $debit = $entry->type === 'debit' ? $entry->amount : 0;
            $credit = $entry->type === 'credit' ? $entry->amount : 0;
            $runningBalance = $runningBalance + $debit - $credit;

            $formattedLedger[] = [
                'id' => $entry->id,
                'date' => $journal->date ?? $journal->created_at->toDateString(),
                'description' => $journal->description ?? 'Transaction',
                'debit' => $debit,
                'credit' => $credit,
                'balance' => $runningBalance
            ];
        }

        // Return a custom paginated structure
        return response()->json([
            'ledger' => [
                'current_page' => $paginatedEntries->currentPage(),
                'data' => array_reverse($formattedLedger), // Return newest first on this page for UI
                'last_page' => $paginatedEntries->lastPage(),
                'total' => $paginatedEntries->total()
            ],
            'total_due' => $customer->total_due, // Provide current overall due
            'wallet_balance' => $customer->wallet_balance
        ]);
    }

    // ── Expense Categories ────────────────────────────────────────────────────────
    
    public function expenseCategoryFormData()
    {
        $coas = \App\Models\ChartOfAccount::where('type', 'expense')->where('status', 1)->get(['id', 'name', 'code']);
        $categories = \App\Models\ExpenseCategory::where('status', 1)->get(['id', 'name']);
        return response()->json([
            'chart_of_accounts' => $coas,
            'categories' => $categories
        ]);
    }

    public function expenseCategories(Request $request)
    {
        $categories = \App\Models\ExpenseCategory::with('chartOfAccount')->paginate($request->get('per_page', 20));
        return response()->json([
            'categories' => $categories
        ]);
    }

    public function storeExpenseCategory(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:50',
            'status' => 'boolean',
            'parent_id' => 'nullable|exists:expense_categories,id',
            'chart_of_account_id' => 'nullable|exists:chart_of_accounts,id'
        ]);

        $category = \App\Models\ExpenseCategory::create($validated);
        return response()->json(['message' => 'Expense category created', 'category' => $category], 201);
    }

    public function updateExpenseCategory(Request $request, $id)
    {
        $category = \App\Models\ExpenseCategory::findOrFail($id);
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:50',
            'status' => 'boolean',
            'parent_id' => 'nullable|exists:expense_categories,id',
            'chart_of_account_id' => 'nullable|exists:chart_of_accounts,id'
        ]);

        $category->update($validated);
        return response()->json(['message' => 'Expense category updated', 'category' => $category]);
    }

    public function destroyExpenseCategory($id)
    {
        $category = \App\Models\ExpenseCategory::findOrFail($id);
        if ($category->expenses()->count() > 0) {
            return response()->json(['error' => 'Cannot delete category with associated expenses'], 400);
        }
        $category->delete();
        return response()->json(['message' => 'Expense category deleted']);
    }

    // ── Expenses ────────────────────────────────────────────────────────────────
    
    public function expenseFormData()
    {
        $categories = \App\Models\ExpenseCategory::where('status', 1)->get(['id', 'name']);
        $paymentMethods = \App\Models\ChartOfAccount::where('is_cash_bank', 1)->where('status', 1)->get(['id', 'name']);
        
        return response()->json([
            'categories' => $categories,
            'paymentMethods' => $paymentMethods
        ]);
    }

    public function expenses(Request $request)
    {
        $query = \App\Models\Expense::with(['category', 'paymentMethod']);

        if ($request->filled('start_date')) {
            $query->whereDate('date', '>=', $request->start_date);
        }
        
        if ($request->filled('end_date')) {
            $query->whereDate('date', '<=', $request->end_date);
        }

        if ($request->filled('category_id')) {
            $query->where('expense_category_id', $request->category_id);
        }

        $expenses = $query->orderBy('date', 'desc')->orderBy('id', 'desc')->paginate($request->get('per_page', 20));

        return response()->json([
            'expenses' => $expenses
        ]);
    }

    public function storeExpense(Request $request)
    {
        $validated = $request->validate([
            'expense_category_id' => 'required|exists:expense_categories,id',
            'payment_method_id' => 'required|exists:chart_of_accounts,id',
            'amount' => 'required|numeric|min:0',
            'date' => 'required|date',
            'reference' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ]);

        $validated['created_by'] = auth()->id();

        // Start transaction
        \DB::beginTransaction();
        try {
            $expense = \App\Models\Expense::create($validated);
            
            // Generate Journal Entry
            $category = \App\Models\ExpenseCategory::find($validated['expense_category_id']);
            
            if ($category && $category->chart_of_account_id) {
                $journal = \App\Models\Journal::create([
                    'date' => $validated['date'],
                    'description' => 'Expense: ' . ($validated['notes'] ?? 'Auto generated'),
                    'reference' => 'EXP-' . $expense->id,
                    'created_by' => auth()->id(),
                    'warehouse_id' => null,
                ]);

                // Debit Expense Account
                \App\Models\JournalEntry::create([
                    'journal_id' => $journal->id,
                    'chart_of_account_id' => $category->chart_of_account_id,
                    'type' => 'debit',
                    'amount' => $validated['amount'],
                ]);

                // Credit Payment Method
                \App\Models\JournalEntry::create([
                    'journal_id' => $journal->id,
                    'chart_of_account_id' => $validated['payment_method_id'],
                    'type' => 'credit',
                    'amount' => $validated['amount'],
                ]);
                
                $expense->reference_type = \App\Models\Journal::class;
                $expense->reference_id = $journal->id;
                $expense->save();
            }
            
            \DB::commit();
            return response()->json(['message' => 'Expense created successfully', 'expense' => $expense], 201);
        } catch (\Exception $e) {
            \DB::rollBack();
            return response()->json(['error' => 'Failed to create expense: ' . $e->getMessage()], 500);
        }
    }

    public function updateExpense(Request $request, $id)
    {
        $expense = \App\Models\Expense::findOrFail($id);
        
        $validated = $request->validate([
            'expense_category_id' => 'required|exists:expense_categories,id',
            'payment_method_id' => 'required|exists:chart_of_accounts,id',
            'amount' => 'required|numeric|min:0',
            'date' => 'required|date',
            'reference' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ]);

        \DB::beginTransaction();
        try {
            $expense->update($validated);
            
            // Update Journal Entry if it exists
            if ($expense->reference_type === \App\Models\Journal::class && $expense->reference_id) {
                $journal = \App\Models\Journal::find($expense->reference_id);
                if ($journal) {
                    $journal->update([
                        'date' => $validated['date'],
                        'description' => 'Expense: ' . ($validated['notes'] ?? 'Auto generated'),
                    ]);
                    
                    // Recreate entries
                    $journal->entries()->delete();
                    
                    $category = \App\Models\ExpenseCategory::find($validated['expense_category_id']);
                    if ($category && $category->chart_of_account_id) {
                         // Debit Expense Account
                        \App\Models\JournalEntry::create([
                            'journal_id' => $journal->id,
                            'chart_of_account_id' => $category->chart_of_account_id,
                            'type' => 'debit',
                            'amount' => $validated['amount'],
                        ]);

                        // Credit Payment Method
                        \App\Models\JournalEntry::create([
                            'journal_id' => $journal->id,
                            'chart_of_account_id' => $validated['payment_method_id'],
                            'type' => 'credit',
                            'amount' => $validated['amount'],
                        ]);
                    }
                }
            }
            
            \DB::commit();
            return response()->json(['message' => 'Expense updated successfully', 'expense' => $expense]);
        } catch (\Exception $e) {
            \DB::rollBack();
            return response()->json(['error' => 'Failed to update expense: ' . $e->getMessage()], 500);
        }
    }

    public function destroyExpense($id)
    {
        $expense = \App\Models\Expense::findOrFail($id);
        
        \DB::beginTransaction();
        try {
            // Delete Journal Entry if it exists
            if ($expense->reference_type === \App\Models\Journal::class && $expense->reference_id) {
                $journal = \App\Models\Journal::find($expense->reference_id);
                if ($journal) {
                    $journal->entries()->delete();
                    $journal->delete();
                }
            }
            
            $expense->delete();
            \DB::commit();
            return response()->json(['message' => 'Expense deleted successfully']);
        } catch (\Exception $e) {
            \DB::rollBack();
            return response()->json(['error' => 'Failed to delete expense: ' . $e->getMessage()], 500);
        }
    }
}
