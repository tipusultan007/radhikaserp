<?php

namespace App\Http\Controllers;

use App\Models\RepackagingOrder;
use App\Models\RepackagingInput;
use App\Models\RepackagingOutput;
use App\Models\RepackagingAdjustment;
use App\Models\Batch;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Warehouse;
use App\Models\InventoryTransaction;
use App\Models\Journal;
use App\Models\JournalEntry;
use App\Models\ChartOfAccount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class RepackagingController extends Controller
{
    public function index(Request $request)
    {
        $query = RepackagingOrder::with(['warehouse', 'creator']);

        if ($request->filled('start_date')) {
            $query->whereDate('date', '>=', $request->start_date);
        }
        
        if ($request->filled('end_date')) {
            $query->whereDate('date', '<=', $request->end_date);
        }
        
        if ($request->filled('ref_no')) {
            $query->where('ref_no', 'like', '%' . $request->ref_no . '%');
        }
        
        if ($request->filled('warehouse_id')) {
            $query->where('warehouse_id', $request->warehouse_id);
        }

        $orders = $query->latest('date')->paginate(15)->withQueryString();
        $warehouses = Warehouse::all();
        return view('repackaging.index', compact('orders', 'warehouses'));
    }

    public function create()
    {
        $warehouses = Warehouse::all();
        $inputProducts = Product::whereIn('type', ['raw', 'finished'])->get();
        $finishedProducts = Product::where('type', 'finished')->get();
        $variants = ProductVariant::with('product')->get();
        return view('repackaging.create', compact('warehouses', 'inputProducts', 'finishedProducts', 'variants'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'warehouse_id' => 'required|exists:warehouses,id',
            'date' => 'required|date',
            'input_product_id' => 'required|exists:products,id',
            'input_qty' => 'required|numeric|min:0.001',
            'output_item' => 'required|string',
            'output_qty' => 'required|numeric|min:0.001',
            'expenses' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        $expenses = $validated['expenses'] ?? 0;

        try {
            DB::beginTransaction();

            $warehouseId = $validated['warehouse_id'];
            $inputProductId = $validated['input_product_id'];
            $inputQty = $validated['input_qty'];

            // 1. FIFO Batch Consumption
            $batches = Batch::where('product_id', $inputProductId)
                ->where('warehouse_id', $warehouseId)
                ->where('remaining_qty', '>', 0)
                ->orderBy('id', 'asc') // Oldest first
                ->lockForUpdate()
                ->get();

            $totalRawCost = 0;
            $remainingToConsume = $inputQty;
            $consumedBatches = [];

            foreach ($batches as $batch) {
                if ($remainingToConsume <= 0) break;

                $takeQty = min($batch->remaining_qty, $remainingToConsume);
                $costForThisTake = $takeQty * $batch->cost_per_unit;

                // Update batch
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

            // 2. Cost Redistribution
            $totalCost = $totalRawCost + $expenses;
            $outputUnitCost = $totalCost / $validated['output_qty'];

            // 3. Create Repackaging Order
            $order = RepackagingOrder::create([
                'ref_no' => 'RPK-' . strtoupper(Str::random(6)),
                'warehouse_id' => $warehouseId,
                'date' => $validated['date'],
                'created_by' => auth()->id() ?? 1,
                'notes' => $validated['notes'] ?? '',
            ]);

            // 4. Create Inputs & Output
            foreach ($consumedBatches as $consumed) {
                RepackagingInput::create([
                    'repackaging_order_id' => $order->id,
                    'batch_id' => $consumed['batch_id'],
                    'product_id' => $inputProductId,
                    'qty_used' => $consumed['qty_used'],
                ]);

                // Inventory Transaction for Input (Out)
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
                    'created_by' => auth()->id() ?? 1,
                ]);
            }

            $outputParts = explode('_', $validated['output_item']);
            $outputType = $outputParts[0];
            $outputId = $outputParts[1];
            
            $productId = null;
            $variantId = null;
            $unitQty = 1; // Default for standard products

            if ($outputType === 'product') {
                $productId = $outputId;
            } else {
                $variantId = $outputId;
                $variant = ProductVariant::find($variantId);
                $productId = $variant->product_id;
                $unitQty = $variant->unit_qty;
            }

            RepackagingOutput::create([
                'repackaging_order_id' => $order->id,
                'product_id' => $productId,
                'product_variant_id' => $variantId,
                'warehouse_id' => $warehouseId,
                'qty_produced' => $validated['output_qty'],
                'unit_cost' => $outputUnitCost,
                'total_cost' => $totalCost,
            ]);

            // Create Batch for output Variant so it can be sold via POS
            $outputBatch = Batch::create([
                'batch_no' => 'B-' . $order->id . '-' . $productId . '-FIN-' . strtoupper(Str::random(4)),
                'product_id' => $productId, // Linked to Master Product
                'product_variant_id' => $variantId,
                'warehouse_id' => $warehouseId,
                'import_id' => null, // Produced internally
                'qty_in' => $validated['output_qty'],
                'qty_out' => 0,
                'remaining_qty' => $validated['output_qty'],
                'cost_per_unit' => $outputUnitCost,
                'expiry_date' => null,
            ]);

            // Inventory Transaction for Output (In)
            InventoryTransaction::create([
                'warehouse_id' => $warehouseId,
                'product_id' => $productId,
                'product_variant_id' => $variantId,
                'batch_id' => $outputBatch->id,
                'type' => 'repack_output',
                'qty_in' => $validated['output_qty'],
                'qty_out' => 0,
                'cost' => $totalCost,
                'reference_type' => RepackagingOrder::class,
                'reference_id' => $order->id,
                'date' => $validated['date'],
                'created_by' => auth()->id() ?? 1,
            ]);

            // Yield Calculation (Optional Tracking)
            $totalOutputKg = $validated['output_qty'] * $unitQty;
            if ($inputQty != $totalOutputKg) {
                $diff = $totalOutputKg - $inputQty;
                RepackagingAdjustment::create([
                    'repackaging_order_id' => $order->id,
                    'type' => $diff > 0 ? 'gain' : 'loss',
                    'qty' => abs($diff),
                    'reason' => 'Yield mismatch during repackaging',
                ]);
            }

            // Accounting Entries
            $inventoryRawAcc = ChartOfAccount::firstOrCreate(['name' => 'Inventory (Raw)', 'type' => 'asset']);
            $inventoryFinAcc = ChartOfAccount::firstOrCreate(['name' => 'Inventory (Finished)', 'type' => 'asset']);
            $expenseAcc = ChartOfAccount::firstOrCreate(['name' => 'Repackaging Expenses', 'type' => 'expense']);

            $journal = Journal::create([
                'journal_no' => 'JNL-' . strtoupper(Str::random(6)),
                'date' => $validated['date'],
                'reference_type' => RepackagingOrder::class,
                'reference_id' => $order->id,
                'notes' => 'Repackaging ' . $order->ref_no,
                'created_by' => auth()->id() ?? 1,
            ]);

            // Debit Finished Goods
            JournalEntry::create([
                'journal_id' => $journal->id,
                'account_id' => $inventoryFinAcc->id,
                'type' => 'debit',
                'amount' => $totalCost,
            ]);

            // Credit Raw Goods
            JournalEntry::create([
                'journal_id' => $journal->id,
                'account_id' => $inventoryRawAcc->id,
                'type' => 'credit',
                'amount' => $totalRawCost,
            ]);

            // Credit Cash (or Payable) for expenses
            if ($expenses > 0) {
                $cashAcc = ChartOfAccount::firstOrCreate(['name' => 'Cash', 'type' => 'asset']);
                JournalEntry::create([
                    'journal_id' => $journal->id,
                    'account_id' => $cashAcc->id,
                    'type' => 'credit',
                    'amount' => $expenses,
                ]);
                
                // Debit Repackaging Expenses to balance if we consider expense already paid or accrued
                // Wait! If we Debit Finished Goods ($totalCost) and Credit Raw Goods ($totalRawCost) and Credit Cash ($expenses)
                // Debit ($totalRawCost + $expenses) = Credit ($totalRawCost + $expenses).
                // Actually the expense cost is capitalized into the asset (Finished Goods).
                // Yes, Debit Asset (Finished Goods) and Credit Cash. The expense is not recorded on PnL directly, it's inside COGS eventually.
                // Or maybe the user wants it on PnL. If they want it on PnL, they would Debit Expense and Credit Cash. And Debit Finished Goods and Credit Raw Goods... But wait, we must capitalize labor/packaging into inventory for accurate COGS.
                // Capitalizing is correct: Debit Finished Goods, Credit Raw, Credit Cash. The equation balances!
            }

            DB::commit();

            return redirect()->route('repackaging.index')->with('success', 'Repackaging Order created successfully.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => $e->getMessage()])->withInput();
        }
    }

    public function show(RepackagingOrder $repackaging)
    {
        $repackaging->load(['inputs.product', 'outputs.productVariant.product', 'warehouse', 'creator']);
        return view('repackaging.show', compact('repackaging'));
    }

    public function edit(RepackagingOrder $repackaging)
    {
        $repackaging->load(['inputs.product', 'outputs.product', 'outputs.productVariant', 'warehouse']);
        $warehouses = Warehouse::all();
        $inputProducts = Product::whereIn('type', ['raw', 'finished'])->get();
        $finishedProducts = Product::where('type', 'finished')->get();
        $variants = ProductVariant::with('product')->get();
        return view('repackaging.edit', compact('repackaging', 'warehouses', 'inputProducts', 'finishedProducts', 'variants'));
    }

    public function update(Request $request, RepackagingOrder $repackaging)
    {
        $validated = $request->validate([
            'warehouse_id' => 'required|exists:warehouses,id',
            'date' => 'required|date',
            'input_product_id' => 'required|exists:products,id',
            'input_qty' => 'required|numeric|min:0.001',
            'output_item' => 'required|string',
            'output_qty' => 'required|numeric|min:0.001',
            'expenses' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        $expenses = $validated['expenses'] ?? 0;

        try {
            DB::beginTransaction();

            $this->reverseRepackaging($repackaging);

            $warehouseId = $validated['warehouse_id'];
            $inputProductId = $validated['input_product_id'];
            $inputQty = $validated['input_qty'];

            // 1. FIFO Batch Consumption
            $batches = Batch::where('product_id', $inputProductId)
                ->where('warehouse_id', $warehouseId)
                ->where('remaining_qty', '>', 0)
                ->orderBy('id', 'asc') // Oldest first
                ->lockForUpdate()
                ->get();

            $totalRawCost = 0;
            $remainingToConsume = $inputQty;
            $consumedBatches = [];

            foreach ($batches as $batch) {
                if ($remainingToConsume <= 0) break;

                $takeQty = min($batch->remaining_qty, $remainingToConsume);
                $costForThisTake = $takeQty * $batch->cost_per_unit;

                // Update batch
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

            // 2. Cost Redistribution
            $totalCost = $totalRawCost + $expenses;
            $outputUnitCost = $totalCost / $validated['output_qty'];

            // 3. Update Repackaging Order
            $repackaging->update([
                'warehouse_id' => $warehouseId,
                'date' => $validated['date'],
                'notes' => $validated['notes'] ?? '',
            ]);

            // 4. Create Inputs & Output
            foreach ($consumedBatches as $consumed) {
                RepackagingInput::create([
                    'repackaging_order_id' => $repackaging->id,
                    'batch_id' => $consumed['batch_id'],
                    'product_id' => $inputProductId,
                    'qty_used' => $consumed['qty_used'],
                ]);

                // Inventory Transaction for Input (Out)
                InventoryTransaction::create([
                    'warehouse_id' => $warehouseId,
                    'product_id' => $inputProductId,
                    'batch_id' => $consumed['batch_id'],
                    'type' => 'repack_input',
                    'qty_in' => 0,
                    'qty_out' => $consumed['qty_used'],
                    'cost' => $consumed['cost'],
                    'reference_type' => RepackagingOrder::class,
                    'reference_id' => $repackaging->id,
                    'date' => $validated['date'],
                    'created_by' => auth()->id() ?? 1,
                ]);
            }

            $outputParts = explode('_', $validated['output_item']);
            $outputType = $outputParts[0];
            $outputId = $outputParts[1];
            
            $productId = null;
            $variantId = null;
            $unitQty = 1; // Default for standard products

            if ($outputType === 'product') {
                $productId = $outputId;
            } else {
                $variantId = $outputId;
                $variant = ProductVariant::find($variantId);
                $productId = $variant->product_id;
                $unitQty = $variant->unit_qty;
            }

            RepackagingOutput::create([
                'repackaging_order_id' => $repackaging->id,
                'product_id' => $productId,
                'product_variant_id' => $variantId,
                'warehouse_id' => $warehouseId,
                'qty_produced' => $validated['output_qty'],
                'unit_cost' => $outputUnitCost,
                'total_cost' => $totalCost,
            ]);

            // Create Batch for output Variant so it can be sold via POS
            $outputBatch = Batch::create([
                'batch_no' => 'B-' . $repackaging->id . '-' . $productId . '-FIN-' . strtoupper(Str::random(4)),
                'product_id' => $productId, // Linked to Master Product
                'product_variant_id' => $variantId,
                'warehouse_id' => $warehouseId,
                'import_id' => null, // Produced internally
                'qty_in' => $validated['output_qty'],
                'qty_out' => 0,
                'remaining_qty' => $validated['output_qty'],
                'cost_per_unit' => $outputUnitCost,
                'expiry_date' => null,
            ]);

            // Inventory Transaction for Output (In)
            InventoryTransaction::create([
                'warehouse_id' => $warehouseId,
                'product_id' => $productId,
                'product_variant_id' => $variantId,
                'batch_id' => $outputBatch->id,
                'type' => 'repack_output',
                'qty_in' => $validated['output_qty'],
                'qty_out' => 0,
                'cost' => $totalCost,
                'reference_type' => RepackagingOrder::class,
                'reference_id' => $repackaging->id,
                'date' => $validated['date'],
                'created_by' => auth()->id() ?? 1,
            ]);

            // Yield Calculation
            $totalOutputKg = $validated['output_qty'] * $unitQty;
            if ($inputQty != $totalOutputKg) {
                $diff = $totalOutputKg - $inputQty;
                RepackagingAdjustment::create([
                    'repackaging_order_id' => $repackaging->id,
                    'type' => $diff > 0 ? 'gain' : 'loss',
                    'qty' => abs($diff),
                    'reason' => 'Yield mismatch during repackaging',
                ]);
            }

            // Accounting Entries
            $inventoryRawAcc = ChartOfAccount::firstOrCreate(['name' => 'Inventory (Raw)', 'type' => 'asset']);
            $inventoryFinAcc = ChartOfAccount::firstOrCreate(['name' => 'Inventory (Finished)', 'type' => 'asset']);

            $journal = Journal::create([
                'journal_no' => 'JNL-' . strtoupper(Str::random(6)),
                'date' => $validated['date'],
                'reference_type' => RepackagingOrder::class,
                'reference_id' => $repackaging->id,
                'notes' => 'Repackaging ' . $repackaging->ref_no . ' (Updated)',
                'created_by' => auth()->id() ?? 1,
            ]);

            // Debit Finished Goods
            JournalEntry::create([
                'journal_id' => $journal->id,
                'account_id' => $inventoryFinAcc->id,
                'type' => 'debit',
                'amount' => $totalCost,
            ]);

            // Credit Raw Goods
            JournalEntry::create([
                'journal_id' => $journal->id,
                'account_id' => $inventoryRawAcc->id,
                'type' => 'credit',
                'amount' => $totalRawCost,
            ]);

            // Credit Cash (or Payable) for expenses
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

            return redirect()->route('repackaging.index')->with('success', 'Repackaging Order updated successfully.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => $e->getMessage()])->withInput();
        }
    }

    public function destroy(RepackagingOrder $repackaging)
    {
        try {
            DB::beginTransaction();
            $this->reverseRepackaging($repackaging);
            $repackaging->delete();
            DB::commit();
            return redirect()->route('repackaging.index')->with('success', 'Repackaging deleted successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Failed to delete repackaging: ' . $e->getMessage()]);
        }
    }

    private function reverseRepackaging(RepackagingOrder $repackaging)
    {
        $repackaging->load(['inputs.batch', 'outputs']);

        // Prevent reversal if output stock has been consumed
        // The output batch was created during repackaging with batch_no starting with B-{order_id}-
        $outputBatches = Batch::where('batch_no', 'like', 'B-' . $repackaging->id . '-%FIN-%')->get();
        foreach ($outputBatches as $batch) {
            if ($batch->qty_out > 0) {
                throw new \Exception("Cannot reverse repackaging because the finished stock from this order has already been consumed/sold.");
            }
        }

        // 1. Delete Inventory Transactions FIRST to remove foreign key dependencies
        InventoryTransaction::where('reference_type', RepackagingOrder::class)->where('reference_id', $repackaging->id)->delete();

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
    }
}
