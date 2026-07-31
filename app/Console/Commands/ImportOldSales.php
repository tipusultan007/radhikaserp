<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportOldSales extends Command
{
    protected $signature = 'import:old-sales {--after=2026-05-20 : Filter sales created on or after date}';
    protected $description = 'Import sales, sale items, and payments (filtered after specified date) from old project database';

    public function handle()
    {
        $afterDate = $this->option('after');
        $this->info("Starting sales, sale items, and payments migration (after {$afterDate})...");

        try {
            $oldDb = DB::connection('old_mysql');
            $oldDb->getPdo();
        } catch (\Exception $e) {
            $this->error('Failed to connect to old database: ' . $e->getMessage());
            return 1;
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        // 1. Fetch sales >= afterDate
        $oldSales = $oldDb->table('sales')
            ->where('created_at', '>=', $afterDate . ' 00:00:00')
            ->orWhere('date', '>=', $afterDate)
            ->orderBy('id', 'asc')
            ->get();

        $this->info('Found ' . count($oldSales) . ' sales created on or after ' . $afterDate);

        $importedSalesCount = 0;
        $totalSalesAmount = 0;
        $saleIds = [];

        $bar = $this->output->createProgressBar(count($oldSales));
        $bar->start();

        foreach ($oldSales as $sale) {
            $saleIds[] = $sale->id;
            $saleDate = !empty($sale->date) ? date('Y-m-d', strtotime($sale->date)) : date('Y-m-d', strtotime($sale->created_at));

            $invoiceNo = $sale->invoice_no;
            if (empty($invoiceNo)) {
                $invoiceNo = 'INV-' . $sale->id;
            }

            // Ensure invoice_no is unique across different sales
            $exists = DB::table('sales')
                ->where('invoice_no', $invoiceNo)
                ->where('id', '!=', $sale->id)
                ->exists();

            if ($exists) {
                $invoiceNo = $invoiceNo . '-S' . $sale->id;
            }

            DB::table('sales')->updateOrInsert(
                ['id' => $sale->id],
                [
                    'invoice_no' => $invoiceNo,
                    'customer_id' => $sale->customer_id,
                    'warehouse_id' => $sale->warehouse_id ?? 1,
                    'date' => $saleDate,
                    'subtotal' => (float)($sale->total_amount ?? $sale->grand_total),
                    'discount' => (float)($sale->discount ?? 0.00),
                    'total' => (float)$sale->grand_total,
                    'total_weight' => 0.000,
                    'paid_amount' => (float)($sale->paid_amount ?? 0.00),
                    'due_amount' => (float)($sale->due_amount ?? 0.00),
                    'is_promotional' => $sale->is_promotional ?? 0,
                    'delivery_charge' => (float)($sale->delivery_charge ?? 0.00),
                    'payment_status' => strtolower($sale->payment_status ?? 'due'),
                    'created_by' => $sale->created_by ?? 1,
                    'source' => 'admin',
                    'delivery_status' => $sale->status ?? 'delivered',
                    'notes' => $sale->notes ?? null,
                    'created_at' => $sale->created_at ?? now(),
                    'updated_at' => $sale->updated_at ?? now(),
                ]
            );

            $importedSalesCount++;
            $totalSalesAmount += (float)$sale->grand_total;
            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        $maxSaleId = DB::table('sales')->max('id') ?? 0;
        DB::statement("ALTER TABLE sales AUTO_INCREMENT = " . ($maxSaleId + 1));

        // 2. Fetch and import sale items
        $importedItemsCount = 0;
        if (!empty($saleIds)) {
            $oldItems = $oldDb->table('sale_items')->whereIn('sale_id', $saleIds)->orderBy('id', 'asc')->get();
            $this->info('Found ' . count($oldItems) . ' sale items for imported sales.');

            foreach ($oldItems as $item) {
                // Find matching variant ID
                $varId = null;
                if (!empty($item->package_type_id)) {
                    $variant = DB::table('product_variants')->where('id', $item->package_type_id)->first();
                    if ($variant) {
                        $varId = $variant->id;
                    }
                }
                if (!$varId && !empty($item->product_id)) {
                    $variant = DB::table('product_variants')->where('product_id', $item->product_id)->first();
                    if ($variant) {
                        $varId = $variant->id;
                    }
                }

                if ($varId) {
                    DB::table('sale_items')->updateOrInsert(
                        ['id' => $item->id],
                        [
                            'sale_id' => $item->sale_id,
                            'product_variant_id' => $varId,
                            'batch_id' => $item->batch_id ?? null,
                            'qty' => (float)$item->quantity,
                            'total_weight' => 0.000,
                            'unit_price' => (float)$item->unit_price,
                            'total_price' => (float)$item->sub_total,
                            'created_at' => $item->created_at ?? now(),
                            'updated_at' => $item->updated_at ?? now(),
                        ]
                    );
                    $importedItemsCount++;
                }
            }

            $maxItemId = DB::table('sale_items')->max('id') ?? 0;
            DB::statement("ALTER TABLE sale_items AUTO_INCREMENT = " . ($maxItemId + 1));
        }

        // 3. Import payments >= afterDate
        $oldPayments = $oldDb->table('payments')
            ->where('created_at', '>=', $afterDate . ' 00:00:00')
            ->orWhere('date', '>=', $afterDate)
            ->get();

        $importedPaymentsCount = 0;
        foreach ($oldPayments as $pay) {
            $pDate = !empty($pay->date) ? date('Y-m-d', strtotime($pay->date)) : date('Y-m-d', strtotime($pay->created_at));
            
            $saleId = ($pay->paymentable_type === 'App\Models\Sale' || $pay->paymentable_type === 'Sale') ? $pay->paymentable_id : null;
            if ($saleId && in_array($saleId, $saleIds)) {
                DB::table('sale_payments')->updateOrInsert(
                    ['id' => $pay->id],
                    [
                        'sale_id' => $saleId,
                        'amount' => (float)$pay->amount,
                        'method' => $pay->payment_method ?? 'cash',
                        'date' => $pDate,
                        'reference' => $pay->notes ?? null,
                        'created_at' => $pay->created_at ?? now(),
                        'updated_at' => $pay->updated_at ?? now(),
                    ]
                );
                $importedPaymentsCount++;
            }
        }

        $maxPayId = DB::table('sale_payments')->max('id') ?? 0;
        DB::statement("ALTER TABLE sale_payments AUTO_INCREMENT = " . ($maxPayId + 1));

        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $this->info("Sales, Sale Items, and Payments migration completed successfully!");
        $this->table(
            ['Metric', 'Value'],
            [
                ['Sales Imported (>= ' . $afterDate . ')', $importedSalesCount],
                ['Sale Items Imported', $importedItemsCount],
                ['Sale Payments Imported', $importedPaymentsCount],
                ['Total Grand Total (BDT)', number_format($totalSalesAmount, 2)],
            ]
        );

        return 0;
    }
}
