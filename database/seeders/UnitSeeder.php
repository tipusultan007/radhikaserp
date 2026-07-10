<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Unit;
use App\Models\Product;
use App\Models\ProductVariant;

class UnitSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // 1. Create standard base units
        $standardUnits = [
            'kg' => 'Kilogram',
            'g' => 'Gram',
            'pcs' => 'Piece',
            'liter' => 'Liter',
            'ml' => 'Milliliter',
            'box' => 'Box',
            'packet' => 'Packet',
        ];

        $unitMap = [];

        foreach ($standardUnits as $short => $name) {
            $unitMap[$short] = Unit::firstOrCreate(
                ['short_name' => $short],
                ['name' => $name, 'multiplier' => 1]
            );
        }

        // 2. Migrate Products
        $products = Product::whereNull('unit_id')->get();
        foreach ($products as $product) {
            $baseUnitString = strtolower(trim($product->base_unit ?? ''));
            if (!empty($baseUnitString)) {
                if (!isset($unitMap[$baseUnitString])) {
                    $unitMap[$baseUnitString] = Unit::firstOrCreate(
                        ['short_name' => $baseUnitString],
                        ['name' => ucfirst($baseUnitString), 'multiplier' => 1]
                    );
                }
                $product->unit_id = $unitMap[$baseUnitString]->id;
                $product->save();
            }
        }

        // 3. Migrate Variants
        $variants = ProductVariant::whereNull('unit_id')->get();
        foreach ($variants as $variant) {
            $unitTypeString = strtolower(trim($variant->unit_type ?? ''));
            if (!empty($unitTypeString)) {
                if (!isset($unitMap[$unitTypeString])) {
                    $unitMap[$unitTypeString] = Unit::firstOrCreate(
                        ['short_name' => $unitTypeString],
                        ['name' => ucfirst($unitTypeString), 'multiplier' => 1]
                    );
                }
                $variant->unit_id = $unitMap[$unitTypeString]->id;
                $variant->save();
            }
        }
    }
}
