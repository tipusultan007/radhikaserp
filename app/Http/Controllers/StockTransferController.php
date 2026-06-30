<?php

namespace App\Http\Controllers;

use App\Models\StockTransfer;
use App\Models\StockTransferItem;
use App\Models\Warehouse;
use App\Models\ProductVariant;
use App\Models\Batch;
use App\Models\InventoryTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class StockTransferController extends Controller
{
    public function index(Request $request)
    {
        $query = StockTransfer::with(['fromWarehouse', 'toWarehouse']);

        if ($request->filled('start_date')) {
            $query->whereDate('created_at', '>=', $request->start_date);
        }
        
        if ($request->filled('end_date')) {
            $query->whereDate('created_at', '<=', $request->end_date);
        }
        
        if ($request->filled('transfer_no')) {
            $query->where('transfer_no', 'like', '%' . $request->transfer_no . '%');
        }
        
        if ($request->filled('warehouse_id')) {
            $query->where(function($q) use ($request) {
                $q->where('from_warehouse_id', $request->warehouse_id)
                  ->orWhere('to_warehouse_id', $request->warehouse_id);
            });
        }

        $transfers = $query->latest()->paginate(15)->withQueryString();
        $warehouses = Warehouse::all();
        return view('stock_transfers.index', compact('transfers', 'warehouses'));
    }

    public function create()
    {
        $warehouses = Warehouse::all();
        $variants = ProductVariant::with('product')->get();
        return view('stock_transfers.create', compact('warehouses', 'variants'));
    }

    public function store(Request $request)
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
                'transfer_no' => 'TRF-' . strtoupper(Str::random(6)),
                'from_warehouse_id' => $validated['from_warehouse_id'],
                'to_warehouse_id' => $validated['to_warehouse_id'],
                'status' => 'draft',
                'created_by' => auth()->id() ?? 1,
            ]);

            foreach ($validated['items'] as $item) {
                StockTransferItem::create([
                    'stock_transfer_id' => $transfer->id,
                    'product_variant_id' => $item['product_variant_id'],
                    'batch_id' => null, // Batch resolution happens during Send
                    'qty' => $item['qty'],
                ]);
            }

            DB::commit();
            return redirect()->route('stock-transfers.index')->with('success', 'Transfer Draft created successfully.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => $e->getMessage()])->withInput();
        }
    }

    public function show(StockTransfer $stock_transfer)
    {
        $stock_transfer->load(['fromWarehouse', 'toWarehouse', 'items.productVariant.product', 'creator']);
        return view('stock_transfers.show', compact('stock_transfer'));
    }

    public function updateStatus(Request $request, StockTransfer $stock_transfer)
    {
        $action = $request->input('action');

        try {
            DB::beginTransaction();

            if ($action === 'send' && $stock_transfer->status === 'draft') {
                // Deduct from Source
                foreach ($stock_transfer->items as $item) {
                    $batches = Batch::where('product_variant_id', $item->product_variant_id)
                        ->where('warehouse_id', $stock_transfer->from_warehouse_id)
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

                        InventoryTransaction::create([
                            'warehouse_id' => $stock_transfer->from_warehouse_id,
                            'product_id' => $batch->product_id,
                            'product_variant_id' => $item->product_variant_id,
                            'batch_id' => $batch->id,
                            'type' => 'transfer_out',
                            'qty_in' => 0,
                            'qty_out' => $takeQty,
                            'cost' => $batch->cost_per_unit * $takeQty,
                            'reference_type' => StockTransfer::class,
                            'reference_id' => $stock_transfer->id,
                            'date' => now(),
                            'created_by' => auth()->id() ?? 1,
                        ]);

                        $remainingToConsume -= $takeQty;
                    }

                    if (round($remainingToConsume, 4) > 0) {
                        throw new \Exception("Insufficient stock in source warehouse for variant ID: {$item->product_variant_id}");
                    }
                }
                $stock_transfer->update(['status' => 'sent']);
            } 
            elseif ($action === 'receive' && $stock_transfer->status === 'sent') {
                // Add to Destination
                foreach ($stock_transfer->items as $item) {
                    // We generate a new batch at the destination warehouse based on standard cost
                    $variant = ProductVariant::find($item->product_variant_id);
                    
                    // Approximate cost from latest batches or assume 0 for simplicity in this demo if not tracking perfect transit cost
                    $latestBatch = Batch::where('product_variant_id', $variant->id)->latest()->first();
                    $costPerUnit = $latestBatch ? $latestBatch->cost_per_unit : 0;

                    $newBatch = Batch::create([
                        'batch_no' => 'B-TRF-' . $stock_transfer->id . '-' . $variant->id . '-' . strtoupper(Str::random(4)),
                        'product_id' => $variant->product_id,
                        'product_variant_id' => $variant->id,
                        'warehouse_id' => $stock_transfer->to_warehouse_id,
                        'qty_in' => $item->qty,
                        'qty_out' => 0,
                        'remaining_qty' => $item->qty,
                        'cost_per_unit' => $costPerUnit,
                    ]);

                    InventoryTransaction::create([
                        'warehouse_id' => $stock_transfer->to_warehouse_id,
                        'product_id' => $variant->product_id,
                        'product_variant_id' => $variant->id,
                        'batch_id' => $newBatch->id,
                        'type' => 'transfer_in',
                        'qty_in' => $item->qty,
                        'qty_out' => 0,
                        'cost' => $costPerUnit * $item->qty,
                        'reference_type' => StockTransfer::class,
                        'reference_id' => $stock_transfer->id,
                        'date' => now(),
                        'created_by' => auth()->id() ?? 1,
                    ]);
                }
                $stock_transfer->update(['status' => 'received']);
            }

            DB::commit();
            return back()->with('success', 'Transfer status updated to ' . ucfirst($stock_transfer->status));

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }
}
