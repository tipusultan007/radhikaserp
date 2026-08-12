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
        $this->info('Starting comprehensive stock reconciliation (Batches, Raw Materials, Variants, Warehouse Stocks)...');

        $result = \App\Services\StockReconciliationService::reconcileLedgerAndBatches();

        $this->info("Reconciliation complete:");
        $this->line("- Batches Reconciled: {$result['batches_reconciled']}");
        $this->line("- Warehouse Stocks Synced: {$result['warehouse_stocks_synced']}");
        $this->line("- Variants Synced: {$result['variants_synced']}");

        if (!empty($result['discrepancies'])) {
            $this->warn("Corrected Discrepancies:");
            foreach ($result['discrepancies'] as $disc) {
                $this->line("  * {$disc}");
            }
        } else {
            $this->info("All stock tallies perfectly match transaction ledgers.");
        }
    }
}
