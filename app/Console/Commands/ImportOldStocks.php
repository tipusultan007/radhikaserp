<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportOldStocks extends Command
{
    protected $signature = 'import:old-stocks {--after=2026-05-20 : Filter stocks created on or after date}';
    protected $description = 'Import stock records and sync warehouse stocks (filtered after specified date) from old project database';

    public function handle()
    {
        $afterDate = $this->option('after');
        $this->info("Starting stocks and inventory transactions migration (after {$afterDate})...");

        try {
            $oldDb = DB::connection('old_mysql');
            $oldDb->getPdo();
        } catch (\Exception $e) {
            $this->error('Failed to connect to old database: ' . $e->getMessage());
            return 1;
        }

        $oldStocks = $oldDb->table('stocks')
            ->where('created_at', '>=', $afterDate . ' 00:00:00')
            ->orderBy('id', 'asc')
            ->get();

        $this->info('Found ' . count($oldStocks) . ' stock records created on or after ' . $afterDate);

        $importedStockCount = 0;
        foreach ($oldStocks as $stk) {
            $variant = DB::table('product_variants')->where('product_id', $stk->product_id)->first();
            $varId = $variant ? $variant->id : null;
            $stkDate = date('Y-m-d', strtotime($stk->created_at));

            DB::table('inventory_transactions')->updateOrInsert(
                [
                    'reference_type' => 'OldStock',
                    'reference_id' => $stk->id
                ],
                [
                    'warehouse_id' => $stk->warehouse_id ?? 1,
                    'product_id' => $stk->product_id,
                    'product_variant_id' => $varId,
                    'batch_id' => null,
                    'type' => 'adjustment',
                    'qty_in' => (float)$stk->quantity,
                    'qty_out' => 0.00,
                    'cost' => 0.00,
                    'date' => $stkDate,
                    'created_by' => 1,
                    'created_at' => $stk->created_at ?? now(),
                    'updated_at' => $stk->updated_at ?? now(),
                ]
            );

            if ($varId) {
                DB::table('warehouse_stocks')->updateOrInsert(
                    [
                        'warehouse_id' => $stk->warehouse_id ?? 1,
                        'product_variant_id' => $varId,
                    ],
                    [
                        'stock' => DB::raw('stock + ' . (float)$stk->quantity),
                        'updated_at' => now(),
                    ]
                );
            }

            $importedStockCount++;
        }

        $maxInvId = DB::table('inventory_transactions')->max('id') ?? 0;
        DB::statement("ALTER TABLE inventory_transactions AUTO_INCREMENT = " . ($maxInvId + 1));

        $this->info("Stocks migration completed successfully!");
        $this->table(
            ['Metric', 'Value'],
            [
                ['Stock Movements Imported (>= ' . $afterDate . ')', $importedStockCount],
            ]
        );

        return 0;
    }
}
