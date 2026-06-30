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
        Schema::table('sales', function (Blueprint $table) {
            $table->boolean('is_promotional')->default(false)->after('due_amount');
            $table->decimal('delivery_charge', 15, 2)->default(0)->after('is_promotional');
            $table->foreignId('payment_method')->nullable()->constrained('chart_of_accounts')->nullOnDelete()->after('payment_status');
            $table->foreignId('dispatched_by')->nullable()->constrained('users')->nullOnDelete()->after('payment_method');
            $table->string('delivered_by')->nullable()->after('dispatched_by');
            $table->timestamp('dispatched_at')->nullable()->after('delivered_by');
            $table->timestamp('delivered_at')->nullable()->after('dispatched_at');
            $table->text('payment_details')->nullable()->after('delivered_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropForeign(['payment_method']);
            $table->dropForeign(['dispatched_by']);
            $table->dropColumn([
                'is_promotional', 'delivery_charge',  
                'payment_method', 'dispatched_by', 'delivered_by', 
                'dispatched_at', 'delivered_at', 'payment_details'
            ]);
        });
    }
};
