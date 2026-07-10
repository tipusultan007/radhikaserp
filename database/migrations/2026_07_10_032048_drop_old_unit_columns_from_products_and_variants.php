<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('base_unit');
        });
        Schema::table('product_variants', function (Blueprint $table) {
            $table->dropColumn('unit_type');
        });
    }

    public function down()
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('base_unit')->nullable();
        });
        Schema::table('product_variants', function (Blueprint $table) {
            $table->string('unit_type')->nullable();
        });
    }
};
