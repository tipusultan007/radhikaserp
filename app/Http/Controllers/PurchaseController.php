<?php

namespace App\Http\Controllers;

use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\Batch;
use App\Models\InventoryTransaction;
use App\Models\Journal;
use App\Models\JournalEntry;
use App\Models\ChartOfAccount;
use App\Models\Supplier;
use App\Models\Warehouse;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PurchaseController extends Controller
{
    public function index()
    {
        $purchases = Purchase::with(['supplier', 'warehouse'])->latest('date')->get();
        return view('purchases.index', compact('purchases'));
    }

    public function create()
    {
        $suppliers = Supplier::all();
        $warehouses = Warehouse::all();
        $products = Product::with(['variants.unit', 'unit'])->where('status', true)->get();
        $variants = ProductVariant::with(['product.unit', 'unit'])->where('status', true)->get();
        return view('purchases.create', compact('suppliers', 'warehouses', 'products', 'variants'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'supplier_id' => 'required|exists:suppliers,id',
            'warehouse_id' => 'required|exists:warehouses,id',
            'date' => 'required|date',
            'purchase_type' => 'required|in:imported,local',
            'delivery_cost' => 'nullable|numeric|min:0',
            'cost_breakdown' => 'nullable|array',
            'items' => 'required|array|min:1',
            'items.*.item_id' => 'nullable|string',
            'items.*.product_id' => 'nullable|exists:products,id',
            'items.*.product_variant_id' => 'nullable|exists:product_variants,id',
            'items.*.qty' => 'required|numeric|min:0.001',
            'items.*.unit_cost' => 'nullable|numeric|min:0',
        ]);

        DB::transaction(function () use ($validated, $request) {
            $totalCost = 0;
            $parsedItems = [];

            foreach ($validated['items'] as $item) {
                $rawItemId = $item['item_id'] ?? null;
                $productId = $item['product_id'] ?? null;
                $variantId = $item['product_variant_id'] ?? null;

                if ($rawItemId) {
                    $parts = explode('_', $rawItemId);
                    if ($parts[0] === 'variant') {
                        $variantId = (int)$parts[1];
                        $variant = \App\Models\ProductVariant::find($variantId);
                        $productId = $variant ? $variant->product_id : null;
                    } elseif ($parts[0] === 'product') {
                        $productId = (int)$parts[1];
                        $variantId = null;
                    }
                } elseif ($variantId) {
                    $variant = \App\Models\ProductVariant::find($variantId);
                    $productId = $variant ? $variant->product_id : $productId;
                }

                if (!$productId) {
                    throw new \Exception("Product not found for one of the purchase items.");
                }

                $product = \App\Models\Product::find($productId);
                $unitCost = (isset($item['unit_cost']) && $item['unit_cost'] !== '') ? (float)$item['unit_cost'] : 0;
                $lineTotal = $item['qty'] * $unitCost;
                $totalCost += $lineTotal;

                $parsedItems[] = [
                    'product_id' => $productId,
                    'product_variant_id' => $variantId,
                    'product' => $product,
                    'qty' => (float)$item['qty'],
                    'unit_cost' => $unitCost,
                    'line_total' => $lineTotal,
                ];
            }

            $purchaseType = $validated['purchase_type'];
            $deliveryCost = 0;
            $totalLandedCost = 0;
            $costBreakdown = null;

            if ($purchaseType === 'local') {
                $deliveryCost = (float)($request->input('delivery_cost', 0));
                $totalLandedCost = $deliveryCost;
            } else {
                $rawBreakdown = $request->input('cost_breakdown', []);
                $costBreakdown = [];
                if (is_array($rawBreakdown)) {
                    foreach ($rawBreakdown as $row) {
                        $amtVal = isset($row['amount']) && $row['amount'] !== '' ? $row['amount'] : ($row['bd_cost'] ?? '');
                        $amount = ($amtVal !== '') ? (float)$amtVal : 0;
                        $totalLandedCost += $amount;
                        $costBreakdown[] = [
                            'description' => $row['description'] ?? '',
                            'amount' => ($amtVal !== '') ? (float)$amtVal : null,
                        ];
                    }
                }
            }

            // 1. Create Purchase Record
            $purchase = Purchase::create([
                'purchase_no' => 'PUR-' . strtoupper(Str::random(6)),
                'supplier_id' => $validated['supplier_id'],
                'warehouse_id' => $validated['warehouse_id'],
                'date' => $validated['date'],
                'purchase_type' => $purchaseType,
                'delivery_cost' => $deliveryCost,
                'total_landed_cost' => $totalLandedCost,
                'cost_breakdown' => $costBreakdown,
                'total_cost' => $totalCost,
            ]);

            // Supplier Payable Update
            $supplier = Supplier::find($validated['supplier_id']);
            if ($supplier) {
                $supplier->increment('total_payable', $totalCost);
            }

            $rawCostTotal = 0;
            $finCostTotal = 0;

            foreach ($parsedItems as $item) {
                $productId = $item['product_id'];
                $variantId = $item['product_variant_id'];
                $qty = $item['qty'];
                $unitCost = $item['unit_cost'];
                $lineTotal = $item['line_total'];

                if ($variantId || ($item['product'] && $item['product']->type === 'finished')) {
                    $finCostTotal += $lineTotal;
                } else {
                    $rawCostTotal += $lineTotal;
                }

                // Landed cost distributed proportionally
                $allocatedLanded = ($totalCost > 0 && $totalLandedCost > 0) ? (($lineTotal / $totalCost) * $totalLandedCost) : 0;
                $effectiveTotal = $lineTotal + $allocatedLanded;
                $effectiveUnitCost = $qty > 0 ? ($effectiveTotal / $qty) : $unitCost;

                // 2. Create Purchase Item
                PurchaseItem::create([
                    'purchase_id' => $purchase->id,
                    'product_id' => $productId,
                    'product_variant_id' => $variantId,
                    'qty' => $qty,
                    'unit_cost' => $unitCost,
                    'total_cost' => $lineTotal,
                ]);

                // 3. Generate Batch
                $batchPrefix = $variantId 
                    ? ('B-PUR-' . $purchase->id . '-V' . $variantId . '-')
                    : ('B-PUR-' . $purchase->id . '-P' . $productId . '-');

                $batch = Batch::create([
                    'batch_no' => $batchPrefix . strtoupper(Str::random(4)),
                    'product_id' => $productId,
                    'product_variant_id' => $variantId,
                    'warehouse_id' => $validated['warehouse_id'],
                    'purchase_id' => $purchase->id,
                    'qty_in' => $qty,
                    'qty_out' => 0,
                    'remaining_qty' => $qty,
                    'cost_per_unit' => $effectiveUnitCost,
                    'expiry_date' => null, 
                ]);

                // 4. Inventory Transaction (Ledger)
                InventoryTransaction::create([
                    'warehouse_id' => $validated['warehouse_id'],
                    'product_id' => $productId,
                    'product_variant_id' => $variantId,
                    'batch_id' => $batch->id,
                    'type' => 'purchase',
                    'qty_in' => $qty,
                    'qty_out' => 0,
                    'cost' => $effectiveTotal,
                    'reference_type' => Purchase::class,
                    'reference_id' => $purchase->id,
                    'date' => $validated['date'],
                    'created_by' => auth()->id() ?? 1,
                ]);
            }

            // 5. Accounting Entry
            $inventoryRawAcc = ChartOfAccount::firstOrCreate(
                ['name' => 'Inventory (Raw)', 'type' => 'asset'],
                ['parent_id' => null]
            );
            $inventoryFinAcc = ChartOfAccount::firstOrCreate(
                ['name' => 'Inventory (Finished)', 'type' => 'asset'],
                ['parent_id' => null]
            );
            $payableAcc = ChartOfAccount::firstOrCreate(
                ['name' => 'Accounts Payable', 'type' => 'liability'],
                ['parent_id' => null]
            );

            $journal = Journal::create([
                'journal_no' => 'JNL-' . strtoupper(Str::random(6)),
                'date' => $validated['date'],
                'reference_type' => Purchase::class,
                'reference_id' => $purchase->id,
                'notes' => 'Purchase Shipment ' . $purchase->purchase_no,
                'created_by' => auth()->id() ?? 1,
            ]);

            if ($rawCostTotal > 0) {
                JournalEntry::create([
                    'journal_id' => $journal->id,
                    'account_id' => $inventoryRawAcc->id,
                    'type' => 'debit',
                    'amount' => $rawCostTotal,
                ]);
            }

            if ($finCostTotal > 0) {
                JournalEntry::create([
                    'journal_id' => $journal->id,
                    'account_id' => $inventoryFinAcc->id,
                    'type' => 'debit',
                    'amount' => $finCostTotal,
                ]);
            }

            JournalEntry::create([
                'journal_id' => $journal->id,
                'account_id' => $payableAcc->id,
                'type' => 'credit',
                'amount' => $totalCost,
            ]);
        });

        return redirect()->route('purchases.index')->with('success', 'Purchase shipment confirmed successfully.');
    }

    public function show(Purchase $purchase)
    {
        $purchase->load(['items.product.unit', 'items.productVariant.unit', 'supplier', 'warehouse']);
        
        $relatedPayments = Journal::where('reference_type', Supplier::class)
            ->where('reference_id', $purchase->supplier_id)
            ->where(function($query) use ($purchase) {
                $query->where('notes', 'like', '%' . $purchase->purchase_no . '%')
                      ->orWhere('journal_no', 'like', '%' . $purchase->purchase_no . '%');
            })
            ->with(['entries.account'])
            ->get();
            
        return view('purchases.show', compact('purchase', 'relatedPayments'));
    }

    public function edit(Purchase $purchase)
    {
        $purchase->load(['items.product.unit', 'items.productVariant.unit', 'supplier', 'warehouse']);
        $suppliers = Supplier::all();
        $warehouses = Warehouse::all();
        $products = Product::with(['variants.unit', 'unit'])->where('status', true)->get();
        $variants = ProductVariant::with(['product.unit', 'unit'])->where('status', true)->get();
        $hasConsumedStock = Batch::where('purchase_id', $purchase->id)->where('qty_out', '>', 0)->exists();

        return view('purchases.edit', compact('purchase', 'suppliers', 'warehouses', 'products', 'variants', 'hasConsumedStock'));
    }

    public function update(Request $request, Purchase $purchase)
    {
        $validated = $request->validate([
            'supplier_id' => 'required|exists:suppliers,id',
            'warehouse_id' => 'required|exists:warehouses,id',
            'date' => 'required|date',
            'purchase_type' => 'required|in:imported,local',
            'delivery_cost' => 'nullable|numeric|min:0',
            'cost_breakdown' => 'nullable|array',
            'items' => 'required|array|min:1',
            'items.*.item_id' => 'nullable|string',
            'items.*.product_id' => 'nullable|exists:products,id',
            'items.*.product_variant_id' => 'nullable|exists:product_variants,id',
            'items.*.qty' => 'required|numeric|min:0.001',
            'items.*.unit_cost' => 'nullable|numeric|min:0',
        ]);

        try {
            DB::beginTransaction();

            $this->reversePurchase($purchase);

            $totalCost = 0;
            $parsedItems = [];

            foreach ($validated['items'] as $item) {
                $rawItemId = $item['item_id'] ?? null;
                $productId = $item['product_id'] ?? null;
                $variantId = $item['product_variant_id'] ?? null;

                if ($rawItemId) {
                    $parts = explode('_', $rawItemId);
                    if ($parts[0] === 'variant') {
                        $variantId = (int)$parts[1];
                        $variant = \App\Models\ProductVariant::find($variantId);
                        $productId = $variant ? $variant->product_id : null;
                    } elseif ($parts[0] === 'product') {
                        $productId = (int)$parts[1];
                        $variantId = null;
                    }
                } elseif ($variantId) {
                    $variant = \App\Models\ProductVariant::find($variantId);
                    $productId = $variant ? $variant->product_id : $productId;
                }

                if (!$productId) {
                    throw new \Exception("Product not found for one of the purchase items.");
                }

                $product = \App\Models\Product::find($productId);
                $unitCost = (isset($item['unit_cost']) && $item['unit_cost'] !== '') ? (float)$item['unit_cost'] : 0;
                $lineTotal = $item['qty'] * $unitCost;
                $totalCost += $lineTotal;

                $parsedItems[] = [
                    'product_id' => $productId,
                    'product_variant_id' => $variantId,
                    'product' => $product,
                    'qty' => (float)$item['qty'],
                    'unit_cost' => $unitCost,
                    'line_total' => $lineTotal,
                ];
            }

            $purchaseType = $validated['purchase_type'];
            $deliveryCost = 0;
            $totalLandedCost = 0;
            $costBreakdown = null;

            if ($purchaseType === 'local') {
                $deliveryCost = (float)($request->input('delivery_cost', 0));
                $totalLandedCost = $deliveryCost;
            } else {
                $rawBreakdown = $request->input('cost_breakdown', []);
                $costBreakdown = [];
                if (is_array($rawBreakdown)) {
                    foreach ($rawBreakdown as $row) {
                        $amtVal = isset($row['amount']) && $row['amount'] !== '' ? $row['amount'] : ($row['bd_cost'] ?? '');
                        $amount = ($amtVal !== '') ? (float)$amtVal : 0;
                        $totalLandedCost += $amount;
                        $costBreakdown[] = [
                            'description' => $row['description'] ?? '',
                            'amount' => ($amtVal !== '') ? (float)$amtVal : null,
                        ];
                    }
                }
            }

            $purchase->update([
                'supplier_id' => $validated['supplier_id'],
                'warehouse_id' => $validated['warehouse_id'],
                'date' => $validated['date'],
                'purchase_type' => $purchaseType,
                'delivery_cost' => $deliveryCost,
                'total_landed_cost' => $totalLandedCost,
                'cost_breakdown' => $costBreakdown,
                'total_cost' => $totalCost,
            ]);

            // Supplier Payable Update
            $supplier = Supplier::find($validated['supplier_id']);
            if ($supplier) {
                $supplier->increment('total_payable', $totalCost);
            }

            $rawCostTotal = 0;
            $finCostTotal = 0;

            foreach ($parsedItems as $item) {
                $productId = $item['product_id'];
                $variantId = $item['product_variant_id'];
                $qty = $item['qty'];
                $unitCost = $item['unit_cost'];
                $lineTotal = $item['line_total'];

                if ($variantId || ($item['product'] && $item['product']->type === 'finished')) {
                    $finCostTotal += $lineTotal;
                } else {
                    $rawCostTotal += $lineTotal;
                }

                $allocatedLanded = ($totalCost > 0 && $totalLandedCost > 0) ? (($lineTotal / $totalCost) * $totalLandedCost) : 0;
                $effectiveTotal = $lineTotal + $allocatedLanded;
                $effectiveUnitCost = $qty > 0 ? ($effectiveTotal / $qty) : $unitCost;

                PurchaseItem::create([
                    'purchase_id' => $purchase->id,
                    'product_id' => $productId,
                    'product_variant_id' => $variantId,
                    'qty' => $qty,
                    'unit_cost' => $unitCost,
                    'total_cost' => $lineTotal,
                ]);

                $batchPrefix = $variantId 
                    ? ('B-PUR-' . $purchase->id . '-V' . $variantId . '-')
                    : ('B-PUR-' . $purchase->id . '-P' . $productId . '-');

                $batch = Batch::create([
                    'batch_no' => $batchPrefix . strtoupper(Str::random(4)),
                    'product_id' => $productId,
                    'product_variant_id' => $variantId,
                    'warehouse_id' => $validated['warehouse_id'],
                    'purchase_id' => $purchase->id,
                    'qty_in' => $qty,
                    'qty_out' => 0,
                    'remaining_qty' => $qty,
                    'cost_per_unit' => $effectiveUnitCost,
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
                    'cost' => $effectiveTotal,
                    'reference_type' => Purchase::class,
                    'reference_id' => $purchase->id,
                    'date' => $validated['date'],
                    'created_by' => auth()->id() ?? 1,
                ]);
            }

            // Accounting Entry
            $inventoryRawAcc = ChartOfAccount::firstOrCreate(['name' => 'Inventory (Raw)', 'type' => 'asset'], ['parent_id' => null]);
            $inventoryFinAcc = ChartOfAccount::firstOrCreate(['name' => 'Inventory (Finished)', 'type' => 'asset'], ['parent_id' => null]);
            $payableAcc = ChartOfAccount::firstOrCreate(['name' => 'Accounts Payable', 'type' => 'liability'], ['parent_id' => null]);

            $journal = Journal::create([
                'journal_no' => 'JNL-' . strtoupper(Str::random(6)),
                'date' => $validated['date'],
                'reference_type' => Purchase::class,
                'reference_id' => $purchase->id,
                'notes' => 'Purchase Shipment ' . $purchase->purchase_no . ' (Updated)',
                'created_by' => auth()->id() ?? 1,
            ]);

            if ($rawCostTotal > 0) {
                JournalEntry::create([
                    'journal_id' => $journal->id,
                    'account_id' => $inventoryRawAcc->id,
                    'type' => 'debit',
                    'amount' => $rawCostTotal,
                ]);
            }

            if ($finCostTotal > 0) {
                JournalEntry::create([
                    'journal_id' => $journal->id,
                    'account_id' => $inventoryFinAcc->id,
                    'type' => 'debit',
                    'amount' => $finCostTotal,
                ]);
            }

            JournalEntry::create([
                'journal_id' => $journal->id,
                'account_id' => $payableAcc->id,
                'type' => 'credit',
                'amount' => $totalCost,
            ]);

            DB::commit();
            return redirect()->route('purchases.index')->with('success', 'Purchase updated successfully.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => $e->getMessage()])->withInput();
        }
    }

    public function destroy(Purchase $purchase)
    {
        try {
            DB::beginTransaction();
            $this->reversePurchase($purchase);
            $purchase->delete();
            DB::commit();
            return redirect()->route('purchases.index')->with('success', 'Purchase deleted successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Failed to delete purchase: ' . $e->getMessage()]);
        }
    }

    private function reversePurchase(Purchase $purchase)
    {
        // Prevent reversal if stock has been consumed
        $batches = Batch::where('purchase_id', $purchase->id)->get();
        foreach ($batches as $batch) {
            if ($batch->qty_out > 0) {
                throw new \Exception("Cannot reverse purchase because stock from batch {$batch->batch_no} has already been consumed/sold.");
            }
        }

        // 1. Revert Supplier Payable
        $supplier = Supplier::find($purchase->supplier_id);
        if ($supplier) {
            $supplier->decrement('total_payable', $purchase->total_cost);
        }

        // 2. Delete Inventory Transactions using model delete so observer fires and reverts warehouse stock / variant current stock
        $txns = InventoryTransaction::where('reference_type', Purchase::class)->where('reference_id', $purchase->id)->get();
        foreach ($txns as $txn) {
            $txn->delete();
        }

        // 3. Delete Batches
        Batch::where('purchase_id', $purchase->id)->delete();

        // 4. Delete Accounting Entries
        $journals = Journal::where('reference_type', Purchase::class)->where('reference_id', $purchase->id)->get();
        foreach ($journals as $journal) {
            JournalEntry::where('journal_id', $journal->id)->delete();
            $journal->delete();
        }

        // 5. Delete Purchase Items
        PurchaseItem::where('purchase_id', $purchase->id)->delete();
    }
}
