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
        Schema::table('sale_payments', function (Blueprint $table) {
            $table->foreignId('journal_id')->nullable()->after('reference')->constrained('journals')->nullOnDelete();
        });

        // Automatically backfill journal_id for existing payments
        foreach (\App\Models\SalePayment::with('sale')->whereNull('journal_id')->cursor() as $sp) {
            if (!$sp->sale) continue;
            $inv = $sp->sale->invoice_no;

            $journal = \App\Models\Journal::where('notes', 'Payment for POS Sale ' . $inv)->first()
                ?? \App\Models\Journal::where('notes', 'Payment for Sale ' . $inv)->first()
                ?? \App\Models\Journal::where('notes', 'LIKE', '%' . $inv . '%')->first();

            if (!$journal && $sp->sale->customer_id) {
                $journal = \App\Models\Journal::where('reference_type', \App\Models\Customer::class)
                    ->where('reference_id', $sp->sale->customer_id)
                    ->whereDate('date', $sp->date)
                    ->whereHas('entries', function($q) use ($sp) {
                        $q->where('amount', $sp->amount);
                    })->first();
            }

            if ($journal) {
                \Illuminate\Support\Facades\DB::table('sale_payments')->where('id', $sp->id)->update(['journal_id' => $journal->id]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sale_payments', function (Blueprint $table) {
            $table->dropForeign(['journal_id']);
            $table->dropColumn('journal_id');
        });
    }
};
