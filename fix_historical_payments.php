<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Sale;
use App\Models\Journal;
use App\Models\JournalEntry;
use App\Models\ChartOfAccount;
use Illuminate\Support\Facades\DB;

$sales = Sale::where('paid_amount', '>', 0)->get();
$arAcc = ChartOfAccount::where('name', 'Accounts Receivable')->first();
$cashAcc = ChartOfAccount::firstOrCreate(['name' => 'Cash', 'type' => 'asset']);

$count = 0;
foreach($sales as $sale) {
    // Find the journal for this sale
    $journal = Journal::where('reference_type', Sale::class)->where('reference_id', $sale->id)->first();
    if ($journal && $arAcc) {
        // Check if there is already a credit entry to AR for this journal (which means payment was recorded)
        $hasPaymentCredit = JournalEntry::where('journal_id', $journal->id)
                                        ->where('account_id', $arAcc->id)
                                        ->where('type', 'credit')
                                        ->exists();
        if (!$hasPaymentCredit) {
            // Add the missing payment entries
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
            $count++;
        }
    }
}
echo "Fixed $count journals that were missing payment entries.\n";
