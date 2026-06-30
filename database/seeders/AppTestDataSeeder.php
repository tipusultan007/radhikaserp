<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Warehouse;
use App\Models\StockTransfer;
use App\Models\StockTransferItem;
use App\Models\RepackagingOrder;
use App\Models\RepackagingInput;
use App\Models\RepackagingOutput;
use App\Models\Batch;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Support\Str;

class AppTestDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function up(): void
    {
    }

    public function run(): void
    {
        // 1. Create a Warehouse if doesn't exist
        $warehouse1 = Warehouse::firstOrCreate(['name' => 'Main Warehouse'], [
            'code' => 'WH01'
        ]);

        $warehouse2 = Warehouse::firstOrCreate(['name' => 'Secondary Warehouse'], [
            'code' => 'WH02'
        ]);

        // 2. Create User if doesn't exist
        $user = User::firstOrCreate(['email' => 'admin@example.com'], [
            'name' => 'Admin User',
            'password' => bcrypt('password'),
            'warehouse_id' => $warehouse1->id
        ]);

        // 3. Create Products and Variants
        $products = [
            ['name' => 'Premium Rice', 'sku' => 'PRC01', 'type' => 'finished', 'base_unit' => 'KG'],
            ['name' => 'Lentils', 'sku' => 'LNT01', 'type' => 'finished', 'base_unit' => 'KG'],
            ['name' => 'Cooking Oil', 'sku' => 'OIL01', 'type' => 'finished', 'base_unit' => 'Liter'],
        ];

        foreach ($products as $pData) {
            $product = Product::firstOrCreate(['sku' => $pData['sku']], [
                'name' => $pData['name'],
                'type' => $pData['type'],
                'base_unit' => $pData['base_unit'],
                'status' => true,
            ]);

            // Create Variants
            ProductVariant::firstOrCreate(['sku' => $pData['sku'] . '-1KG'], [
                'product_id' => $product->id,
                'name' => $pData['name'] . ' 1 KG',
                'barcode' => Str::random(10),
                'unit_qty' => 1.00,
                'unit_type' => $pData['base_unit'],
                'price' => rand(10, 100),
                'status' => true,
            ]);

            ProductVariant::firstOrCreate(['sku' => $pData['sku'] . '-5KG'], [
                'product_id' => $product->id,
                'name' => $pData['name'] . ' 5 KG',
                'barcode' => Str::random(10),
                'unit_qty' => 5.00,
                'unit_type' => $pData['base_unit'],
                'price' => rand(40, 450),
                'status' => true,
            ]);
        }

        // 4. Create Dummy Shipments (Stock Transfers)
        $transfer = StockTransfer::firstOrCreate(['transfer_no' => 'TRF-' . time()], [
            'from_warehouse_id' => $warehouse1->id,
            'to_warehouse_id' => $warehouse2->id,
            'status' => 'sent',
            'created_by' => $user->id,
        ]);

        $variant = ProductVariant::first();
        if ($variant) {
            StockTransferItem::firstOrCreate([
                'stock_transfer_id' => $transfer->id,
                'product_variant_id' => $variant->id,
            ], [
                'qty' => 50,
            ]);
        }

        // 5. Create Dummy Repackaging
        $repackaging = RepackagingOrder::firstOrCreate(['ref_no' => 'RPK-' . time()], [
            'warehouse_id' => $warehouse1->id,
            'date' => now()->toDateString(),
            'created_by' => $user->id,
            'notes' => 'Bulk to small packs',
        ]);

        if ($variant) {
            // Need a batch for input
            $batch = Batch::firstOrCreate([
                'batch_no' => 'BCH-TEST',
            ], [
                'product_id' => $variant->product_id,
                'warehouse_id' => $warehouse1->id,
                'qty_in' => 100,
                'remaining_qty' => 100,
                'cost_per_unit' => 50,
            ]);

            // Dummy input
            RepackagingInput::firstOrCreate([
                'repackaging_order_id' => $repackaging->id,
                'product_id' => $variant->product_id,
            ], [
                'batch_id' => $batch->id,
                'qty_used' => 10,
            ]);

            // Dummy output
            RepackagingOutput::firstOrCreate([
                'repackaging_order_id' => $repackaging->id,
                'product_variant_id' => $variant->id, 
            ], [
                'warehouse_id' => $warehouse1->id,
                'qty_produced' => 10,
                'unit_cost' => $variant->price,
                'total_cost' => $variant->price * 10,
            ]);
        }
    }
}
