<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('repackaging_outputs', function (Blueprint $table) {
            $table->foreignId('product_id')->nullable()->after('repackaging_order_id')->constrained('products')->nullOnDelete();
            $table->foreignId('product_variant_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('repackaging_outputs', function (Blueprint $table) {
            $table->dropForeign(['product_id']);
            $table->dropColumn('product_id');
            $table->foreignId('product_variant_id')->nullable(false)->change();
        });
    }
};
