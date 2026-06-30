<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('designation')->nullable()->after('warehouse_id');
            $table->decimal('basic_salary', 12, 2)->default(0)->after('designation');
            $table->date('join_date')->nullable()->after('basic_salary');
            $table->text('notes')->nullable()->after('join_date');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['designation', 'basic_salary', 'join_date', 'notes']);
        });
    }
};
