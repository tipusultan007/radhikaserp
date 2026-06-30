<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('repackaging_outputs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('repackaging_order_id')->constrained();
            $table->foreignId('product_variant_id')->constrained();
            $table->foreignId('warehouse_id')->constrained();

            $table->decimal('qty_produced', 12, 3);
            $table->decimal('unit_cost', 12, 2);
            $table->decimal('total_cost', 12, 2);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('repackaging_outputs');
    }
};
