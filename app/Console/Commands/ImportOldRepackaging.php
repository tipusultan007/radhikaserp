<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportOldRepackaging extends Command
{
    protected $signature = 'import:old-repackaging {--after=2026-05-20 : Filter repackagings created on or after date}';
    protected $description = 'Import repackaging operations (filtered after specified date) from old project database';

    public function handle()
    {
        $afterDate = $this->option('after');
        $this->info("Starting repackaging migration (after {$afterDate})...");

        try {
            $oldDb = DB::connection('old_mysql');
            $oldDb->getPdo();
        } catch (\Exception $e) {
            $this->error('Failed to connect to old database: ' . $e->getMessage());
            return 1;
        }

        $oldRepack = $oldDb->table('repackagings')
            ->where('created_at', '>=', $afterDate . ' 00:00:00')
            ->orWhere('date', '>=', $afterDate)
            ->orderBy('id', 'asc')
            ->get();

        $this->info('Found ' . count($oldRepack) . ' repackaging records created on or after ' . $afterDate);

        $importedRepackCount = 0;
        foreach ($oldRepack as $rep) {
            $repDate = !empty($rep->date) ? date('Y-m-d', strtotime($rep->date)) : date('Y-m-d', strtotime($rep->created_at));
            $refNo = 'REP-' . str_pad($rep->id, 5, '0', STR_PAD_LEFT);

            DB::table('repackaging_orders')->updateOrInsert(
                ['id' => $rep->id],
                [
                    'ref_no' => $refNo,
                    'warehouse_id' => $rep->warehouse_id ?? 1,
                    'date' => $repDate,
                    'created_by' => $rep->repackaged_by ?? 1,
                    'notes' => $rep->notes ?? '',
                    'created_at' => $rep->created_at ?? now(),
                    'updated_at' => $rep->updated_at ?? now(),
                ]
            );

            // Input (raw product used)
            $firstVariant = DB::table('product_variants')->where('product_id', $rep->product_id)->first();
            $batch = DB::table('batches')->where('product_id', $rep->product_id)->first();

            DB::table('repackaging_inputs')->updateOrInsert(
                [
                    'repackaging_order_id' => $rep->id,
                    'product_id' => $rep->product_id,
                ],
                [
                    'batch_id' => $batch ? $batch->id : 1,
                    'product_variant_id' => $firstVariant ? $firstVariant->id : null,
                    'qty_used' => (float)$rep->raw_weight,
                    'created_at' => $rep->created_at ?? now(),
                    'updated_at' => $rep->updated_at ?? now(),
                ]
            );

            // Output items
            $oldItems = $oldDb->table('repackaged_items')->where('repackaging_id', $rep->id)->get();
            foreach ($oldItems as $item) {
                DB::table('repackaging_outputs')->updateOrInsert(
                    ['id' => $item->id],
                    [
                        'repackaging_order_id' => $rep->id,
                        'product_id' => $rep->product_id,
                        'product_variant_id' => $firstVariant ? $firstVariant->id : null,
                        'warehouse_id' => $rep->warehouse_id ?? 1,
                        'qty_produced' => (float)($item->total_weight ?? $item->quantity),
                        'unit_cost' => 0.00,
                        'total_cost' => 0.00,
                        'created_at' => $item->created_at ?? now(),
                        'updated_at' => $item->updated_at ?? now(),
                    ]
                );
            }

            $importedRepackCount++;
        }

        $maxRepId = DB::table('repackaging_orders')->max('id') ?? 0;
        DB::statement("ALTER TABLE repackaging_orders AUTO_INCREMENT = " . ($maxRepId + 1));

        $this->info("Repackaging migration completed successfully!");
        $this->table(
            ['Metric', 'Value'],
            [
                ['Repackaging Orders Imported (>= ' . $afterDate . ')', $importedRepackCount],
            ]
        );

        return 0;
    }
}
