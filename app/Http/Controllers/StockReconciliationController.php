<?php

namespace App\Http\Controllers;

use App\Models\Batch;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Warehouse;
use App\Models\WarehouseStock;
use App\Services\StockReconciliationService;
use Illuminate\Http\Request;

class StockReconciliationController extends Controller
{
    public function index()
    {
        $discrepancies = StockReconciliationService::detectDiscrepancies();

        $warehouses = Warehouse::all();
        
        $rawProducts = Product::where('type', 'raw')->with('unit')->get();
        $variants = ProductVariant::with(['product', 'unit'])->get();

        // Warehouse Stock mapping
        $whStocks = WarehouseStock::all();
        $whStockMap = [];
        foreach ($whStocks as $ws) {
            $key = $ws->warehouse_id . '_' . ($ws->product_variant_id ? 'variant_' . $ws->product_variant_id : 'raw_' . $ws->product_id);
            $whStockMap[$key] = (float)$ws->stock;
        }

        // Batches summary
        $batches = Batch::with(['product', 'productVariant', 'warehouse'])->latest()->paginate(20);

        return view('stock_reconciliation.index', compact(
            'discrepancies',
            'warehouses',
            'rawProducts',
            'variants',
            'whStockMap',
            'batches'
        ));
    }

    public function reconcile()
    {
        try {
            $result = StockReconciliationService::reconcileLedgerAndBatches();

            $msg = "Ledger Reconciliation Complete! Batches synced: {$result['batches_reconciled']}, Warehouse stocks updated: {$result['warehouse_stocks_synced']}, Variant stocks updated: {$result['variants_synced']}.";

            return redirect()->route('stock-reconciliation.index')->with('success', $msg);
        } catch (\Exception $e) {
            return redirect()->route('stock-reconciliation.index')->withErrors(['error' => 'Reconciliation failed: ' . $e->getMessage()]);
        }
    }

    public function physicalAudit(Request $request)
    {
        $warehouses = Warehouse::all();
        $selectedWarehouseId = $request->input('warehouse_id', $warehouses->first()->id ?? null);

        if (!$selectedWarehouseId) {
            return redirect()->route('warehouses.create')->with('error', 'Please create a warehouse first.');
        }

        $rawProducts = Product::where('type', 'raw')->with('unit')->get();
        $variants = ProductVariant::with(['product', 'unit'])->get();
        $batches = Batch::where('warehouse_id', $selectedWarehouseId)->where('remaining_qty', '>', 0)->with(['product', 'productVariant'])->get();

        // Fetch current warehouse stock
        $whStocks = WarehouseStock::where('warehouse_id', $selectedWarehouseId)->get();
        $stockMap = [];
        foreach ($whStocks as $ws) {
            if ($ws->product_variant_id) {
                $stockMap['variant_' . $ws->product_variant_id] = (float)$ws->stock;
            } else {
                $stockMap['raw_' . $ws->product_id] = (float)$ws->stock;
            }
        }

        return view('stock_reconciliation.physical_audit', compact(
            'warehouses',
            'selectedWarehouseId',
            'rawProducts',
            'variants',
            'batches',
            'stockMap'
        ));
    }

    public function storePhysicalAudit(Request $request)
    {
        $validated = $request->validate([
            'warehouse_id' => 'required|exists:warehouses,id',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.batch_id' => 'nullable|exists:batches,id',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.product_variant_id' => 'nullable|exists:product_variants,id',
            'items.*.physical_qty' => 'required|numeric|min:0',
        ]);

        try {
            $result = StockReconciliationService::processPhysicalStockAudit(
                (int)$validated['warehouse_id'],
                $validated['items'],
                $validated['notes'] ?? null,
                auth()->id() ?? 1
            );

            return redirect()->route('stock-reconciliation.index')
                ->with('success', "Physical Stock Take processed successfully. {$result['adjustments_created']} adjusting transactions were created.");
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Physical stock audit failed: ' . $e->getMessage()])->withInput();
        }
    }
}
