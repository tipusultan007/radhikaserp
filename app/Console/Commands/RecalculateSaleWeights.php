<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use App\Models\SaleItem;
use App\Models\Sale;

#[Signature('sales:recalculate-weights')]
#[Description('Recalculate total_weight for sale_items based on qty and product_variants unit_qty')]
class RecalculateSaleWeights extends Command
{
    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info("Recalculating sale_items total_weight...");

        $saleItems = SaleItem::with('productVariant')->get();
        $bar = $this->output->createProgressBar($saleItems->count());
        $bar->start();

        foreach ($saleItems as $item) {
            if ($item->productVariant) {
                $item->total_weight = $item->qty * $item->productVariant->unit_qty;
                $item->save();
            }
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info("sale_items weights recalculated successfully!");

        $this->info("Recalculating sales total_weight...");
        
        $sales = Sale::with('items')->get();
        $bar2 = $this->output->createProgressBar($sales->count());
        $bar2->start();

        foreach ($sales as $sale) {
            $sale->total_weight = $sale->items->sum('total_weight');
            $sale->save();
            $bar2->advance();
        }

        $bar2->finish();
        $this->newLine();
        $this->info('All sale weights recalculated successfully!');
    }
}
