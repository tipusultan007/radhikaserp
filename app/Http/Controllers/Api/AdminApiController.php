<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\SaleController;
use App\Models\ActivityLog;
use App\Models\Batch;
use App\Models\ChartOfAccount;
use App\Models\Customer;
use App\Models\CustomerBonus;
use App\Models\CustomerTargetItem;
use App\Models\CustomerTargetScheme;
use App\Models\District;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\InventoryTransaction;
use App\Models\Investment;
use App\Models\Journal;
use App\Models\JournalEntry;
use App\Models\PriceHistory;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\RepackagingAdjustment;
use App\Models\RepackagingInput;
use App\Models\RepackagingOrder;
use App\Models\RepackagingOutput;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SalePayment;
use App\Models\StockAdjustment;
use App\Models\StockTransfer;
use App\Models\StockTransferItem;
use App\Models\Supplier;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\WarehouseStock;
use App\Notifications\AdminAlertNotification;
use App\Notifications\CustomerAlertNotification;
use App\Services\SmsService;
use App\Services\SteadfastService;
use App\Services\StockReconciliationService;
use App\Services\CustomerTargetService;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AdminApiController extends Controller
{
    /**
     * Get dashboard statistics.
     */
    public function dashboard(Request $request)
    {
        $today = Carbon::today()->format('Y-m-d');

        $totalSales = Sale::sum('total');
        $totalCustomers = Customer::count();
        $totalProducts = Product::count();
        $totalExpenses = Expense::sum('amount');

        $todaySales = Sale::whereDate('date', $today)->sum('total');
        $todayExpenses = Expense::whereDate('date', $today)->sum('amount');
        $todayPayments = SalePayment::whereDate('date', $today)->sum('amount');
        $todayDues = Sale::whereDate('date', $today)->sum('due_amount');

        $recentSales = Sale::with('customer')->orderBy('id', 'desc')->take(5)->get();
        $recentLogs = ActivityLog::with('user')->orderBy('id', 'desc')->take(5)->get();

        $unreadCount = 0;
        if ($request->user()) {
            $unreadCount = $request->user()->unreadNotifications()->count();
        }

        // 7-day revenue/expense chart data optimized (avoid N+1 queries in loop)
        $sevenDaysAgo = Carbon::now()->subDays(6)->format('Y-m-d');

        $salesData = Sale::where('date', '>=', $sevenDaysAgo)
            ->selectRaw('DATE(date) as date_val, SUM(total) as total_sum')
            ->groupBy('date_val')
            ->pluck('total_sum', 'date_val');

        $expensesData = Expense::where('date', '>=', $sevenDaysAgo)
            ->selectRaw('DATE(date) as date_val, SUM(amount) as total_sum')
            ->groupBy('date_val')
            ->pluck('total_sum', 'date_val');

        $chartData = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i)->format('Y-m-d');

            $chartData[] = [
                'date' => Carbon::now()->subDays($i)->format('M d'),
                'revenue' => $salesData->get($date, 0),
                'expense' => $expensesData->get($date, 0),
            ];
        }

        // Low stock alerts (< 10)
        $lowStock = WarehouseStock::with(['productVariant.product.unit', 'productVariant.unit', 'warehouse'])
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
        $products = Product::with(['unit', 'variants' => function ($q) {
            $q->with(['priceHistory', 'unit']);
        }])->orderBy('id', 'desc')->get();

        return response()->json(['products' => $products]);
    }

    public function storeProduct(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:raw,finished',
            'unit_id' => 'required|exists:units,id',
            'status' => 'nullable|boolean',
            'variants' => 'required|array|min:1',
            'variants.*.name' => 'required|string|max:255',
            'variants.*.unit_qty' => 'required|numeric|min:0',
            'variants.*.unit_id' => 'required|exists:units,id',
            'variants.*.price' => 'nullable|numeric|min:0',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
        ]);
        $validated['status'] = $request->has('status') && $request->status;

        do {
            $sku = 'PRD-'.strtoupper(Str::random(6));
        } while (Product::where('sku', $sku)->exists());
        $validated['sku'] = $sku;

        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('products', 'public');
            $validated['image_path'] = $path;
        }

        $product = Product::create($validated);

        $createdVariants = [];
        foreach ($request->variants as $varData) {
            do {
                $varSku = $product->sku.'-'.strtoupper(Str::random(4));
            } while (ProductVariant::where('sku', $varSku)->exists());

            $createdVariants[] = ProductVariant::create([
                'product_id' => $product->id,
                'name' => $varData['name'],
                'sku' => $varSku,
                'unit_qty' => $varData['unit_qty'],
                'unit_id' => $varData['unit_id'],
                'price' => $varData['price'] ?? 0,
                'status' => true,
            ]);
        }

        return response()->json(['message' => 'Product created', 'product' => $product, 'variants' => $createdVariants], 201);
    }

    public function updateProduct(Request $request, $id)
    {
        $product = Product::findOrFail($id);
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:raw,finished',
            'unit_id' => 'required|exists:units,id',
            'status' => 'nullable|boolean',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
        ]);
        $validated['status'] = $request->has('status') && $request->status;

        if ($request->hasFile('image')) {
            if ($product->image_path && Storage::disk('public')->exists($product->image_path)) {
                Storage::disk('public')->delete($product->image_path);
            }
            $path = $request->file('image')->store('products', 'public');
            $validated['image_path'] = $path;
        }

        $product->update($validated);

        return response()->json(['message' => 'Product updated', 'product' => $product]);
    }

    public function destroyProduct($id)
    {
        try {
            $product = Product::findOrFail($id);
            // Delete variants first to prevent constraint violations
            $product->variants()->delete();
            $product->delete();

            return response()->json(['message' => 'Product deleted']);
        } catch (QueryException $e) {
            if ($e->getCode() == '23000') {
                return response()->json(['message' => 'Cannot delete product because it has associated stock, sales, or other records.'], 400);
            }

            return response()->json(['message' => 'Failed to delete product: '.$e->getMessage()], 500);
        }
    }

    public function generateProductVariantSku(Request $request)
    {
        $prefix = 'VAR';
        if ($request->has('product_id') && $request->product_id != '') {
            $product = Product::find($request->product_id);
            if ($product) {
                $prefix = $product->sku;
            }
        }

        do {
            $sku = $prefix.'-'.strtoupper(Str::random(4));
        } while (ProductVariant::where('sku', $sku)->exists());

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
            'unit_id' => 'required|exists:units,id',
            'price' => 'nullable|numeric|min:0',
            'dealer_price' => 'nullable|numeric|min:0',
            'special_dealer_price' => 'nullable|numeric|min:0',
            'status' => 'nullable|boolean',
        ]);

        $validated['status'] = $request->has('status') && $request->status;
        $validated['price'] = $validated['price'] ?? 0;

        $variant = ProductVariant::create($validated);

        if ($variant->price > 0) {
            PriceHistory::create([
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
        $variant = ProductVariant::findOrFail($id);

        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'name' => 'required|string|max:255',
            'sku' => 'required|string|unique:product_variants,sku,'.$variant->id.'|max:255',
            'barcode' => 'nullable|string|max:255',
            'unit_qty' => 'required|numeric|min:0',
            'unit_id' => 'required|exists:units,id',
            'price' => 'nullable|numeric|min:0',
            'dealer_price' => 'nullable|numeric|min:0',
            'special_dealer_price' => 'nullable|numeric|min:0',
            'status' => 'nullable|boolean',
        ]);

        $validated['status'] = $request->has('status') && $request->status;
        $validated['price'] = $validated['price'] ?? 0;

        $oldPrice = $variant->price;
        $variant->update($validated);

        if ($oldPrice != $variant->price) {
            PriceHistory::create([
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
        ProductVariant::destroy($id);

        return response()->json(['message' => 'Variant deleted']);
    }

    /**
     * Get all sales.
     */
    public function sales(Request $request)
    {
        $query = Sale::with(['customer', 'items.productVariant.product.unit', 'items.productVariant.unit', 'warehouse', 'creator']);

        if ($request->filled('invoice_no')) {
            $query->where('invoice_no', 'LIKE', '%'.$request->invoice_no.'%');
        }
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('invoice_no', 'LIKE', '%'.$search.'%')
                    ->orWhereHas('customer', function ($cq) use ($search) {
                        $cq->where('name', 'LIKE', '%'.$search.'%')
                            ->orWhere('phone', 'LIKE', '%'.$search.'%')
                            ->orWhere('company', 'LIKE', '%'.$search.'%');
                    });
            });
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
            $query->whereBetween('date', [$request->start_date.' 00:00:00', $request->end_date.' 23:59:59']);
        } elseif ($request->filled('start_date')) {
            $query->where('date', '>=', $request->start_date.' 00:00:00');
        } elseif ($request->filled('end_date')) {
            $query->where('date', '<=', $request->end_date.' 23:59:59');
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
            'delivery_type' => 'nullable|integer|in:0,1',
        ]);

        $discount = $validated['discount'] ?? 0;
        $deliveryCharge = $validated['delivery_charge'] ?? 0;
        $isPromotional = ! empty($validated['is_promotional']);

        try {
            DB::beginTransaction();

            $warehouseId = $validated['warehouse_id'];

            // Calculate Totals and Weight
            $subtotal = 0;
            $grandTotalWeight = 0;
            foreach ($validated['items'] as $item) {
                $subtotal += $item['qty'] * $item['unit_price'];
                $variant = ProductVariant::find($item['product_variant_id']);
                $unitQty = $variant ? $variant->getBaseQuantity() : 1;
                $grandTotalWeight += ($item['qty'] * $unitQty);
            }

            $deliveryType = $validated['delivery_type'] ?? 1; // Default to point delivery
            if ($request->input('delivery_method') === 'steadfast') {
                if ($deliveryType == 0) {
                    $deliveryCharge = max(1, ceil($grandTotalWeight)) * 20;
                } else {
                    $deliveryCharge = 0;
                }
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
                'paid_amount' => $paidAmount,
                'due_amount' => $dueAmount,
                'payment_status' => $paymentStatus,
                'payment_method' => $isPromotional ? null : ($validated['payment_method'] ?? null),
                'payment_details' => $validated['payment_details'] ?? null,
                'estimate_delivery_date' => $request->input('estimate_delivery_date'),
                'delivery_status' => $request->input('delivery_method') === 'steadfast' ? 'accepted' : $request->input('delivery_status'),
                'delivery_method' => $request->input('delivery_method', 'manual'),
                'delivery_type' => $deliveryType,
                'shipping_address' => $request->input('shipping_address'),
                'created_by' => $request->user()->id ?? 1,
            ]);

            // Update Customer Due and Wallet (only if not promotional)
            if (! $isPromotional && $customer) {
                $customer->wallet_balance = $customer->wallet_balance - $walletUsed + $newAdvance;
                if ($dueAmount > 0) {
                    $customer->total_due += $dueAmount;
                }
                $customer->save();
            }

            // Record Payment (only if not promotional)
            if (! $isPromotional && $paidAmount > 0) {
                SalePayment::create([
                    'sale_id' => $sale->id,
                    'amount' => $paidAmount,
                    'method' => 'cash',
                    'date' => $validated['date'],
                    'reference' => 'POS Payment (Mobile)',
                ]);
            }

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
            $promoAcc = ChartOfAccount::firstOrCreate(['name' => 'Promotional Expense', 'type' => 'expense']);

            $cashAcc = isset($validated['payment_method']) ? ChartOfAccount::find($validated['payment_method']) : ChartOfAccount::firstOrCreate(['name' => 'Cash', 'type' => 'asset']);
            $arAcc = ChartOfAccount::firstOrCreate(['name' => 'Accounts Receivable', 'type' => 'asset']);
            $advAcc = ChartOfAccount::firstOrCreate(['name' => 'Customer Advance', 'type' => 'liability']);

            $journal = Journal::create([
                'journal_no' => 'JNL-'.strtoupper(Str::random(6)),
                'date' => $validated['date'],
                'reference_type' => Sale::class,
                'reference_id' => $sale->id,
                'notes' => 'POS Sale '.$sale->invoice_no.' (Mobile'.($isPromotional ? ', Promotional' : '').')',
                'created_by' => $request->user()->id ?? 1,
            ]);

            // 1. Revenue & Payment
            if ($isPromotional) {
                JournalEntry::create(['journal_id' => $journal->id, 'account_id' => $promoAcc->id, 'type' => 'debit', 'amount' => $total]);
            } else {
                if ($paidAmount > 0) {
                    JournalEntry::create(['journal_id' => $journal->id, 'account_id' => $cashAcc->id, 'type' => 'debit', 'amount' => $paidAmount]);
                }
                if ($walletUsed > 0) {
                    JournalEntry::create(['journal_id' => $journal->id, 'account_id' => $advAcc->id, 'type' => 'debit', 'amount' => $walletUsed]);
                }
                if ($dueAmount > 0) {
                    JournalEntry::create(['journal_id' => $journal->id, 'account_id' => $arAcc->id, 'type' => 'debit', 'amount' => $dueAmount]);
                }
            }
            JournalEntry::create(['journal_id' => $journal->id, 'account_id' => $salesRevAcc->id, 'type' => 'credit', 'amount' => $total]);
            if (! $isPromotional && $newAdvance > 0) {
                JournalEntry::create(['journal_id' => $journal->id, 'account_id' => $advAcc->id, 'type' => 'credit', 'amount' => $newAdvance]);
            }

            // COGS & Inventory Reduction entries are deferred until dispatch
            if (in_array($sale->delivery_status, ['dispatched', 'delivered'])) {
                $this->consumeStockForSale($sale, $journal->id, $request->user()->id ?? 1);
            }

            if ($sale->delivery_method === 'steadfast') {
                SteadfastService::dispatchSale($sale);
            }

            DB::commit();

            if ($paidAmount > 0 && $customer && ! empty($customer->phone)) {
                SmsService::sendSms($customer->phone, "Dear {$customer->name}, we have received your payment of BDT {$paidAmount} for order {$sale->invoice_no}. Thank you!");
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
            'delivery_type' => 'nullable|integer|in:0,1',
        ]);

        try {
            DB::beginTransaction();

            $customer = $sale->customer;
            $oldPaid = $sale->paid_amount;
            $oldDeliveryStatus = $sale->delivery_status;

            $this->reverseSale($sale);

            $warehouseId = $validated['warehouse_id'];

            // Calculate Totals and Weight
            $subtotal = 0;
            $grandTotalWeight = 0;
            foreach ($validated['items'] as $item) {
                $subtotal += $item['qty'] * $item['unit_price'];
                $variant = ProductVariant::find($item['product_variant_id']);
                $unitQty = $variant ? $variant->getBaseQuantity() : 1;
                $grandTotalWeight += ($item['qty'] * $unitQty);
            }

            $discount = $validated['discount'] ?? 0;
            $deliveryCharge = $validated['delivery_charge'] ?? 0;
            $deliveryType = $validated['delivery_type'] ?? $sale->delivery_type ?? 1;

            if ($request->input('delivery_method', $sale->delivery_method) === 'steadfast') {
                if ($deliveryType == 0) {
                    $deliveryCharge = max(1, ceil($grandTotalWeight)) * 20;
                } else {
                    $deliveryCharge = 0;
                }
            }

            $total = max(0, $subtotal + $deliveryCharge - $discount);

            $isPromotional = $request->has('is_promotional') ? ! empty($request->input('is_promotional')) : ! empty($sale->is_promotional);

            if ($isPromotional) {
                $paidAmount = 0;
                $walletUsed = 0;
                $newAdvance = 0;
                $dueAmount = 0;
                $paymentStatus = 'paid';
                $paymentMethod = null;
            } else {
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
            }

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
                    $response = SteadfastService::createOrder($steadfastData);
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
                'is_promotional' => $isPromotional,
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
                'delivery_type' => $deliveryType,
                'shipping_address' => $request->input('shipping_address', $sale->shipping_address),
                'consignment_id' => $consignmentId,
            ]);

            // Update Customer Due and Wallet (only for non-promotional sales)
            if (! $isPromotional && $customer) {
                $customer->wallet_balance = $customer->wallet_balance - $walletUsed + $newAdvance;
                if ($dueAmount > 0) {
                    $customer->total_due += $dueAmount;
                }
                $customer->save();
            }

            // Record Payment (only for non-promotional sales)
            if (! $isPromotional && $paidAmount > 0) {
                SalePayment::create([
                    'sale_id' => $sale->id,
                    'amount' => $paidAmount,
                    'method' => 'cash',
                    'date' => $sale->date,
                    'reference' => 'POS Payment (Mobile Updated)',
                ]);

                if ($customer) {
                    Notification::send($customer, new CustomerAlertNotification(
                        'Payment Received',
                        'We have received a payment of BDT '.number_format($paidAmount, 2)." for your order #{$sale->invoice_no}.",
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

                $variant = ProductVariant::find($variantId);
                $unitQty = $variant ? $variant->getBaseQuantity() : 1;
                $grandTotalWeight += ($itemQty * $unitQty);

                if ($shouldConsumeStock) {
                    $batches = Batch::where('product_variant_id', $variantId)
                        ->where('warehouse_id', $warehouseId)
                        ->where('remaining_qty', '>', 0)
                        ->orderBy('id', 'asc')
                        ->lockForUpdate()
                        ->get();

                    $remainingToConsume = $itemQty;

                    foreach ($batches as $batch) {
                        if ($remainingToConsume <= 0) {
                            break;
                        }
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
                        $autoBatch = StockReconciliationService::autoRepackageRawToVariant(
                            $warehouseId,
                            $variantId,
                            $remainingToConsume,
                            $sale->date,
                            'Auto-repackaged for Mobile Sale #'.($sale->invoice_no ?? $sale->id)
                        );

                        if ($autoBatch && $autoBatch->remaining_qty > 0) {
                            $takeQty = min((float) $autoBatch->remaining_qty, $remainingToConsume);
                            $cogsForThisTake = $takeQty * $autoBatch->cost_per_unit;

                            $autoBatch->qty_out += $takeQty;
                            $autoBatch->remaining_qty -= $takeQty;
                            $autoBatch->save();

                            $totalCogs += $cogsForThisTake;
                            $remainingToConsume -= $takeQty;

                            SaleItem::create([
                                'sale_id' => $sale->id,
                                'product_variant_id' => $variantId,
                                'batch_id' => $autoBatch->id,
                                'qty' => $takeQty,
                                'unit_price' => $unitPrice,
                                'total_price' => $takeQty * $unitPrice,
                                'total_weight' => $takeQty * $unitQty,
                            ]);

                            InventoryTransaction::create([
                                'warehouse_id' => $warehouseId,
                                'product_id' => $autoBatch->product_id,
                                'product_variant_id' => $variantId,
                                'batch_id' => $autoBatch->id,
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
                    }

                    if (round($remainingToConsume, 4) > 0) {
                        throw new \Exception("Insufficient stock for variant ID: {$variantId}. Shortfall: ".$remainingToConsume);
                    }

                    // Check Low Stock
                    $currentStock = WarehouseStock::where('product_variant_id', $variantId)
                        ->where('warehouse_id', $warehouseId)
                        ->value('stock');

                    if ($currentStock !== null && $currentStock < 10) {
                        try {
                            $admins = User::role(['Admin', 'Accountant', 'Manager'])->get();
                            if ($admins->isEmpty()) {
                                $admins = User::where('id', 1)->get();
                            }
                            $varName = $variant ? $variant->name : 'Item';
                            Notification::send($admins, new AdminAlertNotification(
                                'Low Stock Alert',
                                "Product {$varName} is low on stock ({$currentStock} remaining).",
                                'stock',
                                ['product_variant_id' => $variantId]
                            ));
                        } catch (\Exception $e) {
                        }
                    }
                } else {
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
            }

            $sale->total_weight = $grandTotalWeight;
            $sale->save();

            // Accounting Entries
            $inventoryFinAcc = ChartOfAccount::firstOrCreate(['name' => 'Inventory (Finished)', 'type' => 'asset']);
            $cogsAcc = ChartOfAccount::firstOrCreate(['name' => 'Cost of Goods Sold', 'type' => 'expense']);
            $salesRevAcc = ChartOfAccount::firstOrCreate(['name' => 'Sales Revenue', 'type' => 'income']);
            $promoAcc = ChartOfAccount::firstOrCreate(['name' => 'Promotional Expense', 'type' => 'expense']);
            $cashAcc = $paymentMethod ? ChartOfAccount::find($paymentMethod) : ChartOfAccount::firstOrCreate(['name' => 'Cash', 'type' => 'asset']);
            $arAcc = ChartOfAccount::firstOrCreate(['name' => 'Accounts Receivable', 'type' => 'asset']);
            $advAcc = ChartOfAccount::firstOrCreate(['name' => 'Customer Advance', 'type' => 'liability']);

            $journal = Journal::create([
                'journal_no' => 'JNL-'.strtoupper(Str::random(6)),
                'date' => $sale->date,
                'reference_type' => Sale::class,
                'reference_id' => $sale->id,
                'notes' => 'POS Sale '.$sale->invoice_no.' (Mobile Updated'.($isPromotional ? ', Promotional' : '').')',
                'created_by' => $request->user()->id ?? 1,
            ]);

            if ($isPromotional) {
                JournalEntry::create(['journal_id' => $journal->id, 'account_id' => $promoAcc->id, 'type' => 'debit', 'amount' => $total]);
            } else {
                if ($paidAmount > 0) {
                    JournalEntry::create(['journal_id' => $journal->id, 'account_id' => $cashAcc->id, 'type' => 'debit', 'amount' => $paidAmount]);
                }
                if ($walletUsed > 0) {
                    JournalEntry::create(['journal_id' => $journal->id, 'account_id' => $advAcc->id, 'type' => 'debit', 'amount' => $walletUsed]);
                }
                if ($dueAmount > 0) {
                    JournalEntry::create(['journal_id' => $journal->id, 'account_id' => $arAcc->id, 'type' => 'debit', 'amount' => $dueAmount]);
                }
            }
            JournalEntry::create(['journal_id' => $journal->id, 'account_id' => $salesRevAcc->id, 'type' => 'credit', 'amount' => $total]);
            if (! $isPromotional && $newAdvance > 0) {
                JournalEntry::create(['journal_id' => $journal->id, 'account_id' => $advAcc->id, 'type' => 'credit', 'amount' => $newAdvance]);
            }
            // COGS & Inventory Reduction entries are deferred until dispatch
            if (in_array($newDeliveryStatus, ['dispatched', 'delivered'])) {
                $this->consumeStockForSale($sale, $journal->id, $request->user()->id ?? 1);
            }
            if ($oldDeliveryStatus !== 'processing' && $newDeliveryStatus === 'processing') {
                if ($customer) {
                    Notification::send($customer, new CustomerAlertNotification(
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
                    $admins = User::role(['Admin', 'Accountant', 'Manager'])->get();
                    if ($admins->isEmpty()) {
                        $admins = User::where('id', 1)->get();
                    }
                    Notification::send($admins, new AdminAlertNotification(
                        'Order Dispatched',
                        "Order #{$sale->invoice_no} has been dispatched.",
                        'dispatch',
                        ['sale_id' => $sale->id]
                    ));

                    if ($customer) {
                        Notification::send($customer, new CustomerAlertNotification(
                            'Order Dispatched',
                            "Your order #{$sale->invoice_no} has been dispatched and is on its way!",
                            'order_shipped',
                            ['sale_id' => $sale->id],
                            $customer->id
                        ));
                    }
                } catch (\Exception $e) {
                }
            }

            if ($oldDeliveryStatus !== 'delivered' && $newDeliveryStatus === 'delivered') {
                try {
                    $admins = User::role(['Admin', 'Accountant', 'Manager'])->get();
                    if ($admins->isEmpty()) {
                        $admins = User::where('id', 1)->get();
                    }
                    Notification::send($admins, new AdminAlertNotification(
                        'Order Delivered',
                        "Order #{$sale->invoice_no} has been delivered.",
                        'deliver',
                        ['sale_id' => $sale->id]
                    ));

                    if ($customer) {
                        Notification::send($customer, new CustomerAlertNotification(
                            'Order Delivered',
                            "Your order #{$sale->invoice_no} has been delivered successfully.",
                            'order_delivered',
                            ['sale_id' => $sale->id],
                            $customer->id
                        ));
                    }
                } catch (\Exception $e) {
                }
            }

            DB::commit();

            if ($customer && ! empty($customer->phone)) {
                if ($oldDeliveryStatus !== 'processing' && $newDeliveryStatus === 'processing') {
                    SmsService::sendSms($customer->phone, "Dear {$customer->name}, your order #{$sale->invoice_no} has been accepted and is now processing.");
                }

                if ($paidAmount > $oldPaid) {
                    $paidDiff = $paidAmount - $oldPaid;
                    SmsService::sendSms($customer->phone, "Dear {$customer->name}, we have received your payment of BDT {$paidDiff} for order #{$sale->invoice_no}. Thank you!");
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

            return response()->json(['error' => 'Failed to delete sale: '.$e->getMessage()], 400);
        }
    }

    /**
     * Get all customers.
     */
    public function updateDeliveryStatus(Request $request, $id)
    {
        $request->validate([
            'delivery_status' => 'required|in:pending,accepted,processing,dispatched,delivered,cancelled',
        ]);

        try {
            DB::beginTransaction();

            $sale = Sale::with(['items.batch'])->findOrFail($id);
            $oldStatus = $sale->delivery_status;
            $newStatus = $request->delivery_status;

            $wasDispatched = in_array($oldStatus, ['dispatched', 'delivered']);
            $isDispatched = in_array($newStatus, ['dispatched', 'delivered']);

            $journal = Journal::where('reference_type', Sale::class)
                ->where('reference_id', $sale->id)
                ->first();

            if (! $wasDispatched && $isDispatched) {
                if ($journal) {
                    $this->consumeStockForSale($sale, $journal->id, $request->user()->id ?? 1);
                }
            } elseif ($wasDispatched && ! $isDispatched) {
                foreach ($sale->items as $item) {
                    if ($item->batch) {
                        $item->batch->qty_out -= $item->qty;
                        $item->batch->remaining_qty += $item->qty;
                        $item->batch->save();
                    }
                }
                InventoryTransaction::where('reference_type', Sale::class)
                    ->where('reference_id', $sale->id)
                    ->delete();
                if ($journal) {
                    $cogsAcc = ChartOfAccount::where('name', 'Cost of Goods Sold')->first();
                    $invAcc = ChartOfAccount::where('name', 'Inventory (Finished)')->first();
                    if ($cogsAcc) {
                        JournalEntry::where('journal_id', $journal->id)->where('account_id', $cogsAcc->id)->delete();
                    }
                    if ($invAcc) {
                        JournalEntry::where('journal_id', $journal->id)->where('account_id', $invAcc->id)->delete();
                    }
                }
            }

            $sale->delivery_status = $newStatus;

            if ($newStatus === 'accepted' && $oldStatus !== 'accepted' && $sale->delivery_method === 'steadfast') {
                SteadfastService::dispatchSale($sale);
            }

            $sale->save();

            DB::commit();

            return response()->json([
                'message' => 'Delivery status updated to '.$newStatus,
                'sale' => $sale->fresh(),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json(['error' => 'Failed to update status: '.$e->getMessage()], 400);
        }
    }

    public function districts()
    {
        $districts = District::orderBy('name')->get(['id', 'name']);

        return response()->json(['districts' => $districts]);
    }

    public function customers(Request $request)
    {
        $query = Customer::orderBy('id', 'desc');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                    ->orWhere('phone', 'LIKE', "%{$search}%")
                    ->orWhere('email', 'LIKE', "%{$search}%")
                    ->orWhere('company', 'LIKE', "%{$search}%");
            });
        }

        if ($request->filled('district')) {
            $query->where('district', $request->district);
        }

        if ($request->has('all')) {
            $customers = $query->get();
        } else {
            $customers = $query->paginate(20);
        }

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
            'address' => 'required|string',
            'district' => 'nullable|string|max:255',
            'company' => 'nullable|string|max:255',
            'credit_limit' => 'nullable|numeric|min:0',
            'opening_balance' => 'nullable|numeric|min:0',
        ]);

        $validated['credit_limit'] = $validated['credit_limit'] ?? 0;
        $validated['opening_balance'] = $validated['opening_balance'] ?? 0;
        $validated['total_due'] = $validated['opening_balance'];

        if (! empty($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
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
            'customer' => $customer,
        ]);
    }

    public function customerSales($id)
    {
        $sales = Sale::with('items.productVariant.product', 'warehouse')
            ->where('customer_id', $id)
            ->orderBy('date', 'desc')
            ->paginate(20);

        return response()->json(['sales' => $sales]);
    }

    public function customerPayments($id)
    {
        $payments = SalePayment::whereHas('sale', function ($q) use ($id) {
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
            'address' => 'required|string',
            'district' => 'nullable|string|max:255',
            'company' => 'nullable|string|max:255',
            'credit_limit' => 'nullable|numeric|min:0',
            'opening_balance' => 'nullable|numeric|min:0',
        ]);

        $validated['credit_limit'] = $validated['credit_limit'] ?? $customer->credit_limit;
        $newOpeningBalance = $validated['opening_balance'] ?? 0;

        if (! empty($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
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
            'journal_no' => 'OB-CUST-'.strtoupper(Str::random(6)),
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
        $purchases = Purchase::where('supplier_id', $id)
            ->with('warehouse')
            ->orderBy('date', 'desc')
            ->paginate(20);

        return response()->json(['purchases' => $purchases]);
    }

    public function supplierPayments($id)
    {
        $journals = Journal::with('entries.account')
            ->where('reference_type', Supplier::class)
            ->where('reference_id', $id)
            ->whereHas('entries', function ($q) {
                $q->where('type', 'debit');
            })
            ->orderBy('date', 'desc')
            ->paginate(20);

        $journals->getCollection()->transform(function ($journal) {
            $debitEntry = $journal->entries->firstWhere('type', 'debit');
            $creditEntry = $journal->entries->firstWhere('type', 'credit');
            $amount = $journal->entries->where('type', 'debit')->sum('amount');
            if ($amount <= 0 && $debitEntry) {
                $amount = $debitEntry->amount;
            }

            $notes = $journal->notes ?? '';
            $cleanRef = $notes;
            if (preg_match('/\(Ref:\s*(.*?)\)/i', $notes, $matches)) {
                $cleanRef = $matches[1];
            }

            return [
                'id' => $journal->id,
                'amount' => $amount,
                'date' => $journal->date,
                'reference' => $cleanRef,
                'raw_notes' => $notes,
                'payment_method_id' => $creditEntry ? $creditEntry->account_id : null,
                'payment_method' => $creditEntry && $creditEntry->account ? $creditEntry->account->name : 'Cash',
                'sale' => ['invoice_no' => $journal->journal_no],
            ];
        });

        return response()->json(['payments' => $journals]);
    }

    public function supplierLedger(Request $request, $id)
    {
        $supplier = Supplier::findOrFail($id);
        $apAcc = ChartOfAccount::where('name', 'Accounts Payable')->first();

        if (! $apAcc) {
            return response()->json(['ledger' => ['data' => []], 'total_payable' => 0]);
        }

        $purchaseIds = Purchase::where('supplier_id', $id)->pluck('id');

        $supplierJournalIds = Journal::where(function ($q) use ($supplier) {
            $q->where('reference_type', Supplier::class)->where('reference_id', $supplier->id);
        })->orWhere(function ($q) use ($purchaseIds) {
            $q->where('reference_type', Purchase::class)->whereIn('reference_id', $purchaseIds);
        })->pluck('id');

        $query = JournalEntry::with('journal')
            ->whereIn('journal_id', $supplierJournalIds)
            ->where('account_id', $apAcc->id)
            ->orderBy('id', 'asc');

        $paginatedEntries = $query->paginate(20);

        $initialBalance = 0;
        if ($paginatedEntries->currentPage() > 1 && $paginatedEntries->first()) {
            $firstEntryIdOnPage = $paginatedEntries->first()->id;

            $priorCredits = JournalEntry::whereIn('journal_id', $supplierJournalIds)
                ->where('account_id', $apAcc->id)
                ->where('id', '<', $firstEntryIdOnPage)
                ->where('type', 'credit')
                ->sum('amount');

            $priorDebits = JournalEntry::whereIn('journal_id', $supplierJournalIds)
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
            'total_payable' => $supplier->total_payable,
        ]);
    }

    /**
     * Get all purchases.
     */
    public function purchaseFormData()
    {
        $suppliers = Supplier::all();
        $warehouses = Warehouse::all();
        $products = Product::with('unit')->get();
        $variants = ProductVariant::with(['product.unit', 'unit'])->get();

        return response()->json([
            'suppliers' => $suppliers,
            'warehouses' => $warehouses,
            'products' => $products,
            'variants' => $variants,
        ]);
    }

    public function purchases(Request $request)
    {
        $purchases = Purchase::with(['supplier', 'warehouse', 'items.product.unit', 'items.productVariant.unit'])->orderBy('date', 'desc')->get();

        return response()->json(['purchases' => $purchases]);
    }

    public function showPurchase($id)
    {
        $purchase = Purchase::with(['supplier', 'warehouse', 'items.product.unit', 'items.productVariant.unit'])->findOrFail($id);

        return response()->json(['purchase' => $purchase]);
    }

    public function storePurchase(Request $request)
    {
        $validated = $request->validate([
            'supplier_id' => 'required|exists:suppliers,id',
            'warehouse_id' => 'required|exists:warehouses,id',
            'date' => 'required|date',
            'items' => 'required|array|min:1',
            'items.*.item_id' => 'nullable|string',
            'items.*.product_id' => 'nullable|exists:products,id',
            'items.*.product_variant_id' => 'nullable|exists:product_variants,id',
            'items.*.qty' => 'required|numeric|min:0.001',
            'items.*.unit_cost' => 'nullable|numeric|min:0',
        ]);

        try {
            DB::beginTransaction();

            $parsedItems = [];
            $totalCost = 0;
            $rawCostTotal = 0;
            $finCostTotal = 0;

            foreach ($validated['items'] as $item) {
                $unitCost = (isset($item['unit_cost']) && $item['unit_cost'] !== '') ? (float) $item['unit_cost'] : 0;
                $qty = (float) $item['qty'];
                $lineTotal = $qty * $unitCost;
                $totalCost += $lineTotal;

                $productId = $item['product_id'] ?? null;
                $variantId = $item['product_variant_id'] ?? null;

                if (!empty($item['item_id'])) {
                    $parts = explode('_', $item['item_id']);
                    if (count($parts) === 2) {
                        if ($parts[0] === 'variant') {
                            $variantId = $parts[1];
                        } elseif ($parts[0] === 'product') {
                            $productId = $parts[1];
                        }
                    }
                }

                $product = null;
                if ($variantId) {
                    $variant = ProductVariant::with('product')->find($variantId);
                    if ($variant) {
                        $productId = $variant->product_id;
                        $product = $variant->product;
                    }
                } elseif ($productId) {
                    $product = Product::find($productId);
                }

                if (!$productId) {
                    throw new \Exception('Invalid product or variant specified in purchase items.');
                }

                if ($variantId || ($product && $product->type === 'finished')) {
                    $finCostTotal += $lineTotal;
                } else {
                    $rawCostTotal += $lineTotal;
                }

                $parsedItems[] = [
                    'product_id' => $productId,
                    'product_variant_id' => $variantId,
                    'qty' => $qty,
                    'unit_cost' => $unitCost,
                    'line_total' => $lineTotal,
                ];
            }

            $purchase = Purchase::create([
                'purchase_no' => 'PUR-'.strtoupper(Str::random(6)),
                'supplier_id' => $validated['supplier_id'],
                'warehouse_id' => $validated['warehouse_id'],
                'date' => $validated['date'],
                'total_cost' => $totalCost,
            ]);

            $supplier = Supplier::find($validated['supplier_id']);
            if ($supplier) {
                $supplier->increment('total_payable', $totalCost);
            }

            foreach ($parsedItems as $item) {
                $productId = $item['product_id'];
                $variantId = $item['product_variant_id'];
                $qty = $item['qty'];
                $unitCost = $item['unit_cost'];
                $lineTotal = $item['line_total'];

                PurchaseItem::create([
                    'purchase_id' => $purchase->id,
                    'product_id' => $productId,
                    'product_variant_id' => $variantId,
                    'qty' => $qty,
                    'unit_cost' => $unitCost,
                    'total_cost' => $lineTotal,
                ]);

                $batchPrefix = $variantId 
                    ? ('B-PUR-'.$purchase->id.'-V'.$variantId.'-')
                    : ('B-PUR-'.$purchase->id.'-P'.$productId.'-');

                $batch = Batch::create([
                    'batch_no' => $batchPrefix . strtoupper(Str::random(4)),
                    'product_id' => $productId,
                    'product_variant_id' => $variantId,
                    'warehouse_id' => $validated['warehouse_id'],
                    'purchase_id' => $purchase->id,
                    'qty_in' => $qty,
                    'qty_out' => 0,
                    'remaining_qty' => $qty,
                    'cost_per_unit' => $unitCost,
                    'expiry_date' => null,
                ]);

                InventoryTransaction::create([
                    'warehouse_id' => $validated['warehouse_id'],
                    'product_id' => $productId,
                    'product_variant_id' => $variantId,
                    'batch_id' => $batch->id,
                    'type' => 'purchase',
                    'qty_in' => $qty,
                    'qty_out' => 0,
                    'cost' => $lineTotal,
                    'reference_type' => Purchase::class,
                    'reference_id' => $purchase->id,
                    'date' => $validated['date'],
                    'created_by' => $request->user()->id ?? 1,
                ]);
            }

            $rawInventoryAcc = ChartOfAccount::firstOrCreate(['name' => 'Inventory (Raw)', 'type' => 'asset'], ['parent_id' => null]);
            $finInventoryAcc = ChartOfAccount::firstOrCreate(['name' => 'Inventory (Finished)', 'type' => 'asset'], ['parent_id' => null]);
            $payableAcc = ChartOfAccount::firstOrCreate(['name' => 'Accounts Payable', 'type' => 'liability'], ['parent_id' => null]);

            $journal = Journal::create([
                'journal_no' => 'JNL-'.strtoupper(Str::random(6)),
                'date' => $validated['date'],
                'reference_type' => Purchase::class,
                'reference_id' => $purchase->id,
                'notes' => 'Purchase Shipment '.$purchase->purchase_no,
                'created_by' => $request->user()->id ?? 1,
            ]);

            if ($rawCostTotal > 0) {
                JournalEntry::create(['journal_id' => $journal->id, 'account_id' => $rawInventoryAcc->id, 'type' => 'debit', 'amount' => $rawCostTotal]);
            }
            if ($finCostTotal > 0) {
                JournalEntry::create(['journal_id' => $journal->id, 'account_id' => $finInventoryAcc->id, 'type' => 'debit', 'amount' => $finCostTotal]);
            }
            if ($totalCost > 0) {
                JournalEntry::create(['journal_id' => $journal->id, 'account_id' => $payableAcc->id, 'type' => 'credit', 'amount' => $totalCost]);
            }

            DB::commit();

            return response()->json(['message' => 'Purchase confirmed successfully', 'purchase' => $purchase], 201);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    public function updatePurchase(Request $request, $id)
    {
        $validated = $request->validate([
            'supplier_id' => 'required|exists:suppliers,id',
            'warehouse_id' => 'required|exists:warehouses,id',
            'date' => 'required|date',
            'items' => 'required|array|min:1',
            'items.*.item_id' => 'nullable|string',
            'items.*.product_id' => 'nullable|exists:products,id',
            'items.*.product_variant_id' => 'nullable|exists:product_variants,id',
            'items.*.qty' => 'required|numeric|min:0.001',
            'items.*.unit_cost' => 'nullable|numeric|min:0',
        ]);

        try {
            DB::beginTransaction();

            $purchase = Purchase::findOrFail($id);

            // Reverse existing purchase
            $batches = Batch::where('purchase_id', $purchase->id)->get();
            foreach ($batches as $batch) {
                if ($batch->qty_out > 0) {
                    throw new \Exception("Cannot update purchase. Stock from batch {$batch->batch_no} has already been consumed.");
                }
            }

            $supplier = Supplier::find($purchase->supplier_id);
            if ($supplier) {
                $supplier->decrement('total_payable', $purchase->total_cost);
            }

            Batch::where('purchase_id', $purchase->id)->delete();
            InventoryTransaction::where('reference_type', Purchase::class)->where('reference_id', $purchase->id)->delete();

            // Delete old journals
            $journals = Journal::where('reference_type', Purchase::class)->where('reference_id', $purchase->id)->get();
            foreach ($journals as $journal) {
                JournalEntry::where('journal_id', $journal->id)->delete();
                $journal->delete();
            }

            $purchase->items()->delete();

            // Apply new data
            $parsedItems = [];
            $totalCost = 0;
            $rawCostTotal = 0;
            $finCostTotal = 0;

            foreach ($validated['items'] as $item) {
                $unitCost = (isset($item['unit_cost']) && $item['unit_cost'] !== '') ? (float) $item['unit_cost'] : 0;
                $qty = (float) $item['qty'];
                $lineTotal = $qty * $unitCost;
                $totalCost += $lineTotal;

                $productId = $item['product_id'] ?? null;
                $variantId = $item['product_variant_id'] ?? null;

                if (!empty($item['item_id'])) {
                    $parts = explode('_', $item['item_id']);
                    if (count($parts) === 2) {
                        if ($parts[0] === 'variant') {
                            $variantId = $parts[1];
                        } elseif ($parts[0] === 'product') {
                            $productId = $parts[1];
                        }
                    }
                }

                $product = null;
                if ($variantId) {
                    $variant = ProductVariant::with('product')->find($variantId);
                    if ($variant) {
                        $productId = $variant->product_id;
                        $product = $variant->product;
                    }
                } elseif ($productId) {
                    $product = Product::find($productId);
                }

                if (!$productId) {
                    throw new \Exception('Invalid product or variant specified in purchase items.');
                }

                if ($variantId || ($product && $product->type === 'finished')) {
                    $finCostTotal += $lineTotal;
                } else {
                    $rawCostTotal += $lineTotal;
                }

                $parsedItems[] = [
                    'product_id' => $productId,
                    'product_variant_id' => $variantId,
                    'qty' => $qty,
                    'unit_cost' => $unitCost,
                    'line_total' => $lineTotal,
                ];
            }

            $purchase->update([
                'supplier_id' => $validated['supplier_id'],
                'warehouse_id' => $validated['warehouse_id'],
                'date' => $validated['date'],
                'total_cost' => $totalCost,
            ]);

            $supplier = Supplier::find($validated['supplier_id']);
            if ($supplier) {
                $supplier->increment('total_payable', $totalCost);
            }

            foreach ($parsedItems as $item) {
                $productId = $item['product_id'];
                $variantId = $item['product_variant_id'];
                $qty = $item['qty'];
                $unitCost = $item['unit_cost'];
                $lineTotal = $item['line_total'];

                PurchaseItem::create([
                    'purchase_id' => $purchase->id,
                    'product_id' => $productId,
                    'product_variant_id' => $variantId,
                    'qty' => $qty,
                    'unit_cost' => $unitCost,
                    'total_cost' => $lineTotal,
                ]);

                $batchPrefix = $variantId 
                    ? ('B-PUR-'.$purchase->id.'-V'.$variantId.'-')
                    : ('B-PUR-'.$purchase->id.'-P'.$productId.'-');

                $batch = Batch::create([
                    'batch_no' => $batchPrefix . strtoupper(Str::random(4)),
                    'product_id' => $productId,
                    'product_variant_id' => $variantId,
                    'warehouse_id' => $validated['warehouse_id'],
                    'purchase_id' => $purchase->id,
                    'qty_in' => $qty,
                    'qty_out' => 0,
                    'remaining_qty' => $qty,
                    'cost_per_unit' => $unitCost,
                    'expiry_date' => null,
                ]);

                InventoryTransaction::create([
                    'warehouse_id' => $validated['warehouse_id'],
                    'product_id' => $productId,
                    'product_variant_id' => $variantId,
                    'batch_id' => $batch->id,
                    'type' => 'purchase',
                    'qty_in' => $qty,
                    'qty_out' => 0,
                    'cost' => $lineTotal,
                    'reference_type' => Purchase::class,
                    'reference_id' => $purchase->id,
                    'date' => $validated['date'],
                    'created_by' => $request->user()->id ?? 1,
                ]);
            }

            // Create Accounting Entry
            $rawInventoryAcc = ChartOfAccount::firstOrCreate(['name' => 'Inventory (Raw)', 'type' => 'asset'], ['parent_id' => null]);
            $finInventoryAcc = ChartOfAccount::firstOrCreate(['name' => 'Inventory (Finished)', 'type' => 'asset'], ['parent_id' => null]);
            $payableAcc = ChartOfAccount::firstOrCreate(['name' => 'Accounts Payable', 'type' => 'liability'], ['parent_id' => null]);

            $journal = Journal::create([
                'journal_no' => 'JNL-'.strtoupper(Str::random(6)),
                'date' => $validated['date'],
                'reference_type' => Purchase::class,
                'reference_id' => $purchase->id,
                'notes' => 'Purchase Shipment '.$purchase->purchase_no.' (Updated)',
                'created_by' => $request->user()->id ?? 1,
            ]);

            if ($rawCostTotal > 0) {
                JournalEntry::create(['journal_id' => $journal->id, 'account_id' => $rawInventoryAcc->id, 'type' => 'debit', 'amount' => $rawCostTotal]);
            }
            if ($finCostTotal > 0) {
                JournalEntry::create(['journal_id' => $journal->id, 'account_id' => $finInventoryAcc->id, 'type' => 'debit', 'amount' => $finCostTotal]);
            }
            if ($totalCost > 0) {
                JournalEntry::create(['journal_id' => $journal->id, 'account_id' => $payableAcc->id, 'type' => 'credit', 'amount' => $totalCost]);
            }

            DB::commit();

            return response()->json(['message' => 'Purchase updated successfully', 'purchase' => $purchase], 200);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    public function destroyPurchase($id)
    {
        try {
            DB::beginTransaction();
            $purchase = Purchase::findOrFail($id);

            $batches = Batch::where('purchase_id', $purchase->id)->get();
            foreach ($batches as $batch) {
                if ($batch->qty_out > 0) {
                    throw new \Exception("Cannot reverse purchase. Stock from batch {$batch->batch_no} has already been consumed.");
                }
            }

            $supplier = Supplier::find($purchase->supplier_id);
            if ($supplier) {
                $supplier->decrement('total_payable', $purchase->total_cost);
            }

            Batch::where('purchase_id', $purchase->id)->delete();
            InventoryTransaction::where('reference_type', Purchase::class)->where('reference_id', $purchase->id)->delete();

            $journals = Journal::where('reference_type', Purchase::class)->where('reference_id', $purchase->id)->get();
            foreach ($journals as $journal) {
                JournalEntry::where('journal_id', $journal->id)->delete();
                $journal->delete();
            }

            PurchaseItem::where('purchase_id', $purchase->id)->delete();
            $purchase->delete();

            DB::commit();

            return response()->json(['message' => 'Purchase deleted successfully']);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    /**
     * Get all expenses.
     */

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
            $data['code'] = 'W-'.strtoupper(Str::random(4));
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
            DB::beginTransaction();

            if ($duePayment > 0) {
                $customer->decrement('total_due', $duePayment);
            }
            if ($newAdvance > 0) {
                $customer->increment('wallet_balance', $newAdvance);
            }

            $cashAcc = ChartOfAccount::firstOrCreate(['name' => 'Cash', 'type' => 'asset']);
            $arAcc = ChartOfAccount::firstOrCreate(['name' => 'Accounts Receivable', 'type' => 'asset']);

            $journal = Journal::create([
                'journal_no' => 'RCV-'.strtoupper(Str::random(6)),
                'date' => $validated['date'],
                'reference_type' => Customer::class,
                'reference_id' => $customer->id,
                'notes' => 'API Payment received from Customer: '.$customer->name.($validated['reference'] ? ' (Ref: '.$validated['reference'].')' : ''),
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

                $unpaidSales = Sale::where('customer_id', $customer->id)->where('due_amount', '>', 0)->orderBy('date', 'asc')->get();
                $remainingPayment = $duePayment;
                foreach ($unpaidSales as $sale) {
                    if ($remainingPayment <= 0) {
                        break;
                    }
                    $payThisSale = min($sale->due_amount, $remainingPayment);
                    $sale->paid_amount += $payThisSale;
                    $sale->due_amount -= $payThisSale;
                    $sale->payment_status = $sale->due_amount > 0 ? 'partial' : 'paid';
                    $sale->save();
                    SalePayment::create([
                        'sale_id' => $sale->id,
                        'amount' => $payThisSale,
                        'method' => 'cash',
                        'date' => $validated['date'],
                        'reference' => 'API Settlement '.($validated['reference'] ?? ''),
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

            DB::commit();

            return response()->json(['message' => 'Customer payment recorded successfully']);
        } catch (\Exception $e) {
            DB::rollBack();

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
            'payment_method_id' => 'nullable|exists:chart_of_accounts,id',
            'payment_method' => 'nullable',
        ]);

        $supplier = Supplier::findOrFail($validated['supplier_id']);

        try {
            DB::beginTransaction();

            $supplier->decrement('total_payable', $validated['amount']);

            $paymentMethodId = $validated['payment_method_id'] ?? $validated['payment_method'] ?? null;
            $cashAcc = $paymentMethodId
                ? ChartOfAccount::find($paymentMethodId)
                : ChartOfAccount::firstOrCreate(['name' => 'Cash', 'type' => 'asset']);

            if (! $cashAcc) {
                $cashAcc = ChartOfAccount::firstOrCreate(['name' => 'Cash', 'type' => 'asset']);
            }

            $apAcc = ChartOfAccount::firstOrCreate(['name' => 'Accounts Payable', 'type' => 'liability']);

            $journal = Journal::create([
                'journal_no' => 'PAY-'.strtoupper(Str::random(6)),
                'date' => $validated['date'],
                'reference_type' => Supplier::class,
                'reference_id' => $supplier->id,
                'notes' => 'API Payment made to Supplier: '.$supplier->name.($validated['reference'] ? ' (Ref: '.$validated['reference'].')' : ''),
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

            DB::commit();

            return response()->json(['message' => 'Supplier payment recorded successfully']);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function updatePayment(Request $request, $id)
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'date' => 'required|date',
            'reference' => 'nullable|string',
            'payment_method_id' => 'nullable|exists:chart_of_accounts,id',
        ]);

        $journal = Journal::with('entries.account')->findOrFail($id);

        try {
            DB::beginTransaction();

            $newAmount = (float) $validated['amount'];
            $newDate = $validated['date'];
            $newRef = $validated['reference'] ?? '';
            $paymentMethodId = $validated['payment_method_id'] ?? null;

            if ($journal->reference_type === Supplier::class) {
                $supplier = Supplier::findOrFail($journal->reference_id);
                $apEntry = $journal->entries->first(function ($e) {
                    return $e->type === 'debit';
                });
                $oldAmount = $apEntry ? (float) $apEntry->amount : 0.0;
                $diff = $newAmount - $oldAmount;

                // Adjust supplier payable balance
                $supplier->decrement('total_payable', $diff);

                // Update journal date & notes
                $journal->update([
                    'date' => $newDate,
                    'notes' => 'API Payment made to Supplier: '.$supplier->name.($newRef ? ' (Ref: '.$newRef.')' : ''),
                ]);

                // Update Debit (Accounts Payable)
                if ($apEntry) {
                    $apEntry->update(['amount' => $newAmount]);
                }

                // Update Credit (Cash/Bank account)
                $creditEntry = $journal->entries->first(function ($e) {
                    return $e->type === 'credit';
                });

                if ($creditEntry) {
                    $creditData = ['amount' => $newAmount];
                    if ($paymentMethodId) {
                        $creditData['account_id'] = $paymentMethodId;
                    }
                    $creditEntry->update($creditData);
                }
            } else {
                $journal->update(['date' => $newDate, 'notes' => $newRef]);
                foreach ($journal->entries as $entry) {
                    $entry->update(['amount' => $newAmount]);
                }
            }

            DB::commit();

            return response()->json(['message' => 'Payment updated successfully']);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function destroyPayment($id)
    {
        $journal = Journal::with('entries')->findOrFail($id);

        try {
            DB::beginTransaction();

            if ($journal->reference_type === Supplier::class) {
                $supplier = Supplier::find($journal->reference_id);
                $apEntry = $journal->entries->first(function ($e) {
                    return $e->type === 'debit';
                });
                $oldAmount = $apEntry ? (float) $apEntry->amount : 0.0;

                if ($supplier && $oldAmount > 0) {
                    $supplier->increment('total_payable', $oldAmount);
                }
            }

            JournalEntry::where('journal_id', $journal->id)->delete();
            $journal->delete();

            DB::commit();

            return response()->json(['message' => 'Payment deleted successfully']);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Get all stock transfers.
     */
    public function transferFormData()
    {
        $warehouses = Warehouse::all();
        $variants = ProductVariant::with(['product.unit', 'unit'])->get();
        $stocks = WarehouseStock::all();

        return response()->json(['warehouses' => $warehouses, 'variants' => $variants, 'stocks' => $stocks]);
    }

    public function stockTransfers(Request $request)
    {
        $query = StockTransfer::with(['fromWarehouse', 'toWarehouse', 'creator', 'items.productVariant.unit', 'items.productVariant.product.unit'])->orderBy('id', 'desc');

        if ($request->filled('transfer_no')) {
            $query->where('transfer_no', 'like', '%'.$request->transfer_no.'%');
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $transfers = $query->orderBy('id', 'desc')->paginate(20);

        return response()->json(['stock_transfers' => $transfers]);
    }

    public function showStockTransfer($id)
    {
        $transfer = StockTransfer::with(['fromWarehouse', 'toWarehouse', 'creator', 'items.productVariant.product.unit', 'items.productVariant.unit'])->findOrFail($id);

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
            DB::beginTransaction();

            $transfer = StockTransfer::create([
                'transfer_no' => 'TRF-'.strtoupper(Str::random(6)),
                'from_warehouse_id' => $validated['from_warehouse_id'],
                'to_warehouse_id' => $validated['to_warehouse_id'],
                'status' => 'draft',
                'created_by' => $request->user()->id ?? 1,
            ]);

            foreach ($validated['items'] as $item) {
                StockTransferItem::create([
                    'stock_transfer_id' => $transfer->id,
                    'product_variant_id' => $item['product_variant_id'],
                    'batch_id' => null,
                    'qty' => $item['qty'],
                ]);
            }

            DB::commit();

            return response()->json(['message' => 'Transfer Draft created successfully', 'stock_transfer' => $transfer], 201);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    public function updateTransferStatus(Request $request, $id)
    {
        $transfer = StockTransfer::with('items')->findOrFail($id);
        $action = $request->input('action');

        try {
            DB::beginTransaction();

            if ($action === 'send' && $transfer->status === 'draft') {
                foreach ($transfer->items as $item) {
                    $batches = Batch::where('product_variant_id', $item->product_variant_id)
                        ->where('warehouse_id', $transfer->from_warehouse_id)
                        ->where('remaining_qty', '>', 0)
                        ->orderBy('id', 'asc')
                        ->lockForUpdate()
                        ->get();

                    $remainingToConsume = $item->qty;

                    foreach ($batches as $batch) {
                        if ($remainingToConsume <= 0) {
                            break;
                        }
                        $takeQty = min($batch->remaining_qty, $remainingToConsume);

                        $batch->qty_out += $takeQty;
                        $batch->remaining_qty -= $takeQty;
                        $batch->save();

                        InventoryTransaction::create([
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
            } elseif ($action === 'receive' && $transfer->status === 'sent') {
                foreach ($transfer->items as $item) {
                    $variant = ProductVariant::find($item->product_variant_id);
                    $latestBatch = Batch::where('product_variant_id', $variant->id)->latest()->first();
                    $costPerUnit = $latestBatch ? $latestBatch->cost_per_unit : 0;

                    $newBatch = Batch::create([
                        'batch_no' => 'B-TRF-'.$transfer->id.'-'.$variant->id.'-'.strtoupper(Str::random(4)),
                        'product_id' => $variant->product_id,
                        'product_variant_id' => $variant->id,
                        'warehouse_id' => $transfer->to_warehouse_id,
                        'qty_in' => $item->qty,
                        'qty_out' => 0,
                        'remaining_qty' => $item->qty,
                        'cost_per_unit' => $costPerUnit,
                    ]);

                    InventoryTransaction::create([
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
                throw new \Exception('Invalid action or status mismatch.');
            }

            DB::commit();

            return response()->json(['message' => 'Transfer status updated to '.ucfirst($transfer->status)]);

        } catch (\Exception $e) {
            DB::rollBack();

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
            DB::beginTransaction();

            $transfer->update([
                'from_warehouse_id' => $validated['from_warehouse_id'],
                'to_warehouse_id' => $validated['to_warehouse_id'],
            ]);

            StockTransferItem::where('stock_transfer_id', $transfer->id)->delete();

            foreach ($validated['items'] as $item) {
                StockTransferItem::create([
                    'stock_transfer_id' => $transfer->id,
                    'product_variant_id' => $item['product_variant_id'],
                    'batch_id' => null,
                    'qty' => $item['qty'],
                ]);
            }

            DB::commit();

            return response()->json(['message' => 'Transfer updated successfully', 'stock_transfer' => $transfer], 200);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    public function destroyStockTransfer($id)
    {
        $transfer = StockTransfer::find($id);
        if ($transfer) {
            $transfer->items()->delete();
            $transfer->delete();
        }

        return response()->json(['message' => 'Transfer deleted']);
    }

    /**
     * Get all stock adjustments.
     */
    public function adjustmentFormData()
    {
        $warehouses = Warehouse::all();
        $products = Product::with('unit')->get();
        $variants = ProductVariant::with(['unit', 'product.unit'])->get();
        $batches = Batch::with(['product.unit', 'productVariant.unit'])->where('remaining_qty', '>', 0)->get();

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

        $batch = Batch::findOrFail($validated['batch_id']);

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
            DB::beginTransaction();

            if ($action === 'approve') {
                $batch = $adjustment->batch;

                if ($adjustment->type === 'remove' && $batch->remaining_qty < $adjustment->qty) {
                    throw new \Exception('Batch remaining quantity is less than requested removal.');
                }

                if ($adjustment->type === 'add') {
                    $batch->qty_in += $adjustment->qty;
                    $batch->remaining_qty += $adjustment->qty;
                } else {
                    $batch->qty_out += $adjustment->qty;
                    $batch->remaining_qty -= $adjustment->qty;
                }
                $batch->save();

                InventoryTransaction::create([
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
                throw new \Exception('Invalid action.');
            }

            DB::commit();

            return response()->json(['message' => 'Adjustment '.ucfirst($action).'d']);

        } catch (\Exception $e) {
            DB::rollBack();

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

        $batch = Batch::findOrFail($validated['batch_id']);

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
        $warehouses = Warehouse::all();
        $inputProducts = Product::with('unit')->whereIn('type', ['raw', 'finished'])->get();
        $variants = ProductVariant::with(['product.unit', 'unit'])->get();

        return response()->json([
            'warehouses' => $warehouses,
            'input_products' => $inputProducts,
            'variants' => $variants,
        ]);
    }

    public function repackagingStockCheck(Request $request)
    {
        $warehouseId = $request->get('warehouse_id');
        $item = $request->get('item'); // product_X or variant_Y

        if (!$warehouseId || !$item) {
            return response()->json(['stock' => 0]);
        }

        $parts = explode('_', $item);
        if (count($parts) !== 2) {
            return response()->json(['stock' => 0]);
        }

        $type = $parts[0];
        $id = $parts[1];

        $query = Batch::where('warehouse_id', $warehouseId)
            ->where('remaining_qty', '>', 0);

        if ($type === 'product') {
            $query->where('product_id', $id)->whereNull('product_variant_id');
        } elseif ($type === 'variant') {
            $query->where('product_variant_id', $id);
        } else {
            return response()->json(['stock' => 0]);
        }

        $totalStock = (float) $query->sum('remaining_qty');

        return response()->json(['stock' => $totalStock]);
    }

    public function repackaging(Request $request)
    {
        $query = RepackagingOrder::with(['warehouse', 'creator', 'inputs.product', 'outputs.productVariant'])->orderBy('id', 'desc');

        if ($request->filled('ref_no')) {
            $query->where('ref_no', 'like', '%'.$request->ref_no.'%');
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
            'input_item' => 'required|string',
            'input_qty' => 'required|numeric|min:0.001',
            'outputs' => 'required|array',
            'outputs.*.variant_id' => 'required|exists:product_variants,id',
            'outputs.*.qty' => 'required|numeric|min:0.001',
            'expenses' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        $expenses = $validated['expenses'] ?? 0;

        try {
            DB::beginTransaction();

            $warehouseId = $validated['warehouse_id'];

            $inputParts = explode('_', $validated['input_item']);
            $inputType = $inputParts[0];
            $inputId = $inputParts[1];

            $inputProductId = null;
            $inputVariantId = null;
            $inputUnitQty = 1;

            if ($inputType === 'product') {
                $inputProductId = $inputId;
            } else {
                $inputVariantId = $inputId;
                $variant = ProductVariant::find($inputVariantId);
                $inputProductId = $variant->product_id;
                $inputUnitQty = $variant->getBaseQuantity();
            }

            $inputQty = $validated['input_qty']; // packages or kg
            $inputRawWeight = $inputQty * $inputUnitQty;

            $batches = Batch::where('product_id', $inputProductId)
                ->where('warehouse_id', $warehouseId)
                ->when($inputVariantId, function ($q) use ($inputVariantId) {
                    return $q->where('product_variant_id', $inputVariantId);
                }, function ($q) {
                    return $q->whereNull('product_variant_id');
                })
                ->where('remaining_qty', '>', 0)
                ->orderBy('id', 'asc')
                ->lockForUpdate()
                ->get();

            $totalRawCost = 0;
            $remainingToConsume = $inputQty;
            $consumedBatches = [];

            foreach ($batches as $batch) {
                if ($remainingToConsume <= 0) {
                    break;
                }

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
                    'cost' => $costForThisTake,
                ];
            }

            if (round($remainingToConsume, 4) > 0) {
                throw new \Exception('Insufficient raw stock in the selected warehouse. Shortfall: '.$remainingToConsume);
            }

            // Pre-process Outputs and Calculate Total Weight
            $outputItemsData = [];
            $totalOutputWeight = 0;

            foreach ($validated['outputs'] as $outItemReq) {
                $variantId = $outItemReq['variant_id'];
                $qty = $outItemReq['qty'];

                $variant = ProductVariant::find($variantId);
                $productId = $variant->product_id;
                $unitQty = $variant->getBaseQuantity();

                $weight = $qty * $unitQty;
                $totalOutputWeight += $weight;

                $outputItemsData[] = [
                    'product_id' => $productId,
                    'variant_id' => $variantId,
                    'qty' => $qty,
                    'weight' => $weight,
                    'unit_qty' => $unitQty,
                ];
            }

            $totalCost = $totalRawCost + $expenses;

            $order = RepackagingOrder::create([
                'ref_no' => 'RPK-'.strtoupper(Str::random(6)),
                'warehouse_id' => $warehouseId,
                'date' => $validated['date'],
                'created_by' => $request->user()->id ?? 1,
                'notes' => $validated['notes'] ?? '',
            ]);

            foreach ($consumedBatches as $consumed) {
                RepackagingInput::create([
                    'repackaging_order_id' => $order->id,
                    'batch_id' => $consumed['batch_id'],
                    'product_id' => $inputProductId,
                    'product_variant_id' => $inputVariantId,
                    'qty_used' => $consumed['qty_used'],
                ]);

                InventoryTransaction::create([
                    'warehouse_id' => $warehouseId,
                    'product_id' => $inputProductId,
                    'product_variant_id' => $inputVariantId,
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

            // Create Outputs
            foreach ($outputItemsData as $outItem) {
                $proportion = $totalOutputWeight > 0 ? ($outItem['weight'] / $totalOutputWeight) : 0;
                $variantTotalCost = $totalCost * $proportion;
                $variantUnitCost = $outItem['qty'] > 0 ? ($variantTotalCost / $outItem['qty']) : 0;

                RepackagingOutput::create([
                    'repackaging_order_id' => $order->id,
                    'product_id' => $outItem['product_id'],
                    'product_variant_id' => $outItem['variant_id'],
                    'warehouse_id' => $warehouseId,
                    'qty_produced' => $outItem['qty'],
                    'unit_cost' => $variantUnitCost,
                    'total_cost' => $variantTotalCost,
                ]);

                $outputBatch = Batch::create([
                    'batch_no' => 'B-'.$order->id.'-'.$outItem['product_id'].'-FIN-'.strtoupper(Str::random(4)),
                    'product_id' => $outItem['product_id'],
                    'product_variant_id' => $outItem['variant_id'],
                    'warehouse_id' => $warehouseId,
                    'purchase_id' => null,
                    'qty_in' => $outItem['qty'],
                    'qty_out' => 0,
                    'remaining_qty' => $outItem['qty'],
                    'cost_per_unit' => $variantUnitCost,
                    'expiry_date' => null,
                ]);

                InventoryTransaction::create([
                    'warehouse_id' => $warehouseId,
                    'product_id' => $outItem['product_id'],
                    'product_variant_id' => $outItem['variant_id'],
                    'batch_id' => $outputBatch->id,
                    'type' => 'repack_output',
                    'qty_in' => $outItem['qty'],
                    'qty_out' => 0,
                    'cost' => $variantTotalCost,
                    'reference_type' => RepackagingOrder::class,
                    'reference_id' => $order->id,
                    'date' => $validated['date'],
                    'created_by' => $request->user()->id ?? 1,
                ]);
            }

            if ($inputRawWeight != $totalOutputWeight) {
                $diff = $totalOutputWeight - $inputRawWeight;
                RepackagingAdjustment::create([
                    'repackaging_order_id' => $order->id,
                    'type' => $diff > 0 ? 'gain' : 'loss',
                    'qty' => abs($diff),
                    'reason' => 'Yield mismatch during repackaging',
                ]);
            }

            $inventoryRawAcc = ChartOfAccount::firstOrCreate(['name' => 'Inventory (Raw)', 'type' => 'asset']);
            $inventoryFinAcc = ChartOfAccount::firstOrCreate(['name' => 'Inventory (Finished)', 'type' => 'asset']);

            $journal = Journal::create([
                'journal_no' => 'JNL-'.strtoupper(Str::random(6)),
                'date' => $validated['date'],
                'reference_type' => RepackagingOrder::class,
                'reference_id' => $order->id,
                'notes' => 'API Repackaging '.$order->ref_no,
                'created_by' => $request->user()->id ?? 1,
            ]);

            JournalEntry::create([
                'journal_id' => $journal->id,
                'account_id' => $inventoryFinAcc->id,
                'type' => 'debit',
                'amount' => $totalCost,
            ]);

            JournalEntry::create([
                'journal_id' => $journal->id,
                'account_id' => $inventoryRawAcc->id,
                'type' => 'credit',
                'amount' => $totalRawCost,
            ]);

            if ($expenses > 0) {
                $cashAcc = ChartOfAccount::firstOrCreate(['name' => 'Cash', 'type' => 'asset']);
                JournalEntry::create([
                    'journal_id' => $journal->id,
                    'account_id' => $cashAcc->id,
                    'type' => 'credit',
                    'amount' => $expenses,
                ]);
            }

            DB::commit();

            return response()->json(['message' => 'Repackaging created successfully', 'repackaging_order' => $order], 201);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    private function reverseRepackaging(RepackagingOrder $repackaging)
    {
        $repackaging->load(['inputs.batch', 'outputs']);

        $outputBatches = Batch::where('batch_no', 'like', 'B-'.$repackaging->id.'-%FIN-%')->get();
        foreach ($outputBatches as $batch) {
            if ($batch->qty_out > 0) {
                throw new \Exception('Cannot update repackaging because the finished stock has already been consumed.');
            }
        }

        InventoryTransaction::where('reference_type', RepackagingOrder::class)->where('reference_id', $repackaging->id)->delete();

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

        $journal = Journal::where('reference_type', RepackagingOrder::class)->where('reference_id', $repackaging->id)->first();
        if ($journal) {
            JournalEntry::where('journal_id', $journal->id)->delete();
            $journal->delete();
        }

        RepackagingInput::where('repackaging_order_id', $repackaging->id)->delete();
        RepackagingOutput::where('repackaging_order_id', $repackaging->id)->delete();
        RepackagingAdjustment::where('repackaging_order_id', $repackaging->id)->delete();
    }

    public function updateRepackaging(Request $request, $id)
    {
        $validated = $request->validate([
            'warehouse_id' => 'required|exists:warehouses,id',
            'date' => 'required|date',
            'input_product_id' => 'required|exists:products,id',
            'input_qty' => 'required|numeric|min:0.001',
            'outputs' => 'required|array',
            'outputs.*.variant_id' => 'required|exists:product_variants,id',
            'outputs.*.qty' => 'required|numeric|min:0.001',
            'expenses' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        $expenses = $validated['expenses'] ?? 0;

        try {
            DB::beginTransaction();

            $order = RepackagingOrder::findOrFail($id);
            $this->reverseRepackaging($order);

            $warehouseId = $validated['warehouse_id'];
            $inputProductId = $validated['input_product_id'];
            $inputQty = $validated['input_qty'];

            $batches = Batch::where('product_id', $inputProductId)
                ->where('warehouse_id', $warehouseId)
                ->where('remaining_qty', '>', 0)
                ->orderBy('id', 'asc')
                ->lockForUpdate()
                ->get();

            $totalRawCost = 0;
            $remainingToConsume = $inputQty;
            $consumedBatches = [];

            foreach ($batches as $batch) {
                if ($remainingToConsume <= 0) {
                    break;
                }

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
                    'cost' => $costForThisTake,
                ];
            }

            if (round($remainingToConsume, 4) > 0) {
                throw new \Exception('Insufficient raw stock in the selected warehouse.');
            }

            // Pre-process Outputs and Calculate Total Weight
            $outputItemsData = [];
            $totalOutputWeight = 0;

            foreach ($validated['outputs'] as $outItemReq) {
                $variantId = $outItemReq['variant_id'];
                $qty = $outItemReq['qty'];

                $variant = ProductVariant::find($variantId);
                $productId = $variant->product_id;
                $unitQty = $variant->getBaseQuantity();

                $weight = $qty * $unitQty;
                $totalOutputWeight += $weight;

                $outputItemsData[] = [
                    'product_id' => $productId,
                    'variant_id' => $variantId,
                    'qty' => $qty,
                    'weight' => $weight,
                    'unit_qty' => $unitQty,
                ];
            }

            $totalCost = $totalRawCost + $expenses;

            $order->update([
                'warehouse_id' => $warehouseId,
                'date' => $validated['date'],
                'notes' => $validated['notes'] ?? '',
            ]);

            foreach ($consumedBatches as $consumed) {
                RepackagingInput::create([
                    'repackaging_order_id' => $order->id,
                    'batch_id' => $consumed['batch_id'],
                    'product_id' => $inputProductId,
                    'qty_used' => $consumed['qty_used'],
                ]);

                InventoryTransaction::create([
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

            // Create Outputs
            foreach ($outputItemsData as $outItem) {
                $proportion = $totalOutputWeight > 0 ? ($outItem['weight'] / $totalOutputWeight) : 0;
                $variantTotalCost = $totalCost * $proportion;
                $variantUnitCost = $outItem['qty'] > 0 ? ($variantTotalCost / $outItem['qty']) : 0;

                RepackagingOutput::create([
                    'repackaging_order_id' => $order->id,
                    'product_id' => $outItem['product_id'],
                    'product_variant_id' => $outItem['variant_id'],
                    'warehouse_id' => $warehouseId,
                    'qty_produced' => $outItem['qty'],
                    'unit_cost' => $variantUnitCost,
                    'total_cost' => $variantTotalCost,
                ]);

                $outputBatch = Batch::create([
                    'batch_no' => 'B-'.$order->id.'-'.$outItem['product_id'].'-FIN-'.strtoupper(Str::random(4)),
                    'product_id' => $outItem['product_id'],
                    'product_variant_id' => $outItem['variant_id'],
                    'warehouse_id' => $warehouseId,
                    'purchase_id' => null,
                    'qty_in' => $outItem['qty'],
                    'qty_out' => 0,
                    'remaining_qty' => $outItem['qty'],
                    'cost_per_unit' => $variantUnitCost,
                    'expiry_date' => null,
                ]);

                InventoryTransaction::create([
                    'warehouse_id' => $warehouseId,
                    'product_id' => $outItem['product_id'],
                    'product_variant_id' => $outItem['variant_id'],
                    'batch_id' => $outputBatch->id,
                    'type' => 'repack_output',
                    'qty_in' => $outItem['qty'],
                    'qty_out' => 0,
                    'cost' => $variantTotalCost,
                    'reference_type' => RepackagingOrder::class,
                    'reference_id' => $order->id,
                    'date' => $validated['date'],
                    'created_by' => $request->user()->id ?? 1,
                ]);
            }

            if ($inputQty != $totalOutputWeight) {
                $diff = $totalOutputWeight - $inputQty;
                RepackagingAdjustment::create([
                    'repackaging_order_id' => $order->id,
                    'type' => $diff > 0 ? 'gain' : 'loss',
                    'qty' => abs($diff),
                    'reason' => 'Yield mismatch during repackaging',
                ]);
            }

            $inventoryRawAcc = ChartOfAccount::firstOrCreate(['name' => 'Inventory (Raw)', 'type' => 'asset']);
            $inventoryFinAcc = ChartOfAccount::firstOrCreate(['name' => 'Inventory (Finished)', 'type' => 'asset']);

            $journal = Journal::create([
                'journal_no' => 'JNL-'.strtoupper(Str::random(6)),
                'date' => $validated['date'],
                'reference_type' => RepackagingOrder::class,
                'reference_id' => $order->id,
                'notes' => 'API Repackaging '.$order->ref_no.' (Updated)',
                'created_by' => $request->user()->id ?? 1,
            ]);

            JournalEntry::create([
                'journal_id' => $journal->id,
                'account_id' => $inventoryFinAcc->id,
                'type' => 'debit',
                'amount' => $totalCost,
            ]);

            JournalEntry::create([
                'journal_id' => $journal->id,
                'account_id' => $inventoryRawAcc->id,
                'type' => 'credit',
                'amount' => $totalRawCost,
            ]);

            if ($expenses > 0) {
                $cashAcc = ChartOfAccount::firstOrCreate(['name' => 'Cash', 'type' => 'asset']);
                JournalEntry::create([
                    'journal_id' => $journal->id,
                    'account_id' => $cashAcc->id,
                    'type' => 'credit',
                    'amount' => $expenses,
                ]);
            }

            DB::commit();

            return response()->json(['message' => 'Repackaging updated successfully', 'repackaging_order' => $order], 200);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    public function destroyRepackaging($id)
    {
        try {
            DB::beginTransaction();
            $repackaging = RepackagingOrder::findOrFail($id);
            $repackaging->load(['inputs.batch', 'outputs']);

            // Prevent reversal if output stock has been consumed
            $outputBatches = Batch::where('batch_no', 'like', 'B-'.$repackaging->id.'-%FIN-%')->get();
            foreach ($outputBatches as $batch) {
                if ($batch->qty_out > 0) {
                    throw new \Exception('Cannot reverse repackaging because the finished stock from this order has already been consumed/sold.');
                }
            }

            // 1. Delete Inventory Transactions individually to trigger observer
            $transactions = InventoryTransaction::where('reference_type', RepackagingOrder::class)->where('reference_id', $repackaging->id)->get();
            foreach ($transactions as $txn) {
                $txn->delete();
            }

            // 2. Delete Output Batches
            foreach ($outputBatches as $batch) {
                $batch->delete();
            }

            // 3. Restore Raw Input Batches
            foreach ($repackaging->inputs as $input) {
                if ($input->batch) {
                    $input->batch->qty_out -= $input->qty_used;
                    $input->batch->remaining_qty += $input->qty_used;
                    $input->batch->save();
                }
            }

            // 4. Delete Accounting Entries
            $journal = Journal::where('reference_type', RepackagingOrder::class)->where('reference_id', $repackaging->id)->first();
            if ($journal) {
                JournalEntry::where('journal_id', $journal->id)->delete();
                $journal->delete();
            }

            // 5. Delete Associations
            RepackagingInput::where('repackaging_order_id', $repackaging->id)->delete();
            RepackagingOutput::where('repackaging_order_id', $repackaging->id)->delete();
            RepackagingAdjustment::where('repackaging_order_id', $repackaging->id)->delete();

            $repackaging->delete();

            DB::commit();

            return response()->json(['message' => 'Repackaging deleted and stock restored successfully']);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json(['error' => $e->getMessage()], 400);
        }
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
        $query = Journal::with(['entries.account', 'creator']);

        if ($request->filled('start_date')) {
            $query->whereDate('date', '>=', $request->start_date);
        }

        if ($request->filled('end_date')) {
            $query->whereDate('date', '<=', $request->end_date);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('journal_no', 'like', "%{$search}%")
                    ->orWhere('notes', 'like', "%{$search}%");
            });
        }

        $journals = $query->orderBy('date', 'desc')->orderBy('id', 'desc')->paginate($request->get('per_page', 20));

        return response()->json(['journals' => $journals]);
    }

    public function storeJournal(Request $request)
    {
        $data = $request->all();
        if (! isset($data['journal_no'])) {
            $data['journal_no'] = 'JRN-'.time();
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

        return app(SaleController::class)->pdf($sale);
    }

    // ── Investments ─────────────────────────────────────────────────────────

    public function investmentFormData()
    {
        $accounts = ChartOfAccount::where('is_payment_method', true)->get();

        return response()->json([
            'accounts' => $accounts,
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

            return response()->json(['message' => ucfirst($investment->type).' recorded successfully.', 'investment' => $investment], 201);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json(['message' => 'Error recording transaction: '.$e->getMessage()], 500);
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

            return response()->json(['message' => ucfirst($investment->type).' updated successfully.', 'investment' => $investment]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json(['message' => 'Error updating transaction: '.$e->getMessage()], 500);
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

            return response()->json(['message' => 'Error deleting transaction: '.$e->getMessage()], 500);
        }
    }

    private function createInvestmentJournals(Investment $investment, $userId)
    {
        $journal = Journal::create([
            'journal_no' => 'JNL-'.strtoupper(Str::random(6)),
            'date' => $investment->date,
            'reference_type' => Investment::class,
            'reference_id' => $investment->id,
            'notes' => ucfirst($investment->type).' - '.($investment->reference ?? 'N/A'),
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
        if (! $user) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }

        $notifications = $user->notifications()->take(50)->get();
        $unreadCount = $user->unreadNotifications()->count();

        return response()->json([
            'notifications' => $notifications,
            'unread_count' => $unreadCount,
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
        $customer = Customer::findOrFail($id);

        $customer->load(['sales' => function ($query) {
            $query->orderBy('date', 'asc');
        }]);

        $arAcc = ChartOfAccount::where('name', 'Accounts Receivable')->first();
        $advAcc = ChartOfAccount::where('name', 'Customer Advance')->first();
        $arId = $arAcc ? $arAcc->id : 0;
        $advId = $advAcc ? $advAcc->id : 0;

        $journals = Journal::with(['entries', 'reference'])
            ->where(function ($q) use ($customer) {
                $q->where('reference_type', Customer::class)->where('reference_id', $customer->id);
            })->orWhere(function ($q) use ($customer) {
                $q->where('reference_type', Sale::class)->whereIn('reference_id', $customer->sales()->pluck('id'));
            })
            ->get();

        $ledgerEntries = collect();
        $runningBalance = 0;

        foreach ($journals as $journal) {
            $debit = 0;
            $credit = 0;

            if ($journal->reference_type == Sale::class) {
                $sale = $journal->reference;
                if ($sale && $sale->total >= 0) {
                    $runningBalance += $sale->total;
                    $ledgerEntries->push((object) [
                        'id' => $journal->id.'_sale',
                        'journal' => $journal,
                        'debit' => $sale->total,
                        'credit' => 0,
                        'running_balance' => $runningBalance,
                    ]);
                }

                $initialPaymentAmount = SalePayment::where('sale_id', $sale->id)
                    ->where(function ($q) {
                        $q->whereNull('reference')
                            ->orWhereIn('reference', ['POS Payment', 'Wallet Payment']);
                    })
                    ->sum('amount');

                $hasJournal = $journals->contains(function ($j) use ($sale) {
                    return str_contains($j->notes, 'Payment for POS Sale '.$sale->invoice_no);
                });

                if ($initialPaymentAmount > 0 && ! $hasJournal) {
                    $runningBalance -= $initialPaymentAmount;
                    $paymentJournal = clone $journal;
                    $paymentJournal->notes = 'Payment for '.$sale->invoice_no;

                    $ledgerEntries->push((object) [
                        'id' => $journal->id.'_pay',
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
                } elseif ($credit > $debit) {
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
                $journal->notes .= ' (Wallet Used: ৳'.number_format($internalTransferAmount, 0).')';
            }

            $runningBalance += $debit;
            $runningBalance -= $credit;

            $ledgerEntries->push((object) [
                'id' => $journal->id,
                'journal' => $journal,
                'debit' => $debit,
                'credit' => $credit,
                'running_balance' => $runningBalance,
            ]);
        }

        $ledgerEntries = $ledgerEntries->sortByDesc(function ($entry) {
            $parts = explode('_', (string) $entry->id);
            $journalId = str_pad($parts[0], 10, '0', STR_PAD_LEFT);
            $subSeq = isset($parts[1]) ? $parts[1] : '0';

            $seqMap = [
                'sale' => '1',
                'pay' => '2',
            ];
            $seq = $seqMap[$subSeq] ?? '0';

            return $entry->journal->date.'_'.$journalId.'_'.$seq;
        })->values();

        $perPage = (int) $request->get('per_page', 20);
        $page = (int) $request->get('page', 1);
        $total = $ledgerEntries->count();
        $offset = ($page - 1) * $perPage;
        $itemsForPage = $ledgerEntries->slice($offset, $perPage)->values();

        $formattedLedger = [];
        foreach ($itemsForPage as $entry) {
            $formattedLedger[] = [
                'id' => $entry->id,
                'date' => $entry->journal->date ? Carbon::parse($entry->journal->date)->format('Y-m-d') : ($entry->journal->created_at ? $entry->journal->created_at->toDateString() : ''),
                'description' => $entry->journal->notes ?? 'Transaction',
                'debit' => $entry->debit,
                'credit' => $entry->credit,
                'balance' => $entry->running_balance,
            ];
        }

        return response()->json([
            'ledger' => [
                'current_page' => $page,
                'data' => $formattedLedger,
                'last_page' => (int) ceil($total / max(1, $perPage)),
                'total' => $total,
            ],
            'total_due' => $customer->total_due,
            'wallet_balance' => $customer->wallet_balance,
        ]);
    }

    // ── Expense Categories ────────────────────────────────────────────────────────

    public function expenseCategoryFormData()
    {
        $coas = ChartOfAccount::where('type', 'expense')->get(['id', 'name']);
        $categories = ExpenseCategory::where('status', 1)->get(['id', 'name']);

        return response()->json([
            'chart_of_accounts' => $coas,
            'categories' => $categories,
        ]);
    }

    public function expenseCategories(Request $request)
    {
        $categories = ExpenseCategory::with('chartOfAccount')->paginate($request->get('per_page', 20));

        return response()->json([
            'categories' => $categories,
        ]);
    }

    public function storeExpenseCategory(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:50',
            'status' => 'boolean',
            'parent_id' => 'nullable|exists:expense_categories,id',
            'chart_of_account_id' => 'nullable|exists:chart_of_accounts,id',
        ]);

        $category = ExpenseCategory::create($validated);

        return response()->json(['message' => 'Expense category created', 'category' => $category], 201);
    }

    public function updateExpenseCategory(Request $request, $id)
    {
        $category = ExpenseCategory::findOrFail($id);
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:50',
            'status' => 'boolean',
            'parent_id' => 'nullable|exists:expense_categories,id',
            'chart_of_account_id' => 'nullable|exists:chart_of_accounts,id',
        ]);

        $category->update($validated);

        return response()->json(['message' => 'Expense category updated', 'category' => $category]);
    }

    public function destroyExpenseCategory($id)
    {
        $category = ExpenseCategory::findOrFail($id);
        if ($category->expenses()->count() > 0) {
            return response()->json(['error' => 'Cannot delete category with associated expenses'], 400);
        }
        $category->delete();

        return response()->json(['message' => 'Expense category deleted']);
    }

    // ── Expenses ────────────────────────────────────────────────────────────────

    public function expenseFormData()
    {
        $categories = ExpenseCategory::all(['id', 'name', 'code', 'chart_of_account_id']);

        if ($categories->isEmpty()) {
            $defaultCategories = [
                'Office Expenses',
                'Utility & Bills',
                'Rent Expense',
                'Salaries & Wages',
                'Transport & Travel',
                'Marketing & Ads',
                'Maintenance & Repair',
                'Miscellaneous',
            ];
            foreach ($defaultCategories as $name) {
                $acc = ChartOfAccount::firstOrCreate(['name' => $name, 'type' => 'expense']);
                ExpenseCategory::firstOrCreate(
                    ['name' => $name],
                    [
                        'code' => strtoupper(substr(str_replace(' ', '', $name), 0, 4)),
                        'chart_of_account_id' => $acc->id,
                    ]
                );
            }
            $categories = ExpenseCategory::all(['id', 'name', 'code', 'chart_of_account_id']);
        }

        $paymentMethods = ChartOfAccount::where('is_payment_method', 1)
            ->orWhereIn('type', ['cash', 'bank'])
            ->get(['id', 'name', 'type']);

        return response()->json([
            'categories' => $categories,
            'paymentMethods' => $paymentMethods,
            'payment_methods' => $paymentMethods,
        ]);
    }

    public function expenses(Request $request)
    {
        $query = Expense::with(['category', 'paymentMethod']);

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
            'expenses' => $expenses,
        ]);
    }

    public function storeExpense(Request $request)
    {
        $validated = $request->validate([
            'expense_category_id' => 'required|exists:expense_categories,id',
            'payment_method_id' => 'required|exists:chart_of_accounts,id',
            'amount' => 'required|numeric|min:0.01',
            'date' => 'required|date',
            'reference' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ]);

        $validated['created_by'] = auth()->id() ?? 1;

        \DB::beginTransaction();
        try {
            $expense = Expense::create($validated);

            $category = ExpenseCategory::find($validated['expense_category_id']);
            $expenseAccId = $category ? $category->chart_of_account_id : null;
            if (! $expenseAccId && $category) {
                $acc = ChartOfAccount::firstOrCreate(['name' => $category->name, 'type' => 'expense']);
                $category->chart_of_account_id = $acc->id;
                $category->save();
                $expenseAccId = $acc->id;
            }
            if (! $expenseAccId) {
                $fallbackAcc = ChartOfAccount::firstOrCreate(['name' => 'Operational Expenses', 'type' => 'expense']);
                $expenseAccId = $fallbackAcc->id;
            }

            $journalNotes = ! empty($validated['notes'])
                ? 'Expense: '.$validated['notes']
                : 'Expense ('.($category->name ?? 'Operational Expense').')';

            $journal = Journal::create([
                'journal_no' => 'EXP-'.strtoupper(Str::random(6)),
                'date' => $validated['date'],
                'reference_type' => Expense::class,
                'reference_id' => $expense->id,
                'notes' => $journalNotes,
                'created_by' => auth()->id() ?? 1,
            ]);

            // Debit Expense Account
            JournalEntry::create([
                'journal_id' => $journal->id,
                'account_id' => $expenseAccId,
                'type' => 'debit',
                'amount' => $validated['amount'],
            ]);

            // Credit Payment Method Account
            JournalEntry::create([
                'journal_id' => $journal->id,
                'account_id' => $validated['payment_method_id'],
                'type' => 'credit',
                'amount' => $validated['amount'],
            ]);

            $expense->reference_type = Journal::class;
            $expense->reference_id = $journal->id;
            $expense->save();

            \DB::commit();

            return response()->json(['message' => 'Expense created successfully', 'expense' => $expense->load(['category', 'paymentMethod'])], 201);
        } catch (\Exception $e) {
            \DB::rollBack();

            return response()->json(['error' => 'Failed to create expense: '.$e->getMessage()], 500);
        }
    }

    public function updateExpense(Request $request, $id)
    {
        $expense = Expense::findOrFail($id);

        $validated = $request->validate([
            'expense_category_id' => 'required|exists:expense_categories,id',
            'payment_method_id' => 'required|exists:chart_of_accounts,id',
            'amount' => 'required|numeric|min:0.01',
            'date' => 'required|date',
            'reference' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ]);

        \DB::beginTransaction();
        try {
            $expense->update($validated);

            $category = ExpenseCategory::find($validated['expense_category_id']);
            $expenseAccId = $category ? $category->chart_of_account_id : null;
            if (! $expenseAccId && $category) {
                $acc = ChartOfAccount::firstOrCreate(['name' => $category->name, 'type' => 'expense']);
                $category->chart_of_account_id = $acc->id;
                $category->save();
                $expenseAccId = $acc->id;
            }
            if (! $expenseAccId) {
                $fallbackAcc = ChartOfAccount::firstOrCreate(['name' => 'Operational Expenses', 'type' => 'expense']);
                $expenseAccId = $fallbackAcc->id;
            }

            $journalNotes = ! empty($validated['notes'])
                ? 'Expense: '.$validated['notes']
                : 'Expense ('.($category->name ?? 'Operational Expense').')';

            $journal = Journal::where('reference_type', Expense::class)
                ->where('reference_id', $expense->id)
                ->first();

            if (! $journal) {
                $journal = Journal::create([
                    'journal_no' => 'EXP-'.strtoupper(Str::random(6)),
                    'date' => $validated['date'],
                    'reference_type' => Expense::class,
                    'reference_id' => $expense->id,
                    'notes' => $journalNotes,
                    'created_by' => auth()->id() ?? 1,
                ]);
            } else {
                $journal->update([
                    'date' => $validated['date'],
                    'notes' => $journalNotes,
                ]);
                $journal->entries()->delete();
            }

            // Debit Expense Account
            JournalEntry::create([
                'journal_id' => $journal->id,
                'account_id' => $expenseAccId,
                'type' => 'debit',
                'amount' => $validated['amount'],
            ]);

            // Credit Payment Method Account
            JournalEntry::create([
                'journal_id' => $journal->id,
                'account_id' => $validated['payment_method_id'],
                'type' => 'credit',
                'amount' => $validated['amount'],
            ]);

            \DB::commit();

            return response()->json(['message' => 'Expense updated successfully', 'expense' => $expense->load(['category', 'paymentMethod'])]);
        } catch (\Exception $e) {
            \DB::rollBack();

            return response()->json(['error' => 'Failed to update expense: '.$e->getMessage()], 500);
        }
    }

    public function destroyExpense($id)
    {
        $expense = Expense::findOrFail($id);

        \DB::beginTransaction();
        try {
            $journals = Journal::where('reference_type', Expense::class)
                ->where('reference_id', $expense->id)
                ->get();

            foreach ($journals as $journal) {
                $journal->entries()->delete();
                $journal->delete();
            }

            $expense->delete();
            \DB::commit();

            return response()->json(['message' => 'Expense deleted successfully']);
        } catch (\Exception $e) {
            \DB::rollBack();

            return response()->json(['error' => 'Failed to delete expense: '.$e->getMessage()], 500);
        }
    }

    // ── Mobile App Reports ────────────────────────────────────────────────────────────

    public function dailySales(Request $request)
    {
        $startDate = $request->input('start_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->input('end_date', Carbon::now()->endOfMonth()->format('Y-m-d'));

        $sales = Sale::whereBetween('date', [$startDate, $endDate])
            ->select(DB::raw('DATE(date) as sale_date'), DB::raw('count(*) as total_orders'), DB::raw('sum(total) as total_revenue'))
            ->groupBy('sale_date')
            ->orderBy('sale_date', 'desc')
            ->get();

        return response()->json(['sales' => $sales]);
    }

    public function monthlySales(Request $request)
    {
        $year = $request->input('year', Carbon::now()->year);

        $sales = Sale::whereYear('date', $year)
            ->select(DB::raw('MONTH(date) as sale_month'), DB::raw('count(*) as total_orders'), DB::raw('sum(total) as total_revenue'))
            ->groupBy('sale_month')
            ->orderBy('sale_month', 'desc')
            ->get();

        return response()->json(['sales' => $sales]);
    }

    public function stockSummary(Request $request)
    {
        $rawBatches = Batch::whereHas('product', function ($q) {
            $q->where('type', 'raw');
        })->whereNull('product_variant_id')->with(['product.unit', 'warehouse'])->where('remaining_qty', '>', 0)->get();

        $standaloneBatches = Batch::whereHas('product', function ($q) {
            $q->where('type', 'finished');
        })->whereNull('product_variant_id')->with(['product.unit', 'warehouse'])->where('remaining_qty', '>', 0)->get();

        $packagedBatches = Batch::whereNotNull('product_variant_id')->with(['productVariant.product', 'warehouse'])->where('remaining_qty', '>', 0)->get();

        return response()->json([
            'raw_batches' => $rawBatches,
            'standalone_batches' => $standaloneBatches,
            'packaged_batches' => $packagedBatches,
        ]);
    }

    public function warehouseStocks(Request $request)
    {
        $stocks = WarehouseStock::with([
            'warehouse',
            'productVariant.product',
            'productVariant.unit',
            'productVariant.product.unit',
        ])
            ->where('stock', '>', 0)
            ->get();

        $batches = Batch::whereHas('product', function ($q) {
            $q->whereIn('type', ['raw', 'standalone']);
        })->whereNull('product_variant_id')
            ->with(['product.unit', 'warehouse'])
            ->where('remaining_qty', '>', 0)
            ->get();

        $merged = [];
        foreach ($stocks as $s) {
            $merged[] = [
                'type' => 'variant',
                'warehouse' => $s->warehouse,
                'stock' => $s->stock,
                'product_variant' => $s->productVariant,
            ];
        }

        foreach ($batches as $b) {
            $merged[] = [
                'type' => 'batch',
                'warehouse' => $b->warehouse,
                'stock' => $b->remaining_qty,
                'product' => $b->product,
                'batch_no' => $b->batch_no,
            ];
        }

        return response()->json(['stocks' => $merged]);
    }

    public function cashbook(Request $request)
    {
        $date = $request->filled('date') ? $request->date : now()->toDateString();

        $cashAccounts = ChartOfAccount::where('is_payment_method', 1)->get();
        $cashAccIds = $cashAccounts->pluck('id')->toArray();

        $baseOpening = $cashAccounts->sum('opening_balance');

        $priorTransactions = JournalEntry::whereIn('account_id', $cashAccIds)
            ->whereHas('journal', function ($q) use ($date) {
                $q->whereDate('date', '<', $date);
            })
            ->selectRaw('SUM(CASE WHEN type = "debit" THEN amount ELSE 0 END) as total_debit')
            ->selectRaw('SUM(CASE WHEN type = "credit" THEN amount ELSE 0 END) as total_credit')
            ->first();

        $priorDebit = $priorTransactions->total_debit ?? 0;
        $priorCredit = $priorTransactions->total_credit ?? 0;

        $openingBalance = $baseOpening + $priorDebit - $priorCredit;

        $entries = JournalEntry::whereIn('account_id', $cashAccIds)
            ->with(['journal', 'account'])
            ->whereHas('journal', function ($q) use ($date) {
                $q->whereDate('date', $date);
            })
            ->latest()
            ->get();

        $totalIn = $entries->where('type', 'debit')->sum('amount');
        $totalOut = $entries->where('type', 'credit')->sum('amount');
        $closingBalance = $openingBalance + $totalIn - $totalOut;

        return response()->json([
            'date' => $date,
            'opening_balance' => $openingBalance,
            'total_in' => $totalIn,
            'total_out' => $totalOut,
            'closing_balance' => $closingBalance,
            'entries' => $entries,
        ]);
    }

    private function consumeStockForSale(Sale $sale, $journalId = null, $userId = 1)
    {
        $hasTransactions = InventoryTransaction::where('reference_type', Sale::class)->where('reference_id', $sale->id)->exists();
        if ($hasTransactions) {
            return;
        }

        $totalCogs = 0;
        $items = SaleItem::where('sale_id', $sale->id)->get();

        $groupedItems = [];
        foreach ($items as $item) {
            if (! isset($groupedItems[$item->product_variant_id])) {
                $groupedItems[$item->product_variant_id] = [
                    'qty' => 0,
                    'unit_price' => $item->unit_price,
                    'total_weight' => 0,
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
                if ($remainingToConsume <= 0) {
                    break;
                }

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
                    'created_by' => $userId,
                ]);
            }

            if (round($remainingToConsume, 4) > 0) {
                throw new \Exception("Insufficient finished stock for variant ID: {$variantId}. Shortfall: ".$remainingToConsume);
            }
        }

        if ($totalCogs > 0) {
            $journal = Journal::find($journalId);
            if (! $journal) {
                $journal = Journal::where('reference_type', Sale::class)->where('reference_id', $sale->id)->first();
            }
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

    // ── Customer Monthly Targets & Bonuses ─────────────────────────────────────

    public function customerTargets(Request $request)
    {
        $query = CustomerTargetScheme::with(['creator', 'items.product', 'items.productVariant'])
            ->withCount('items');

        if ($request->filled('month')) {
            $query->where('target_month', $request->month);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $schemes = $query->latest('target_month')->latest('id')->paginate($request->get('per_page', 20));

        return response()->json($schemes);
    }

    public function customerTargetDetails($id, Request $request, CustomerTargetService $targetService)
    {
        $scheme = CustomerTargetScheme::with(['creator', 'items.product.unit', 'items.productVariant.unit'])->findOrFail($id);
        $report = $targetService->getSchemeReport($scheme, $request->search);

        return response()->json([
            'scheme' => $scheme,
            'report' => $report,
        ]);
    }

    public function disburseCustomerBonus($id, $customerId, Request $request, CustomerTargetService $targetService)
    {
        $scheme = CustomerTargetScheme::findOrFail($id);
        $customer = Customer::findOrFail($customerId);

        try {
            $bonus = $targetService->disburseBonus($scheme, $customer, $request->user(), $request->input('notes'));

            return response()->json([
                'message' => "Bonus of BDT {$bonus->bonus_amount} disbursed to {$customer->name}'s wallet.",
                'bonus' => $bonus,
                'wallet_balance' => (float) $customer->fresh()->wallet_balance,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }
    }
}
