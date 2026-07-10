<?php

namespace App\Http\Controllers;

use App\Models\Batch;
use App\Models\Product;
use App\Models\Warehouse;
use App\Models\InventoryTransaction;
use Illuminate\Http\Request;
use Carbon\Carbon;

class InventoryReportController extends Controller
{
    public function stockSummary()
    {
        $rawBatches = Batch::whereHas('product', function($q) {
            $q->where('type', 'raw');
        })->whereNull('product_variant_id')->with(['product', 'warehouse'])->where('remaining_qty', '>', 0)->get();

        $standaloneBatches = Batch::whereHas('product', function($q) {
            $q->where('type', 'finished');
        })->whereNull('product_variant_id')->with(['product', 'warehouse'])->where('remaining_qty', '>', 0)->get();

        $packagedBatches = Batch::whereNotNull('product_variant_id')->with(['productVariant.product', 'warehouse'])->where('remaining_qty', '>', 0)->get();

        return view('reports.inventory.stock_summary', compact('rawBatches', 'standaloneBatches', 'packagedBatches'));
    }

    public function stockSummaryPrint()
    {
        $rawBatches = Batch::whereHas('product', function($q) {
            $q->where('type', 'raw');
        })->whereNull('product_variant_id')->with(['product', 'warehouse'])->where('remaining_qty', '>', 0)->get();

        $standaloneBatches = Batch::whereHas('product', function($q) {
            $q->where('type', 'finished');
        })->whereNull('product_variant_id')->with(['product', 'warehouse'])->where('remaining_qty', '>', 0)->get();

        $packagedBatches = Batch::whereNotNull('product_variant_id')->with(['productVariant.product', 'warehouse'])->where('remaining_qty', '>', 0)->get();

        return view('reports.inventory.stock_summary_print', compact('rawBatches', 'standaloneBatches', 'packagedBatches'));
    }

    public function stockByWarehouse()
    {
        $warehouses = Warehouse::all();
        $stockData = [];

        foreach ($warehouses as $warehouse) {
            $raw = Batch::where('warehouse_id', $warehouse->id)->whereHas('product', function($q) {
                $q->where('type', 'raw');
            })->whereNull('product_variant_id')->where('remaining_qty', '>', 0)->with('product')->get();
            
            $finished = Batch::where('warehouse_id', $warehouse->id)->where(function($q) {
                $q->whereHas('product', function($q2) {
                    $q2->where('type', 'finished');
                })->orWhereNotNull('product_variant_id');
            })->where('remaining_qty', '>', 0)->with(['product', 'productVariant'])->get();

            $stockData[] = [
                'warehouse' => $warehouse,
                'raw' => $raw,
                'finished' => $finished,
                'total_value' => $raw->sum(fn($b) => $b->remaining_qty * $b->cost_per_unit) + $finished->sum(fn($b) => $b->remaining_qty * $b->cost_per_unit)
            ];
        }

        return view('reports.inventory.stock_warehouse', compact('stockData'));
    }

    public function stockByDate(Request $request)
    {
        $date = $request->input('date', Carbon::today()->format('Y-m-d'));
        
        // Sum Qty In - Qty Out for each product/variant up to the given date
        $transactions = InventoryTransaction::whereDate('date', '<=', $date)
            ->with(['product', 'productVariant', 'warehouse'])
            ->get();

        $stock = [];
        foreach ($transactions as $txn) {
            $key = $txn->warehouse_id . '_' . $txn->product_id . '_' . ($txn->product_variant_id ?? 'none');
            
            if (!isset($stock[$key])) {
                $stock[$key] = [
                    'warehouse' => $txn->warehouse->name,
                    'product_name' => $txn->product->name,
                    'variant_name' => $txn->productVariant ? $txn->productVariant->name : 'N/A',
                    'type' => ($txn->product->type === 'finished' || $txn->product_variant_id) ? 'finished' : 'raw',
                    'unit' => $txn->productVariant ? $txn->productVariant->unit_type : $txn->product->base_unit,
                    'variant_unit_qty' => $txn->productVariant ? $txn->productVariant->getBaseQuantity() : null,
                    'base_unit' => $txn->product->base_unit,
                    'qty' => 0,
                    'value' => 0
                ];
            }
            
            $netQty = $txn->qty_in - $txn->qty_out;
            $stock[$key]['qty'] += $netQty;
            $stock[$key]['value'] += ($netQty * $txn->cost); // Approximation if cost varies, but acceptable for a historical snapshot
        }

        // Filter out zero quantities
        $stock = array_filter($stock, fn($s) => $s['qty'] > 0);

        return view('reports.inventory.stock_date', compact('stock', 'date'));
    }

    public function batchMovement(Request $request)
    {
        $query = Batch::with(['product', 'productVariant', 'warehouse']);
        
        if ($request->filled('batch_no')) {
            $query->where('batch_no', 'like', '%' . $request->batch_no . '%');
        }
        
        $batches = $query->orderBy('created_at', 'desc')->paginate(20)->withQueryString();

        return view('reports.inventory.batch_movement', compact('batches'));
    }
}
