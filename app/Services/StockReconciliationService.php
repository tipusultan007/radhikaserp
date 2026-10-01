<?php

namespace App\Services;

use App\Models\Batch;
use App\Models\InventoryTransaction;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\WarehouseStock;
use App\Models\StockAdjustment;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\RepackagingOrder;
use App\Models\RepackagingInput;
use App\Models\RepackagingOutput;
use App\Models\Journal;
use App\Models\JournalEntry;
use App\Models\ChartOfAccount;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class StockReconciliationService
{
    /**
     * Reconcile all stock tallies (Batches, WarehouseStock, ProductVariant stock) directly from InventoryTransaction ledger.
     */
    public static function reconcileLedgerAndBatches(): array
    {
        return DB::transaction(function () {
            $batchUpdatesCount = 0;
            $warehouseStockCount = 0;
            $variantUpdatesCount = 0;
            $discrepanciesFixed = [];

            // -1. Process stock consumption for any delivered/migrated sales missing inventory transactions
            $salesConsumedCount = self::processUnconsumedDeliveredSales();
            if ($salesConsumedCount > 0) {
                $discrepanciesFixed[] = "Processed stock consumption for {$salesConsumedCount} delivered/migrated sale(s).";
            }

            // -0.5. Scan for variant batches with negative remaining_qty and auto-repackage from raw stock where available
            $negativeBatches = Batch::whereNotNull('product_variant_id')
                ->where('remaining_qty', '<', 0)
                ->get();

            foreach ($negativeBatches as $negBatch) {
                $deficit = abs((float)$negBatch->remaining_qty);
                if ($deficit > 0) {
                    $repackagedBatch = self::autoRepackageRawToVariant(
                        $negBatch->warehouse_id,
                        $negBatch->product_variant_id,
                        $deficit,
                        null,
                        "Auto-repackaged during reconciliation to resolve negative variant stock (Batch #{$negBatch->batch_no})",
                        $negBatch
                    );
                    if ($repackagedBatch) {
                        $discrepanciesFixed[] = "Auto-repackaged raw stock to cover deficit of {$deficit} units for variant ID {$negBatch->product_variant_id} in warehouse {$negBatch->warehouse_id}.";
                    }
                }
            }

            // 0. Auto-assign orphan InventoryTransactions where batch_id is null to appropriate batches
            $orphanGroups = InventoryTransaction::whereNull('batch_id')
                ->whereNotNull('warehouse_id')
                ->select('warehouse_id', 'product_id', 'product_variant_id')
                ->groupBy('warehouse_id', 'product_id', 'product_variant_id')
                ->get();

            foreach ($orphanGroups as $orphan) {
                $batch = Batch::where('warehouse_id', $orphan->warehouse_id)
                    ->where('product_id', $orphan->product_id)
                    ->when($orphan->product_variant_id, function ($q) use ($orphan) {
                        $q->where('product_variant_id', $orphan->product_variant_id);
                    }, function ($q) {
                        $q->whereNull('product_variant_id');
                    })
                    ->first();

                if (!$batch) {
                    $batchNo = 'B-MIG-' . $orphan->warehouse_id . '-' . $orphan->product_id . ($orphan->product_variant_id ? '-V' . $orphan->product_variant_id : '');
                    $batch = Batch::create([
                        'batch_no' => $batchNo,
                        'product_id' => $orphan->product_id,
                        'product_variant_id' => $orphan->product_variant_id,
                        'warehouse_id' => $orphan->warehouse_id,
                        'qty_in' => 0,
                        'qty_out' => 0,
                        'remaining_qty' => 0,
                        'cost_per_unit' => 0,
                    ]);
                }

                InventoryTransaction::whereNull('batch_id')
                    ->where('warehouse_id', $orphan->warehouse_id)
                    ->where('product_id', $orphan->product_id)
                    ->when($orphan->product_variant_id, function ($q) use ($orphan) {
                        $q->where('product_variant_id', $orphan->product_variant_id);
                    }, function ($q) {
                        $q->whereNull('product_variant_id');
                    })
                    ->update(['batch_id' => $batch->id]);
            }

            // 1. Reconcile Batches from InventoryTransactions
            $batches = Batch::all();
            foreach ($batches as $batch) {
                $sums = InventoryTransaction::where('batch_id', $batch->id)
                    ->selectRaw('COALESCE(SUM(qty_in), 0) as total_in, COALESCE(SUM(qty_out), 0) as total_out')
                    ->first();

                $totalIn = (float) ($sums->total_in ?? 0);
                $totalOut = (float) ($sums->total_out ?? 0);

                // If batch was created via purchase/repack without an initial InventoryTransaction (fallback check), keep original qty_in if higher
                if ($totalIn == 0 && $batch->qty_in > 0) {
                    $totalIn = (float) $batch->qty_in;
                }

                $calculatedRemaining = $totalIn - $totalOut;

                if (
                    abs((float)$batch->qty_in - $totalIn) > 0.0001 ||
                    abs((float)$batch->qty_out - $totalOut) > 0.0001 ||
                    abs((float)$batch->remaining_qty - $calculatedRemaining) > 0.0001
                ) {
                    $discrepanciesFixed[] = "Batch #{$batch->batch_no} (ID: {$batch->id}): Old Remaining: {$batch->remaining_qty} -> New Remaining: {$calculatedRemaining}";
                    
                    $batch->qty_in = $totalIn;
                    $batch->qty_out = $totalOut;
                    $batch->remaining_qty = $calculatedRemaining;
                    $batch->save();
                    $batchUpdatesCount++;
                }
            }

            // 2. Reconcile WarehouseStock cache table
            WarehouseStock::query()->delete();

            $groupedTxns = InventoryTransaction::select(
                    'warehouse_id',
                    'product_id',
                    'product_variant_id',
                    DB::raw('COALESCE(SUM(qty_in), 0) - COALESCE(SUM(qty_out), 0) as net_stock')
                )
                ->whereNotNull('warehouse_id')
                ->groupBy('warehouse_id', 'product_id', 'product_variant_id')
                ->get();

            foreach ($groupedTxns as $grp) {
                $netStock = (float) $grp->net_stock;
                if ($netStock != 0) {
                    WarehouseStock::create([
                        'warehouse_id' => $grp->warehouse_id,
                        'product_id' => $grp->product_id,
                        'product_variant_id' => $grp->product_variant_id,
                        'stock' => $netStock,
                    ]);
                    $warehouseStockCount++;
                }
            }

            // 3. Reconcile ProductVariant current_stock
            $variants = ProductVariant::all();
            foreach ($variants as $variant) {
                $netVariantStock = InventoryTransaction::where('product_variant_id', $variant->id)
                    ->selectRaw('COALESCE(SUM(qty_in), 0) - COALESCE(SUM(qty_out), 0) as net_stock')
                    ->value('net_stock') ?? 0;

                $netVariantStock = max(0, (float) $netVariantStock);

                if (abs((float)$variant->current_stock - $netVariantStock) > 0.0001) {
                    $variant->current_stock = $netVariantStock;
                    $variant->save();
                    $variantUpdatesCount++;
                }
            }

            return [
                'batches_reconciled' => $batchUpdatesCount,
                'warehouse_stocks_synced' => $warehouseStockCount,
                'variants_synced' => $variantUpdatesCount,
                'discrepancies' => $discrepanciesFixed,
            ];
        });
    }

    /**
     * Detect discrepancies between Batch remaining_qty, WarehouseStock, Variant stock, and Ledger.
     */
    public static function detectDiscrepancies(): array
    {
        $discrepancies = [];

        // Check Batch mismatches
        $batches = Batch::with(['product', 'productVariant', 'warehouse'])->get();
        foreach ($batches as $batch) {
            $ledgerNet = InventoryTransaction::where('batch_id', $batch->id)
                ->selectRaw('COALESCE(SUM(qty_in), 0) - COALESCE(SUM(qty_out), 0) as net_qty')
                ->value('net_qty') ?? 0;

            if (abs((float)$batch->remaining_qty - (float)$ledgerNet) > 0.0001) {
                $itemLabel = $batch->productVariant ? $batch->productVariant->name : ($batch->product->name ?? 'Product');
                $discrepancies[] = [
                    'type' => 'Batch Mismatch',
                    'reference' => "Batch #{$batch->batch_no} ({$itemLabel})",
                    'warehouse' => $batch->warehouse->name ?? 'N/A',
                    'system_val' => (float)$batch->remaining_qty,
                    'ledger_val' => (float)$ledgerNet,
                    'difference' => (float)$batch->remaining_qty - (float)$ledgerNet,
                ];
            }
        }

        // Check Variant stock mismatches
        $variants = ProductVariant::with('product')->get();
        foreach ($variants as $variant) {
            $ledgerNet = InventoryTransaction::where('product_variant_id', $variant->id)
                ->selectRaw('COALESCE(SUM(qty_in), 0) - COALESCE(SUM(qty_out), 0) as net_qty')
                ->value('net_qty') ?? 0;

            $expectedStock = max(0, (float)$ledgerNet);

            if (abs((float)$variant->current_stock - $expectedStock) > 0.0001) {
                $discrepancies[] = [
                    'type' => 'Variant Total Stock Mismatch',
                    'reference' => "Variant: {$variant->name} ({$variant->product->name})",
                    'warehouse' => 'Global',
                    'system_val' => (float)$variant->current_stock,
                    'ledger_val' => $expectedStock,
                    'difference' => (float)$variant->current_stock - $expectedStock,
                ];
            }
        }

        return $discrepancies;
    }

    /**
     * Process physical stock take audit and auto-adjust stock to match physical count.
     */
    public static function processPhysicalStockAudit(int $warehouseId, array $items, ?string $notes = null, ?int $userId = null): array
    {
        return DB::transaction(function () use ($warehouseId, $items, $notes, $userId) {
            $adjustmentsCreated = 0;
            $creatorId = $userId ?? (auth()->id() ?? 1);

            foreach ($items as $item) {
                $batchId = $item['batch_id'] ?? null;
                $productId = $item['product_id'] ?? null;
                $variantId = $item['product_variant_id'] ?? null;
                $physicalQty = (float) ($item['physical_qty'] ?? 0);

                $currentSystemQty = 0;
                $batch = null;

                if ($batchId) {
                    $batch = Batch::find($batchId);
                    if ($batch) {
                        $currentSystemQty = (float) $batch->remaining_qty;
                        $productId = $batch->product_id;
                        $variantId = $batch->product_variant_id;
                    }
                } else {
                    // Look up by warehouse stock
                    $whStock = WarehouseStock::where('warehouse_id', $warehouseId)
                        ->where('product_id', $productId)
                        ->when($variantId, function ($q) use ($variantId) {
                            $q->where('product_variant_id', $variantId);
                        }, function ($q) {
                            $q->whereNull('product_variant_id');
                        })->first();

                    $currentSystemQty = $whStock ? (float) $whStock->stock : 0;

                    // Find latest batch for this item in warehouse or create audit batch if missing
                    $batch = Batch::where('warehouse_id', $warehouseId)
                        ->where('product_id', $productId)
                        ->when($variantId, function ($q) use ($variantId) {
                            $q->where('product_variant_id', $variantId);
                        }, function ($q) {
                            $q->whereNull('product_variant_id');
                        })->latest()->first();
                }

                $diff = $physicalQty - $currentSystemQty;
                if (abs($diff) < 0.0001) {
                    continue; // Stock matches physical count perfectly
                }

                if (!$batch) {
                    // Create fallback batch if none exists
                    $batch = Batch::create([
                        'batch_no' => 'B-AUDIT-' . time() . '-' . rand(100, 999),
                        'product_id' => $productId,
                        'product_variant_id' => $variantId,
                        'warehouse_id' => $warehouseId,
                        'qty_in' => max(0, $diff),
                        'qty_out' => 0,
                        'remaining_qty' => max(0, $diff),
                        'cost_per_unit' => 0,
                    ]);
                }

                $adjType = $diff > 0 ? 'add' : 'remove';
                $adjQty = abs($diff);

                // Update Batch
                if ($adjType === 'add') {
                    $batch->qty_in += $adjQty;
                    $batch->remaining_qty += $adjQty;
                } else {
                    $batch->qty_out += $adjQty;
                    $batch->remaining_qty = max(0, $batch->remaining_qty - $adjQty);
                }
                $batch->save();

                // Create Stock Adjustment
                $adjustment = StockAdjustment::create([
                    'warehouse_id' => $warehouseId,
                    'product_id' => $productId,
                    'product_variant_id' => $variantId,
                    'batch_id' => $batch->id,
                    'type' => $adjType,
                    'qty' => $adjQty,
                    'reason' => 'Physical Stock Take Reconciliation: ' . ($notes ?? 'Count adjustment'),
                    'status' => 'approved',
                    'created_by' => $creatorId,
                    'approved_by' => $creatorId,
                ]);

                // Create Inventory Transaction
                InventoryTransaction::create([
                    'warehouse_id' => $warehouseId,
                    'product_id' => $productId,
                    'product_variant_id' => $variantId,
                    'batch_id' => $batch->id,
                    'type' => 'adjustment',
                    'qty_in' => $adjType === 'add' ? $adjQty : 0,
                    'qty_out' => $adjType === 'remove' ? $adjQty : 0,
                    'cost' => $batch->cost_per_unit * $adjQty,
                    'reference_type' => StockAdjustment::class,
                    'reference_id' => $adjustment->id,
                    'date' => now(),
                    'created_by' => $creatorId,
                ]);

                $adjustmentsCreated++;
            }

            // Re-run ledger sync to ensure complete consistency
            self::reconcileLedgerAndBatches();

            return [
                'adjustments_created' => $adjustmentsCreated,
                'status' => 'success',
            ];
        });
    }

    /**
     * Process stock consumption for any delivered/migrated sales missing inventory transactions.
     */
    public static function processUnconsumedDeliveredSales(): int
    {
        $unconsumedSales = Sale::whereIn('delivery_status', ['delivered', 'dispatched'])
            ->whereNotIn('id', function ($q) {
                $q->select('reference_id')->from('inventory_transactions')->where('reference_type', Sale::class);
            })->get();

        $consumedCount = 0;
        foreach ($unconsumedSales as $sale) {
            DB::transaction(function () use ($sale) {
                $items = SaleItem::where('sale_id', $sale->id)->get();
                $groupedItems = [];
                foreach ($items as $item) {
                    $vid = $item->product_variant_id;
                    if (!isset($groupedItems[$vid])) {
                        $groupedItems[$vid] = [
                            'qty' => 0,
                            'unit_price' => $item->unit_price,
                            'total_weight' => 0,
                            'total_price' => 0,
                        ];
                    }
                    $groupedItems[$vid]['qty'] += (float)$item->qty;
                    $groupedItems[$vid]['total_weight'] += (float)$item->total_weight;
                    $groupedItems[$vid]['total_price'] += (float)$item->total_price;
                }

                // Consolidate sale items into single row per variant (never split into batch rows)
                SaleItem::where('sale_id', $sale->id)->delete();
                foreach ($groupedItems as $variantId => $data) {
                    SaleItem::create([
                        'sale_id' => $sale->id,
                        'product_variant_id' => $variantId,
                        'batch_id' => null,
                        'qty' => $data['qty'],
                        'unit_price' => $data['unit_price'],
                        'total_price' => $data['total_price'],
                        'total_weight' => $data['total_weight'],
                    ]);
                }

                foreach ($groupedItems as $variantId => $data) {
                    $itemQty = $data['qty'];
                    $variant = ProductVariant::find($variantId);

                    $batches = Batch::where('product_variant_id', $variantId)
                        ->where('warehouse_id', $sale->warehouse_id)
                        ->where('remaining_qty', '>', 0)
                        ->orderBy('id', 'asc')
                        ->get();

                    $remainingToConsume = $itemQty;

                    foreach ($batches as $batch) {
                        if ($remainingToConsume <= 0) break;

                        $takeQty = min($batch->remaining_qty, $remainingToConsume);
                        $cogsForThisTake = $takeQty * $batch->cost_per_unit;

                        $batch->qty_out += $takeQty;
                        $batch->remaining_qty -= $takeQty;
                        $batch->save();

                        $remainingToConsume -= $takeQty;

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
                            'created_by' => auth()->id() ?? 1,
                        ]);
                    }

                    if (round($remainingToConsume, 4) > 0) {
                        // Attempt auto-repackaging from raw product stock
                        $autoBatch = self::autoRepackageRawToVariant(
                            $sale->warehouse_id,
                            $variantId,
                            $remainingToConsume,
                            $sale->dispatched_at ?? $sale->date,
                            'Auto-repackaged during stock reconciliation for Sale #' . ($sale->invoice_no ?? $sale->id)
                        );

                        if ($autoBatch && $autoBatch->remaining_qty > 0) {
                            $takeQty = min((float)$autoBatch->remaining_qty, $remainingToConsume);
                            $cogsForThisTake = $takeQty * $autoBatch->cost_per_unit;

                            $autoBatch->qty_out += $takeQty;
                            $autoBatch->remaining_qty -= $takeQty;
                            $autoBatch->save();

                            $remainingToConsume -= $takeQty;

                            InventoryTransaction::create([
                                'warehouse_id' => $sale->warehouse_id,
                                'product_id' => $autoBatch->product_id,
                                'product_variant_id' => $variantId,
                                'batch_id' => $autoBatch->id,
                                'type' => 'sale',
                                'qty_in' => 0,
                                'qty_out' => $takeQty,
                                'cost' => $cogsForThisTake,
                                'reference_type' => Sale::class,
                                'reference_id' => $sale->id,
                                'date' => $sale->dispatched_at ?? $sale->date,
                                'created_by' => auth()->id() ?? 1,
                            ]);
                        }
                    }

                    if (round($remainingToConsume, 4) > 0) {
                        $batch = Batch::where('product_variant_id', $variantId)
                            ->where('warehouse_id', $sale->warehouse_id)
                            ->latest()
                            ->first();

                        if (!$batch) {
                            $productId = $variant ? $variant->product_id : 1;
                            $batch = Batch::create([
                                'batch_no' => 'B-POS-' . $sale->id . '-' . $variantId,
                                'product_id' => $productId,
                                'product_variant_id' => $variantId,
                                'warehouse_id' => $sale->warehouse_id,
                                'qty_in' => 0,
                                'qty_out' => 0,
                                'remaining_qty' => 0,
                                'cost_per_unit' => 0,
                            ]);
                        }

                        $takeQty = $remainingToConsume;
                        $cogsForThisTake = $takeQty * $batch->cost_per_unit;

                        $batch->qty_out += $takeQty;
                        $batch->remaining_qty -= $takeQty;
                        $batch->save();

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
                            'created_by' => auth()->id() ?? 1,
                        ]);
                    }
                }
            });
            $consumedCount++;
        }
        return $consumedCount;
    }

    /**
     * Auto-repackage raw product stock into variant stock when variant stock is short.
     * Returns the new Batch created for the repackaged variant stock, or null if no raw stock was available.
     */
    public static function autoRepackageRawToVariant(int $warehouseId, int $variantId, float $neededVariantQty, ?string $date = null, ?string $notes = null, ?Batch $targetBatch = null): ?Batch
    {
        if ($neededVariantQty <= 0) {
            return null;
        }

        $variant = ProductVariant::find($variantId);
        if (!$variant) {
            return null;
        }

        $productId = $variant->product_id;
        $unitQty = $variant->getBaseQuantity();
        if ($unitQty <= 0) {
            $unitQty = 1;
        }

        $rawWeightNeeded = $neededVariantQty * $unitQty;

        // Find raw product batches (product_variant_id IS NULL) with remaining stock in FIFO order
        $rawBatches = Batch::where('product_id', $productId)
            ->whereNull('product_variant_id')
            ->where('warehouse_id', $warehouseId)
            ->where('remaining_qty', '>', 0)
            ->orderBy('id', 'asc')
            ->get();

        if ($rawBatches->isEmpty()) {
            return null;
        }

        $totalRawCost = 0;
        $remainingRawToConsume = $rawWeightNeeded;
        $consumedRawBatches = [];

        foreach ($rawBatches as $rawBatch) {
            if ($remainingRawToConsume <= 0) break;

            $takeQty = min((float)$rawBatch->remaining_qty, $remainingRawToConsume);
            $costForTake = $takeQty * (float)$rawBatch->cost_per_unit;

            $rawBatch->qty_out += $takeQty;
            $rawBatch->remaining_qty -= $takeQty;
            $rawBatch->save();

            $totalRawCost += $costForTake;
            $remainingRawToConsume -= $takeQty;

            $consumedRawBatches[] = [
                'batch_id' => $rawBatch->id,
                'qty_used' => $takeQty,
                'cost' => $costForTake,
            ];
        }

        $actualRawConsumed = $rawWeightNeeded - $remainingRawToConsume;
        if ($actualRawConsumed <= 0) {
            return null;
        }

        $producedVariantQty = $actualRawConsumed / $unitQty;
        $unitCost = $producedVariantQty > 0 ? ($totalRawCost / $producedVariantQty) : 0;
        $repackDate = $date ?? date('Y-m-d');

        // Create Repackaging Order
        $order = RepackagingOrder::create([
            'ref_no' => 'RPK-AUTO-' . strtoupper(\Illuminate\Support\Str::random(8)),
            'warehouse_id' => $warehouseId,
            'date' => $repackDate,
            'created_by' => auth()->id() ?? 1,
            'notes' => $notes ?? 'Auto-repackaged raw product stock to resolve variant shortage',
        ]);

        // Create Repackaging Inputs & Transactions
        foreach ($consumedRawBatches as $consumed) {
            RepackagingInput::create([
                'repackaging_order_id' => $order->id,
                'batch_id' => $consumed['batch_id'],
                'product_id' => $productId,
                'product_variant_id' => null,
                'qty_used' => $consumed['qty_used'],
            ]);

            InventoryTransaction::create([
                'warehouse_id' => $warehouseId,
                'product_id' => $productId,
                'product_variant_id' => null,
                'batch_id' => $consumed['batch_id'],
                'type' => 'repack_input',
                'qty_in' => 0,
                'qty_out' => $consumed['qty_used'],
                'cost' => $consumed['cost'],
                'reference_type' => RepackagingOrder::class,
                'reference_id' => $order->id,
                'date' => $repackDate,
                'created_by' => auth()->id() ?? 1,
            ]);
        }

        // Create Repackaging Output
        RepackagingOutput::create([
            'repackaging_order_id' => $order->id,
            'product_id' => $productId,
            'product_variant_id' => $variantId,
            'warehouse_id' => $warehouseId,
            'qty_produced' => $producedVariantQty,
            'unit_cost' => $unitCost,
            'total_cost' => $totalRawCost,
        ]);

        if ($targetBatch) {
            $outputBatch = $targetBatch;
            $outputBatch->qty_in += $producedVariantQty;
            $outputBatch->remaining_qty += $producedVariantQty;
            if ($unitCost > 0) {
                $outputBatch->cost_per_unit = $unitCost;
            }
            $outputBatch->save();
        } else {
            // Create new Variant Batch
            $outputBatch = Batch::create([
                'batch_no' => 'B-RPK-AUTO-' . $order->id . '-' . $variantId . '-' . rand(100, 999),
                'product_id' => $productId,
                'product_variant_id' => $variantId,
                'warehouse_id' => $warehouseId,
                'purchase_id' => null,
                'qty_in' => $producedVariantQty,
                'qty_out' => 0,
                'remaining_qty' => $producedVariantQty,
                'cost_per_unit' => $unitCost,
                'expiry_date' => null,
            ]);
        }

        // Inventory Transaction for Output
        InventoryTransaction::create([
            'warehouse_id' => $warehouseId,
            'product_id' => $productId,
            'product_variant_id' => $variantId,
            'batch_id' => $outputBatch->id,
            'type' => 'repack_output',
            'qty_in' => $producedVariantQty,
            'qty_out' => 0,
            'cost' => $totalRawCost,
            'reference_type' => RepackagingOrder::class,
            'reference_id' => $order->id,
            'date' => $repackDate,
            'created_by' => auth()->id() ?? 1,
        ]);

        // Accounting Entries
        $inventoryRawAcc = ChartOfAccount::firstOrCreate(['name' => 'Inventory (Raw)', 'type' => 'asset']);
        $inventoryFinAcc = ChartOfAccount::firstOrCreate(['name' => 'Inventory (Finished)', 'type' => 'asset']);

        $journal = Journal::create([
            'journal_no' => 'JNL-' . strtoupper(Str::random(6)),
            'date' => $repackDate,
            'reference_type' => RepackagingOrder::class,
            'reference_id' => $order->id,
            'notes' => 'Auto-repackaging ' . $order->ref_no,
            'created_by' => auth()->id() ?? 1,
        ]);

        JournalEntry::create([
            'journal_id' => $journal->id,
            'account_id' => $inventoryFinAcc->id,
            'type' => 'debit',
            'amount' => $totalRawCost,
        ]);

        JournalEntry::create([
            'journal_id' => $journal->id,
            'account_id' => $inventoryRawAcc->id,
            'type' => 'credit',
            'amount' => $totalRawCost,
        ]);

        return $outputBatch;
    }
}
