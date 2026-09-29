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
        Schema::table('customer_target_schemes', function (Blueprint $table) {
            $table->string('target_month', 20)->nullable()->change();
            $table->date('end_date')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customer_target_schemes', function (Blueprint $table) {
            $table->string('target_month', 7)->nullable(false)->change();
            $table->date('end_date')->nullable(false)->change();
        });
    }
};

