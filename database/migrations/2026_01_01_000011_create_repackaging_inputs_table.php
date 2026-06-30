<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('repackaging_inputs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('repackaging_order_id')->constrained();
            $table->foreignId('batch_id')->constrained();
            $table->foreignId('product_id')->constrained();
            $table->decimal('qty_used', 12, 3);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('repackaging_inputs');
    }
};
