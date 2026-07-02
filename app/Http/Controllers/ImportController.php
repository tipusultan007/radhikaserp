<?php

namespace App\Http\Controllers;

use App\Models\Import;
use App\Models\ImportItem;
use App\Models\Batch;
use App\Models\InventoryTransaction;
use App\Models\Journal;
use App\Models\JournalEntry;
use App\Models\ChartOfAccount;
use App\Models\Supplier;
use App\Models\Warehouse;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ImportController extends Controller
{
    public function index()
    {
        $imports = Import::with(['supplier', 'warehouse'])->latest('date')->get();
        return view('imports.index', compact('imports'));
    }

    public function create()
    {
        $suppliers = Supplier::all();
        $warehouses = Warehouse::all();
        $products = Product::where('type', 'raw')->get(); // Only import raw stock
        return view('imports.create', compact('suppliers', 'warehouses', 'products'));
    }

    public function store(Request $request)
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

        DB::transaction(function () use ($validated, $request) {
            $totalCost = 0;
            foreach ($validated['items'] as $item) {
                $totalCost += $item['qty'] * $item['unit_cost'];
            }

            // 1. Create Import Record
            $import = Import::create([
                'import_no' => 'IMP-' . strtoupper(Str::random(6)),
                'supplier_id' => $validated['supplier_id'],
                'warehouse_id' => $validated['warehouse_id'],
                'date' => $validated['date'],
                'total_cost' => $totalCost,
            ]);

            // Supplier Payable Update
            $supplier = Supplier::find($validated['supplier_id']);
            $supplier->increment('total_payable', $totalCost);

            foreach ($validated['items'] as $item) {
                $lineTotal = $item['qty'] * $item['unit_cost'];

                // 2. Create Import Item
                ImportItem::create([
                    'import_id' => $import->id,
                    'product_id' => $item['product_id'],
                    'qty' => $item['qty'],
                    'unit_cost' => $item['unit_cost'],
                    'total_cost' => $lineTotal,
                ]);

                // 3. Generate Batch
                $batch = Batch::create([
                    'batch_no' => 'B-' . $import->id . '-' . $item['product_id'] . '-' . strtoupper(Str::random(4)),
                    'product_id' => $item['product_id'],
                    'warehouse_id' => $validated['warehouse_id'],
                    'import_id' => $import->id,
                    'qty_in' => $item['qty'],
                    'qty_out' => 0,
                    'remaining_qty' => $item['qty'],
                    'cost_per_unit' => $item['unit_cost'],
                    'expiry_date' => null, // Not tracking expiry in this simple form
                ]);

                // 4. Inventory Transaction (Ledger)
                InventoryTransaction::create([
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
                    'created_by' => auth()->id() ?? 1,
                ]);
            }

            // 5. Accounting Entry
            // Find or create accounts
            $inventoryAcc = ChartOfAccount::firstOrCreate(
                ['name' => 'Inventory (Raw)', 'type' => 'asset'],
                ['parent_id' => null]
            );
            $payableAcc = ChartOfAccount::firstOrCreate(
                ['name' => 'Accounts Payable', 'type' => 'liability'],
                ['parent_id' => null]
            );

            $journal = Journal::create([
                'journal_no' => 'JNL-' . strtoupper(Str::random(6)),
                'date' => $validated['date'],
                'reference_type' => Import::class,
                'reference_id' => $import->id,
                'notes' => 'Import Shipment ' . $import->import_no,
                'created_by' => auth()->id() ?? 1,
            ]);

            JournalEntry::create([
                'journal_id' => $journal->id,
                'account_id' => $inventoryAcc->id,
                'type' => 'debit',
                'amount' => $totalCost,
            ]);

            JournalEntry::create([
                'journal_id' => $journal->id,
                'account_id' => $payableAcc->id,
                'type' => 'credit',
                'amount' => $totalCost,
            ]);
        });

        return redirect()->route('imports.index')->with('success', 'Import confirmed successfully.');
    }

    public function show(Import $import)
    {
        $import->load(['items.product', 'supplier', 'warehouse']);
        
        // Find payments from supplier settlements that reference this import number
        $relatedPayments = \App\Models\Journal::where('reference_type', \App\Models\Supplier::class)
            ->where('reference_id', $import->supplier_id)
            ->where(function($query) use ($import) {
                $query->where('notes', 'like', '%' . $import->import_no . '%')
                      ->orWhere('journal_no', 'like', '%' . $import->import_no . '%');
            })
            ->with(['entries.account'])
            ->get();
            
        return view('imports.show', compact('import', 'relatedPayments'));
    }

    public function edit(Import $import)
    {
        $import->load(['items.product', 'supplier', 'warehouse']);
        $suppliers = Supplier::all();
        $warehouses = Warehouse::all();
        $products = Product::where('type', 'raw')->get();
        return view('imports.edit', compact('import', 'suppliers', 'warehouses', 'products'));
    }

    public function update(Request $request, Import $import)
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

            $this->reverseImport($import);

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

            // Supplier Payable Update
            $supplier = Supplier::find($validated['supplier_id']);
            $supplier->increment('total_payable', $totalCost);

            foreach ($validated['items'] as $item) {
                $lineTotal = $item['qty'] * $item['unit_cost'];

                ImportItem::create([
                    'import_id' => $import->id,
                    'product_id' => $item['product_id'],
                    'qty' => $item['qty'],
                    'unit_cost' => $item['unit_cost'],
                    'total_cost' => $lineTotal,
                ]);

                $batch = Batch::create([
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

                InventoryTransaction::create([
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
                    'created_by' => auth()->id() ?? 1,
                ]);
            }

            // Accounting Entry
            $inventoryAcc = ChartOfAccount::firstOrCreate(['name' => 'Inventory (Raw)', 'type' => 'asset'], ['parent_id' => null]);
            $payableAcc = ChartOfAccount::firstOrCreate(['name' => 'Accounts Payable', 'type' => 'liability'], ['parent_id' => null]);

            $journal = Journal::create([
                'journal_no' => 'JNL-' . strtoupper(Str::random(6)),
                'date' => $validated['date'],
                'reference_type' => Import::class,
                'reference_id' => $import->id,
                'notes' => 'Import Shipment ' . $import->import_no . ' (Updated)',
                'created_by' => auth()->id() ?? 1,
            ]);

            JournalEntry::create([
                'journal_id' => $journal->id,
                'account_id' => $inventoryAcc->id,
                'type' => 'debit',
                'amount' => $totalCost,
            ]);

            JournalEntry::create([
                'journal_id' => $journal->id,
                'account_id' => $payableAcc->id,
                'type' => 'credit',
                'amount' => $totalCost,
            ]);

            DB::commit();
            return redirect()->route('imports.index')->with('success', 'Import updated successfully.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => $e->getMessage()])->withInput();
        }
    }

    public function destroy(Import $import)
    {
        try {
            DB::beginTransaction();
            $this->reverseImport($import);
            $import->delete();
            DB::commit();
            return redirect()->route('imports.index')->with('success', 'Import deleted successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Failed to delete import: ' . $e->getMessage()]);
        }
    }

    private function reverseImport(Import $import)
    {
        // Prevent reversal if stock has been consumed
        $batches = Batch::where('import_id', $import->id)->get();
        foreach ($batches as $batch) {
            if ($batch->qty_out > 0) {
                throw new \Exception("Cannot reverse import because stock from batch {$batch->batch_no} has already been consumed/sold.");
            }
        }

        // 1. Revert Supplier Payable
        $supplier = Supplier::find($import->supplier_id);
        if ($supplier) {
            $supplier->decrement('total_payable', $import->total_cost);
        }

        // 2. Delete Batches
        Batch::where('import_id', $import->id)->delete();

        // 3. Delete Inventory Transactions
        InventoryTransaction::where('reference_type', Import::class)->where('reference_id', $import->id)->delete();

        // 4. Delete Accounting Entries
        $journal = Journal::where('reference_type', Import::class)->where('reference_id', $import->id)->first();
        if ($journal) {
            JournalEntry::where('journal_id', $journal->id)->delete();
            $journal->delete();
        }

        // 5. Delete Import Items
        ImportItem::where('import_id', $import->id)->delete();
    }
}

