<?php

namespace App\Observers;

use App\Models\InventoryTransaction;

use App\Models\WarehouseStock;
use App\Models\ProductVariant;

class InventoryTransactionObserver
{
    /**
     * Handle the InventoryTransaction "created" event.
     */
    public function created(InventoryTransaction $inventoryTransaction): void
    {
        $this->updateStock($inventoryTransaction);
    }

    /**
     * Handle the InventoryTransaction "updated" event.
     */
    public function updated(InventoryTransaction $inventoryTransaction): void
    {
        // If qty changed or warehouse/variant changed, it's easier to revert original and apply new
        // We use getOriginal() to revert
        $original = new InventoryTransaction($inventoryTransaction->getOriginal());
        $this->updateStock($original, true);
        
        $this->updateStock($inventoryTransaction);
    }

    /**
     * Handle the InventoryTransaction "deleted" event.
     */
    public function deleted(InventoryTransaction $inventoryTransaction): void
    {
        $this->updateStock($inventoryTransaction, true);
    }

    private function updateStock(InventoryTransaction $transaction, $revert = false)
    {
        $netQty = $transaction->qty_in - $transaction->qty_out;
        if ($revert) {
            $netQty = -$netQty;
        }

        if ($netQty == 0) return;

        // Update warehouse stock
        if ($transaction->warehouse_id && $transaction->product_variant_id) {
            $warehouseStock = WarehouseStock::firstOrCreate(
                [
                    'warehouse_id' => $transaction->warehouse_id,
                    'product_variant_id' => $transaction->product_variant_id
                ],
                ['stock' => 0]
            );
            $warehouseStock->increment('stock', $netQty);
        }

        // Update global product variant stock
        if ($transaction->product_variant_id) {
            ProductVariant::where('id', $transaction->product_variant_id)->increment('current_stock', $netQty);
        }
    }
}
