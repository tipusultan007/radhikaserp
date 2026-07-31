<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('purchases', function (Blueprint $table) {
            $table->string('purchase_type', 20)->default('imported')->after('warehouse_id');
            $table->decimal('delivery_cost', 12, 2)->default(0)->after('total_cost');
            $table->decimal('total_landed_cost', 12, 2)->default(0)->after('delivery_cost');
            $table->json('cost_breakdown')->nullable()->after('total_landed_cost');
        });
    }

    public function down(): void
    {
        Schema::table('purchases', function (Blueprint $table) {
            $table->dropColumn(['purchase_type', 'delivery_cost', 'total_landed_cost', 'cost_breakdown']);
        });
    }
};
