<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('inventory_transactions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('warehouse_id')->constrained();
            $table->foreignId('product_id')->constrained();
            $table->foreignId('product_variant_id')->nullable()->constrained();
            $table->foreignId('batch_id')->nullable()->constrained();

            $table->enum('type', [
                'import',
                'repack_input',
                'repack_output',
                'sale',
                'return',
                'transfer_in',
                'transfer_out',
                'adjustment',
                'damage'
            ]);

            $table->decimal('qty_in', 12, 3)->default(0);
            $table->decimal('qty_out', 12, 3)->default(0);

            $table->decimal('cost', 12, 2)->default(0);

            $table->string('reference_type');
            $table->unsignedBigInteger('reference_id');

            $table->date('date');
            $table->foreignId('created_by')->constrained('users');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_transactions');
    }
};
