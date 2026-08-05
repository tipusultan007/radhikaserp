<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Sale;
use App\Models\Journal;
use App\Models\JournalEntry;
use App\Models\ChartOfAccount;
use Illuminate\Support\Str;

$sales = Sale::all();
$arAcc = ChartOfAccount::where('name', 'Accounts Receivable')->first();
$salesAcc = ChartOfAccount::where('name', 'Sales Revenue')->first();
$adminId = \App\Models\User::first()->id ?? 1;

$count = 0;
foreach($sales as $sale) {
    $hasJournal = Journal::where('reference_type', Sale::class)->where('reference_id', $sale->id)->exists();
    if (!$hasJournal) {
        $journal = Journal::create([
            'journal_no' => 'JNL-' . strtoupper(Str::random(6)),
            'date' => $sale->date,
            'reference_type' => Sale::class,
            'reference_id' => $sale->id,
            'notes' => 'Customer App Order ' . $sale->invoice_no,
            'created_by' => $adminId,
        ]);

        if ($arAcc && $salesAcc) {
            JournalEntry::create([
                'journal_id' => $journal->id,
                'account_id' => $arAcc->id,
                'description' => 'Customer App Order ' . $sale->invoice_no,
                'type' => 'debit',
                'amount' => $sale->total,
            ]);

            JournalEntry::create([
                'journal_id' => $journal->id,
                'account_id' => $salesAcc->id,
                'description' => 'Customer App Order ' . $sale->invoice_no,
                'type' => 'credit',
                'amount' => $sale->total,
            ]);
            
            if ($sale->paid_amount > 0) {
                $cashAcc = ChartOfAccount::firstOrCreate(['name' => 'Cash', 'type' => 'asset']);
                JournalEntry::create([
                    'journal_id' => $journal->id,
                    'account_id' => $cashAcc->id,
                    'description' => 'Payment for ' . $sale->invoice_no,
                    'type' => 'debit',
                    'amount' => $sale->paid_amount,
                ]);
                JournalEntry::create([
                    'journal_id' => $journal->id,
                    'account_id' => $arAcc->id,
                    'description' => 'Payment for ' . $sale->invoice_no,
                    'type' => 'credit',
                    'amount' => $sale->paid_amount,
                ]);
            }
        }
        $count++;
    }
}
echo "Created $count missing journals for sales.\n";
