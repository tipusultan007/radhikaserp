<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('stock:sync')]
#[Description('Sync running stock balances for variants and warehouses')]
class SyncStockBalancesCommand extends Command
{
    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Syncing stock balances...');

        // Wipe existing running tallies
        \App\Models\WarehouseStock::truncate();
        \App\Models\ProductVariant::query()->update(['current_stock' => 0]);

        // Get all transactions
        $transactions = \App\Models\InventoryTransaction::all();

        $this->withProgressBar($transactions, function ($transaction) {
            $netQty = $transaction->qty_in - $transaction->qty_out;
            if ($netQty == 0) return;

            // Update warehouse stock
            if ($transaction->warehouse_id && $transaction->product_variant_id) {
                $warehouseStock = \App\Models\WarehouseStock::firstOrCreate(
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
                \App\Models\ProductVariant::where('id', $transaction->product_variant_id)->increment('current_stock', $netQty);
            }
        });

        $this->newLine();
        $this->info('Stock balances synced successfully.');
    }
}
