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
        Schema::create('customer_target_schemes', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('target_month', 7)->index(); // e.g. '2026-09'
            $table->date('start_date');
            $table->date('end_date');
            $table->text('description')->nullable();
            $table->enum('status', ['active', 'completed', 'cancelled'])->default('active');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('customer_target_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_target_scheme_id')->constrained('customer_target_schemes')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignId('product_variant_id')->nullable()->constrained('product_variants')->nullOnDelete();
            $table->decimal('target_qty', 12, 2)->default(0);
            $table->decimal('bonus_per_unit', 12, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('customer_bonuses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_target_scheme_id')->constrained('customer_target_schemes')->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->decimal('achieved_qty', 12, 2)->default(0);
            $table->decimal('target_qty', 12, 2)->default(0);
            $table->boolean('is_target_met')->default(false);
            $table->decimal('bonus_amount', 12, 2)->default(0);
            $table->enum('status', ['pending', 'approved', 'disbursed', 'rejected'])->default('pending');
            $table->timestamp('disbursed_at')->nullable();
            $table->foreignId('disbursed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('journal_id')->nullable()->constrained('journals')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['customer_target_scheme_id', 'customer_id'], 'cust_target_bonus_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customer_bonuses');
        Schema::dropIfExists('customer_target_items');
        Schema::dropIfExists('customer_target_schemes');
    }
};

