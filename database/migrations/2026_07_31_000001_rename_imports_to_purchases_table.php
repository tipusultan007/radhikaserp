<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        // 1. Rename imports table to purchases
        if (Schema::hasTable('imports') && !Schema::hasTable('purchases')) {
            Schema::rename('imports', 'purchases');
        }

        if (Schema::hasColumn('purchases', 'import_no')) {
            DB::statement("ALTER TABLE purchases CHANGE import_no purchase_no VARCHAR(255)");
        }

        // 2. Rename import_items table to purchase_items
        if (Schema::hasTable('import_items') && !Schema::hasTable('purchase_items')) {
            Schema::rename('import_items', 'purchase_items');
        }

        if (Schema::hasColumn('purchase_items', 'import_id')) {
            DB::statement("ALTER TABLE purchase_items CHANGE import_id purchase_id BIGINT UNSIGNED NOT NULL");
        }

        // 3. Rename batches.import_id to batches.purchase_id
        if (Schema::hasColumn('batches', 'import_id')) {
            DB::statement("ALTER TABLE batches CHANGE import_id purchase_id BIGINT UNSIGNED NULL");
        }

        // 4. Update inventory_transactions type enum & reference_type
        DB::statement("ALTER TABLE inventory_transactions MODIFY COLUMN type ENUM('import', 'purchase', 'repack_input', 'repack_output', 'sale', 'return', 'transfer_in', 'transfer_out', 'adjustment', 'damage') NOT NULL");
        DB::table('inventory_transactions')->where('type', 'import')->update(['type' => 'purchase']);
        DB::table('inventory_transactions')->where('reference_type', 'App\\Models\\Import')->update(['reference_type' => 'App\\Models\\Purchase']);

        // 5. Update journals reference_type & notes
        DB::table('journals')->where('reference_type', 'App\\Models\\Import')->update(['reference_type' => 'App\\Models\\Purchase']);
        DB::statement("UPDATE journals SET notes = REPLACE(notes, 'Import Shipment IMP-', 'Purchase Shipment PUR-') WHERE notes LIKE '%Import Shipment%'");
        DB::statement("UPDATE journals SET notes = REPLACE(notes, 'Import Shipment', 'Purchase Shipment') WHERE notes LIKE '%Import Shipment%'");

        // 6. Update Spatie permissions
        $permissions = [
            'view imports' => 'view purchases',
            'create imports' => 'create purchases',
            'edit imports' => 'edit purchases',
            'delete imports' => 'delete purchases',
            'manage imports' => 'manage purchases',
        ];

        foreach ($permissions as $oldPerm => $newPerm) {
            DB::table('permissions')->where('name', $oldPerm)->update(['name' => $newPerm]);
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }

    public function down(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        if (Schema::hasTable('purchases') && !Schema::hasTable('imports')) {
            Schema::rename('purchases', 'imports');
        }
        if (Schema::hasColumn('imports', 'purchase_no')) {
            DB::statement("ALTER TABLE imports CHANGE purchase_no import_no VARCHAR(255)");
        }

        if (Schema::hasTable('purchase_items') && !Schema::hasTable('import_items')) {
            Schema::rename('purchase_items', 'import_items');
        }
        if (Schema::hasColumn('import_items', 'purchase_id')) {
            DB::statement("ALTER TABLE import_items CHANGE purchase_id import_id BIGINT UNSIGNED NOT NULL");
        }

        if (Schema::hasColumn('batches', 'purchase_id')) {
            DB::statement("ALTER TABLE batches CHANGE purchase_id import_id BIGINT UNSIGNED NULL");
        }

        DB::table('inventory_transactions')->where('type', 'purchase')->update(['type' => 'import']);
        DB::table('inventory_transactions')->where('reference_type', 'App\\Models\\Purchase')->update(['reference_type' => 'App\\Models\\Import']);
        DB::table('journals')->where('reference_type', 'App\\Models\\Purchase')->update(['reference_type' => 'App\\Models\\Import']);

        $permissions = [
            'view purchases' => 'view imports',
            'create purchases' => 'create imports',
            'edit purchases' => 'edit imports',
            'delete purchases' => 'delete imports',
            'manage purchases' => 'manage imports',
        ];

        foreach ($permissions as $oldPerm => $newPerm) {
            DB::table('permissions')->where('name', $oldPerm)->update(['name' => $newPerm]);
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }
};
