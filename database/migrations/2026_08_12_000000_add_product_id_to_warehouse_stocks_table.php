<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('warehouse_stocks', function (Blueprint $table) {
            // Create temporary index on warehouse_id so MySQL FK warehouse_stocks_warehouse_id_foreign remains satisfied
            $table->index('warehouse_id', 'temp_wh_idx');
        });

        Schema::table('warehouse_stocks', function (Blueprint $table) {
            // Drop old unique index
            $table->dropUnique('warehouse_stocks_warehouse_id_product_variant_id_unique');

            // Make product_variant_id nullable
            $table->foreignId('product_variant_id')->nullable()->change();

            // Add product_id column
            $table->foreignId('product_id')->nullable()->after('warehouse_id')->constrained()->cascadeOnDelete();

            // Add composite unique index
            $table->unique(['warehouse_id', 'product_id', 'product_variant_id'], 'warehouse_product_variant_unique');
        });

        Schema::table('warehouse_stocks', function (Blueprint $table) {
            // Drop temporary index
            $table->dropIndex('temp_wh_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('warehouse_stocks', function (Blueprint $table) {
            $table->index('warehouse_id', 'temp_wh_idx');
        });

        Schema::table('warehouse_stocks', function (Blueprint $table) {
            $table->dropUnique('warehouse_product_variant_unique');
            $table->dropForeign(['product_id']);
            $table->dropColumn('product_id');

            $table->foreignId('product_variant_id')->nullable(false)->change();
            $table->unique(['warehouse_id', 'product_variant_id'], 'warehouse_stocks_warehouse_id_product_variant_id_unique');
        });

        Schema::table('warehouse_stocks', function (Blueprint $table) {
            $table->dropIndex('temp_wh_idx');
        });
    }
};
