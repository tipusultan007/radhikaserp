<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Customer;
use App\Models\Sale;
use App\Models\Journal;
use App\Models\ChartOfAccount;

$customer = Customer::find(55); // the one mentioned by user
if(!$customer) $customer = Customer::first();

$journals = Journal::with(['entries', 'reference'])
    ->where(function($q) use ($customer) {
        $q->where('reference_type', Customer::class)->where('reference_id', $customer->id);
    })->orWhere(function($q) use ($customer) {
        $q->where('reference_type', Sale::class)->whereIn('reference_id', $customer->sales()->pluck('id'));
    })
    ->get()
    ->sortBy(function($journal) {
        return $journal->date . '_' . str_pad($journal->id, 10, '0', STR_PAD_LEFT);
    })->values();

$arAcc = ChartOfAccount::where('name', 'Accounts Receivable')->first();
$advAcc = ChartOfAccount::where('name', 'Customer Advance')->first();
$arId = $arAcc ? $arAcc->id : 0;
$advId = $advAcc ? $advAcc->id : 0;

$runningBalance = 0;
foreach ($journals as $journal) {
    $debit = 0;
    $credit = 0;

    if ($journal->reference_type == Sale::class) {
        $sale = $journal->reference;
        $debit = $sale->total;
        $credit = $sale->paid_amount;
    } else {
        if ($journal->notes == 'Opening Balance') {
            $debit = $customer->opening_balance;
        } else {
            $credit = $journal->entries->whereIn('account_id', [$arId, $advId])->where('type', 'credit')->sum('amount');
            $debit = $journal->entries->whereIn('account_id', [$arId, $advId])->where('type', 'debit')->sum('amount');
        }
    }

    if ($debit == 0 && $credit == 0) continue;

    $runningBalance += $debit;
    $runningBalance -= $credit;

    echo "JNL: {$journal->journal_no} | Note: {$journal->notes} | DR: $debit | CR: $credit | BAL: $runningBalance\n";
}
echo "Total Due DB: {$customer->total_due} | Wallet DB: {$customer->wallet_balance} | Net DB: " . ($customer->total_due - $customer->wallet_balance) . "\n";
