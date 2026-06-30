<?php

namespace App\Http\Controllers;

use App\Models\RepackagingOrder;
use App\Models\RepackagingAdjustment;
use App\Models\Batch;
use Illuminate\Http\Request;
use Carbon\Carbon;

class ProductionReportController extends Controller
{
    public function repackagingYield(Request $request)
    {
        $startDate = $request->input('start_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->input('end_date', Carbon::now()->endOfMonth()->format('Y-m-d'));

        $orders = RepackagingOrder::whereBetween('date', [$startDate, $endDate])
            ->with(['inputs.product', 'outputs.product', 'outputs.productVariant', 'adjustments'])
            ->orderBy('date', 'desc')
            ->get();

        $yieldData = [];
        foreach ($orders as $order) {
            $totalInputWeight = $order->inputs->sum('qty_used');
            
            // Output weight depends on if it's a standalone product or a variant
            $totalOutputWeight = 0;
            foreach ($order->outputs as $out) {
                if ($out->product_variant_id) {
                    $totalOutputWeight += ($out->qty_produced * $out->productVariant->unit_qty);
                } else {
                    $totalOutputWeight += $out->qty_produced; // Standard base unit
                }
            }

            $diff = $totalOutputWeight - $totalInputWeight;
            $yieldPct = $totalInputWeight > 0 ? ($totalOutputWeight / $totalInputWeight) * 100 : 0;

            $yieldData[] = [
                'order' => $order,
                'input_weight' => $totalInputWeight,
                'output_weight' => $totalOutputWeight,
                'diff' => $diff,
                'yield_pct' => $yieldPct
            ];
        }

        return view('reports.production.yield', compact('yieldData', 'startDate', 'endDate'));
    }

    public function lossGainReport(Request $request)
    {
        $startDate = $request->input('start_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->input('end_date', Carbon::now()->endOfMonth()->format('Y-m-d'));

        $adjustments = RepackagingAdjustment::whereHas('repackagingOrder', function($q) use ($startDate, $endDate) {
                $q->whereBetween('date', [$startDate, $endDate]);
            })
            ->with('repackagingOrder')
            ->get();

        $totalLoss = $adjustments->where('type', 'loss')->sum('qty');
        $totalGain = $adjustments->where('type', 'gain')->sum('qty');
        $net = $totalGain - $totalLoss;

        return view('reports.production.loss_gain', compact('adjustments', 'totalLoss', 'totalGain', 'net', 'startDate', 'endDate'));
    }

    public function costPerBatch(Request $request)
    {
        // We only care about internally produced batches (where import_id is null)
        $batches = Batch::whereNull('import_id')
            ->whereHas('product', function($q) {
                // Focus on finished goods that were produced
                $q->where('type', 'finished');
            })
            ->with(['product', 'productVariant', 'warehouse'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('reports.production.batch_cost', compact('batches'));
    }
}
