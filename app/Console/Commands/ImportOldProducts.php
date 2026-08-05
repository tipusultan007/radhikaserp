<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\Unit;

class ImportOldProducts extends Command
{
    protected $signature = 'import:old-products';
    protected $description = 'Import all products, package variants, and batches from old project database';

    public function handle()
    {
        $this->info('Starting products, variants, and batches migration...');

        try {
            $oldDb = DB::connection('old_mysql');
            $oldDb->getPdo();
        } catch (\Exception $e) {
            $this->error('Failed to connect to old database: ' . $e->getMessage());
            return 1;
        }

        // Ensure default unit 'Kg' exists
        $unit = Unit::firstOrCreate(
            ['name' => 'Kg'],
            ['short_name' => 'kg', 'multiplier' => 1.00, 'status' => 1]
        );

        // 1. Import Products
        $oldProducts = $oldDb->table('products')->orderBy('id', 'asc')->get();
        $this->info('Found ' . count($oldProducts) . ' products in old database.');

        foreach ($oldProducts as $oldProd) {
            DB::table('products')->updateOrInsert(
                ['id' => $oldProd->id],
                [
                    'name' => $oldProd->name,
                    'sku' => $oldProd->sku ?? ('PROD-' . $oldProd->id),
                    'type' => 'finished',
                    'unit_id' => $unit->id,
                    'status' => 1,
                    'created_at' => $oldProd->created_at ?? now(),
                    'updated_at' => $oldProd->updated_at ?? now(),
                ]
            );
        }

        // Adjust auto increment on products table
        $maxProdId = DB::table('products')->max('id') ?? 0;
        DB::statement("ALTER TABLE products AUTO_INCREMENT = " . ($maxProdId + 1));

        // 2. Import Packages as Product Variants
        $oldPackages = $oldDb->table('packages')->get();
        $oldPriceLists = $oldDb->table('package_price_lists')->get()->groupBy('package_id');

        $variantCount = 0;
        foreach ($oldPackages as $pkg) {
            $prices = $oldPriceLists->get($pkg->id);
            $latestPrice = $prices ? $prices->last() : null;

            $unitPrice = $latestPrice ? (float)$latestPrice->unit_price : 0.00;
            $dealerPrice = $latestPrice ? (float)$latestPrice->dealer_price : 0.00;

            $variantName = number_format((float)$pkg->size, 2) . ' Kg';
            $sku = 'VAR-P' . $pkg->product_id . '-PKG' . $pkg->id;

            DB::table('product_variants')->updateOrInsert(
                ['id' => $pkg->id],
                [
                    'product_id' => $pkg->product_id,
                    'name' => $variantName,
                    'sku' => $sku,
                    'barcode' => null,
                    'unit_qty' => $pkg->size,
                    'unit_id' => $unit->id,
                    'price' => $unitPrice,
                    'dealer_price' => $dealerPrice,
                    'special_dealer_price' => $dealerPrice,
                    'status' => 1,
                    'created_at' => $pkg->created_at ?? now(),
                    'updated_at' => $pkg->updated_at ?? now(),
                ]
            );
            $variantCount++;
        }

        // Ensure products without packages have at least 1 default variant
        foreach ($oldProducts as $oldProd) {
            $hasVariant = DB::table('product_variants')->where('product_id', $oldProd->id)->exists();
            if (!$hasVariant) {
                $nextVarId = (DB::table('product_variants')->max('id') ?? 0) + 1;
                DB::table('product_variants')->insert([
                    'id' => $nextVarId,
                    'product_id' => $oldProd->id,
                    'name' => '1.00 Kg (Default)',
                    'sku' => 'VAR-P' . $oldProd->id . '-DEF',
                    'unit_qty' => 1.00,
                    'unit_id' => $unit->id,
                    'price' => 0.00,
                    'dealer_price' => 0.00,
                    'special_dealer_price' => 0.00,
                    'status' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $variantCount++;
            }
        }

        $maxVarId = DB::table('product_variants')->max('id') ?? 0;
        DB::statement("ALTER TABLE product_variants AUTO_INCREMENT = " . ($maxVarId + 1));

        // 3. Import Batches
        $oldBatches = $oldDb->table('batches')->get();
        $this->info('Found ' . count($oldBatches) . ' batches in old database.');

        foreach ($oldBatches as $b) {
            $firstVariant = DB::table('product_variants')->where('product_id', $b->product_id)->first();
            $varId = $firstVariant ? $firstVariant->id : null;

            DB::table('batches')->updateOrInsert(
                ['id' => $b->id],
                [
                    'batch_no' => $b->batch_code ?? ('BATCH-' . $b->id),
                    'product_id' => $b->product_id,
                    'product_variant_id' => $varId,
                    'warehouse_id' => 1, // Default Storage warehouse
                    'purchase_id' => null,
                    'qty_in' => 0.00,
                    'qty_out' => 0.00,
                    'remaining_qty' => 0.00,
                    'cost_per_unit' => 0.00,
                    'expiry_date' => null,
                    'created_at' => $b->created_at ?? now(),
                    'updated_at' => $b->updated_at ?? now(),
                ]
            );
        }

        $maxBatchId = DB::table('batches')->max('id') ?? 0;
        DB::statement("ALTER TABLE batches AUTO_INCREMENT = " . ($maxBatchId + 1));

        $this->info("Products, Variants, and Batches migration completed successfully!");
        $this->table(
            ['Entity', 'Imported Count'],
            [
                ['Products', count($oldProducts)],
                ['Product Variants', $variantCount],
                ['Batches', count($oldBatches)],
            ]
        );

        return 0;
    }
}
