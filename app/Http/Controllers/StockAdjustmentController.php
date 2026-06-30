<?php

namespace App\Http\Controllers;

use App\Models\StockAdjustment;
use App\Models\Warehouse;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Batch;
use App\Models\InventoryTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StockAdjustmentController extends Controller
{
    public function index(Request $request)
    {
        $query = StockAdjustment::with(['warehouse', 'product', 'productVariant', 'batch', 'creator', 'approver']);

        if ($request->filled('start_date')) {
            $query->whereDate('created_at', '>=', $request->start_date);
        }
        
        if ($request->filled('end_date')) {
            $query->whereDate('created_at', '<=', $request->end_date);
        }
        
        if ($request->filled('warehouse_id')) {
            $query->where('warehouse_id', $request->warehouse_id);
        }
        
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $adjustments = $query->latest()->paginate(15)->withQueryString();
        $warehouses = Warehouse::all();
        return view('stock_adjustments.index', compact('adjustments', 'warehouses'));
    }

    public function create()
    {
        $warehouses = Warehouse::all();
        $products = Product::all();
        $variants = ProductVariant::all();
        $batches = Batch::with(['product', 'productVariant'])->where('remaining_qty', '>', 0)->get();
        return view('stock_adjustments.create', compact('warehouses', 'products', 'variants', 'batches'));
    }

    public function store(Request $request)
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
            return back()->withErrors(['qty' => 'Cannot remove more than the batch remaining quantity.'])->withInput();
        }

        $validated['status'] = 'pending';
        $validated['created_by'] = auth()->id() ?? 1;
        $validated['product_id'] = $batch->product_id;
        $validated['product_variant_id'] = $batch->product_variant_id;

        StockAdjustment::create($validated);

        return redirect()->route('stock-adjustments.index')->with('success', 'Stock Adjustment request submitted for approval.');
    }

    public function updateStatus(Request $request, StockAdjustment $stock_adjustment)
    {
        $action = $request->input('action');

        if ($stock_adjustment->status !== 'pending') {
            return back()->withErrors(['error' => 'Adjustment is already processed.']);
        }

        try {
            DB::beginTransaction();

            if ($action === 'approve') {
                $batch = $stock_adjustment->batch;

                if ($stock_adjustment->type === 'remove' && $batch->remaining_qty < $stock_adjustment->qty) {
                    throw new \Exception("Batch remaining quantity is less than requested removal.");
                }

                if ($stock_adjustment->type === 'add') {
                    $batch->qty_in += $stock_adjustment->qty;
                    $batch->remaining_qty += $stock_adjustment->qty;
                } else {
                    $batch->qty_out += $stock_adjustment->qty;
                    $batch->remaining_qty -= $stock_adjustment->qty;
                }
                $batch->save();

                InventoryTransaction::create([
                    'warehouse_id' => $stock_adjustment->warehouse_id,
                    'product_id' => $stock_adjustment->product_id,
                    'product_variant_id' => $stock_adjustment->product_variant_id,
                    'batch_id' => $stock_adjustment->batch_id,
                    'type' => 'adjustment',
                    'qty_in' => $stock_adjustment->type === 'add' ? $stock_adjustment->qty : 0,
                    'qty_out' => $stock_adjustment->type === 'remove' ? $stock_adjustment->qty : 0,
                    'cost' => $batch->cost_per_unit * $stock_adjustment->qty,
                    'reference_type' => StockAdjustment::class,
                    'reference_id' => $stock_adjustment->id,
                    'date' => now(),
                    'created_by' => auth()->id() ?? 1,
                ]);

                $stock_adjustment->update([
                    'status' => 'approved',
                    'approved_by' => auth()->id() ?? 1,
                ]);
            } elseif ($action === 'reject') {
                $stock_adjustment->update([
                    'status' => 'rejected',
                    'approved_by' => auth()->id() ?? 1,
                ]);
            }

            DB::commit();
            return back()->with('success', 'Adjustment request ' . ucfirst($action) . 'd.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }
}
