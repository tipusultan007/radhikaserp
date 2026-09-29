<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Warehouse;
use App\Models\WarehouseStock;
use App\Models\Batch;
use App\Models\InventoryTransaction;
use App\Services\StockReconciliationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StockVerificationController extends Controller
{
    public function index(Request $request)
    {
        $data = $this->gatherVerificationData($request);
        return view('stock_verification.index', $data);
    }

    public function printReport(Request $request)
    {
        $data = $this->gatherVerificationData($request);
        return view('stock_verification.print', $data);
    }

    private function gatherVerificationData(Request $request): array
    {
        $warehouses = Warehouse::orderBy('id')->get();
        $discrepancies = StockReconciliationService::detectDiscrepancies();

        // 1. Preload Batch sums
        $variantBatchSums = DB::table('batches')
            ->select('product_variant_id', 'warehouse_id', DB::raw('SUM(remaining_qty) as total'))
            ->whereNotNull('product_variant_id')
            ->groupBy('product_variant_id', 'warehouse_id')
            ->get()
            ->groupBy('product_variant_id')
            ->map(fn($group) => $group->pluck('total', 'warehouse_id')->toArray())
            ->toArray();

        $rawBatchSums = DB::table('batches')
            ->select('product_id', 'warehouse_id', DB::raw('SUM(remaining_qty) as total'))
            ->whereNull('product_variant_id')
            ->groupBy('product_id', 'warehouse_id')
            ->get()
            ->groupBy('product_id')
            ->map(fn($group) => $group->pluck('total', 'warehouse_id')->toArray())
            ->toArray();

        // 2. Preload WarehouseStock
        $variantWsData = DB::table('warehouse_stocks')
            ->select('product_variant_id', 'warehouse_id', 'stock')
            ->whereNotNull('product_variant_id')
            ->get()
            ->groupBy('product_variant_id')
            ->map(fn($group) => $group->pluck('stock', 'warehouse_id')->toArray())
            ->toArray();

        $rawWsData = DB::table('warehouse_stocks')
            ->select('product_id', 'warehouse_id', 'stock')
            ->whereNull('product_variant_id')
            ->get()
            ->groupBy('product_id')
            ->map(fn($group) => $group->pluck('stock', 'warehouse_id')->toArray())
            ->toArray();

        // 3. Preload Ledger sums
        $variantLedgerSums = DB::table('inventory_transactions')
            ->select('product_variant_id', 'warehouse_id', DB::raw('COALESCE(SUM(qty_in), 0) - COALESCE(SUM(qty_out), 0) as net'))
            ->whereNotNull('product_variant_id')
            ->groupBy('product_variant_id', 'warehouse_id')
            ->get()
            ->groupBy('product_variant_id')
            ->map(fn($group) => $group->pluck('net', 'warehouse_id')->toArray())
            ->toArray();

        $rawLedgerSums = DB::table('inventory_transactions')
            ->select('product_id', 'warehouse_id', DB::raw('COALESCE(SUM(qty_in), 0) - COALESCE(SUM(qty_out), 0) as net'))
            ->whereNull('product_variant_id')
            ->groupBy('product_id', 'warehouse_id')
            ->get()
            ->groupBy('product_id')
            ->map(fn($group) => $group->pluck('net', 'warehouse_id')->toArray())
            ->toArray();

        // Query Variants
        $variantsQuery = ProductVariant::with(['product.unit', 'unit'])->orderBy('product_id');
        if ($request->filled('search')) {
            $s = trim($request->search);
            $variantsQuery->where(function($q) use ($s) {
                $q->where('name', 'like', "%{$s}%")
                  ->orWhere('sku', 'like', "%{$s}%")
                  ->orWhereHas('product', fn($pq) => $pq->where('name', 'like', "%{$s}%"));
            });
        }
        $variants = $variantsQuery->get();

        $variantRows = [];
        $totalVariantsCount = 0;
        $perfectVariantsCount = 0;
        $totalFinishedUnits = 0;

        foreach ($variants as $v) {
            $totalVariantsCount++;
            $totalFinishedUnits += (float)$v->current_stock;

            $row = [
                'id' => $v->id,
                'product_name' => $v->product?->name ?? 'Unknown',
                'variant_name' => $v->name,
                'sku' => $v->sku,
                'unit' => $v->unit?->short_name ?? ($v->product?->unit?->short_name ?? ''),
                'current_stock' => (float)$v->current_stock,
                'warehouses' => [],
                'is_perfect' => true,
                'discrepancies' => [],
            ];

            $totalLedger = 0;
            $totalBatch = 0;
            $totalWs = 0;

            foreach ($warehouses as $wh) {
                $ledger = (float)($variantLedgerSums[$v->id][$wh->id] ?? 0);
                $batch = (float)($variantBatchSums[$v->id][$wh->id] ?? 0);
                $ws = (float)($variantWsData[$v->id][$wh->id] ?? 0);

                $totalLedger += $ledger;
                $totalBatch += $batch;
                $totalWs += $ws;

                $whMatch = (abs($ledger - $batch) < 0.001 && abs($batch - $ws) < 0.001);
                if (!$whMatch) {
                    $row['is_perfect'] = false;
                    $row['discrepancies'][] = "{$wh->name}: Ledger ({$ledger}) vs Batch ({$batch}) vs WS ({$ws})";
                }

                $row['warehouses'][$wh->id] = [
                    'name' => $wh->name,
                    'ledger' => $ledger,
                    'batch' => $batch,
                    'ws' => $ws,
                    'match' => $whMatch,
                ];
            }

            $globalMatch = (abs($totalWs - (float)$v->current_stock) < 0.001 && abs($totalLedger - (float)$v->current_stock) < 0.001);
            if (!$globalMatch) {
                $row['is_perfect'] = false;
                $row['discrepancies'][] = "Global: Sum WH ({$totalWs}) vs Current Stock ({$v->current_stock})";
            }

            if ($row['is_perfect']) {
                $perfectVariantsCount++;
            }

            // Optional status filter
            if ($request->filled('status')) {
                if ($request->status === 'perfect' && !$row['is_perfect']) continue;
                if ($request->status === 'mismatch' && $row['is_perfect']) continue;
                if ($request->status === 'in_stock' && $row['current_stock'] <= 0) continue;
                if ($request->status === 'out_of_stock' && $row['current_stock'] > 0) continue;
            }

            $variantRows[] = $row;
        }

        // Query Raw Materials
        $rawProducts = Product::where('type', 'raw')->with('unit')->orderBy('name')->get();
        $rawRows = [];
        $perfectRawsCount = 0;
        $totalRawStock = 0;

        foreach ($rawProducts as $raw) {
            $rRow = [
                'id' => $raw->id,
                'name' => $raw->name,
                'sku' => $raw->sku,
                'unit' => $raw->unit?->short_name ?? $raw->base_unit ?? 'kg',
                'warehouses' => [],
                'is_perfect' => true,
                'total_stock' => 0,
                'discrepancies' => [],
            ];

            $rawTotalLedger = 0;
            $rawTotalBatch = 0;
            $rawTotalWs = 0;

            foreach ($warehouses as $wh) {
                $ledger = (float)($rawLedgerSums[$raw->id][$wh->id] ?? 0);
                $batch = (float)($rawBatchSums[$raw->id][$wh->id] ?? 0);
                $ws = (float)($rawWsData[$raw->id][$wh->id] ?? 0);

                $rawTotalLedger += $ledger;
                $rawTotalBatch += $batch;
                $rawTotalWs += $ws;

                $whMatch = (abs($ledger - $batch) < 0.001 && abs($batch - $ws) < 0.001);
                if (!$whMatch) {
                    $rRow['is_perfect'] = false;
                    $rRow['discrepancies'][] = "{$wh->name}: Ledger ({$ledger}) vs Batch ({$batch}) vs WS ({$ws})";
                }

                $rRow['warehouses'][$wh->id] = [
                    'name' => $wh->name,
                    'ledger' => $ledger,
                    'batch' => $batch,
                    'ws' => $ws,
                    'match' => $whMatch,
                ];
            }

            $rRow['total_stock'] = $rawTotalWs;
            $totalRawStock += $rawTotalWs;

            if ($rRow['is_perfect']) {
                $perfectRawsCount++;
            }

            $rawRows[] = $rRow;
        }

        $systemIntegrityScore = ($totalVariantsCount > 0)
            ? round(($perfectVariantsCount / $totalVariantsCount) * 100, 1)
            : 100;

        return compact(
            'warehouses',
            'variantRows',
            'rawRows',
            'totalVariantsCount',
            'perfectVariantsCount',
            'totalFinishedUnits',
            'totalRawStock',
            'perfectRawsCount',
            'systemIntegrityScore',
            'discrepancies'
        );
    }
}

