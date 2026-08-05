<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Customer;
use App\Models\JournalEntry;
use App\Models\ChartOfAccount;
use App\Models\Journal;
use App\Models\Sale;
use Illuminate\Support\Facades\DB;

$arAcc = ChartOfAccount::where('name', 'Accounts Receivable')->first();
$advAcc = ChartOfAccount::where('name', 'Customer Advance')->first();

if (!$arAcc || !$advAcc) {
    die("Required accounts not found.\n");
}

$customers = Customer::all();
$updated = 0;

foreach ($customers as $customer) {
    // We need to find all Journal IDs that belong to this customer
    $journalIds = Journal::where(function($q) use ($customer) {
        $q->where('reference_type', Customer::class)->where('reference_id', $customer->id);
    })->orWhere(function($q) use ($customer) {
        $q->where('reference_type', Sale::class)->whereIn('reference_id', $customer->sales()->pluck('id'));
    })->orWhere(function($q) use ($customer) {
        $q->where('reference_type', \App\Models\SalePayment::class)->whereIn('reference_id', \App\Models\SalePayment::whereIn('sale_id', $customer->sales()->pluck('id'))->pluck('id'));
    })->pluck('id');

    // AR Balance (Total Due) = Debits - Credits
    $arDebits = JournalEntry::whereIn('journal_id', $journalIds)->where('account_id', $arAcc->id)->where('type', 'debit')->sum('amount');
    $arCredits = JournalEntry::whereIn('journal_id', $journalIds)->where('account_id', $arAcc->id)->where('type', 'credit')->sum('amount');
    $newTotalDue = $arDebits - $arCredits;
    
    // Advance Balance (Wallet) = Credits - Debits
    $advCredits = JournalEntry::whereIn('journal_id', $journalIds)->where('account_id', $advAcc->id)->where('type', 'credit')->sum('amount');
    $advDebits = JournalEntry::whereIn('journal_id', $journalIds)->where('account_id', $advAcc->id)->where('type', 'debit')->sum('amount');
    $newWallet = $advCredits - $advDebits;

    // Reconciliation: Customer can't have both negative due or negative wallet
    if ($newTotalDue < 0) {
        $newWallet += abs($newTotalDue);
        $newTotalDue = 0;
    }

    if ($newWallet < 0) {
        $newTotalDue += abs($newWallet);
        $newWallet = 0;
    }

    // Update customer
    if ($customer->total_due != $newTotalDue || $customer->wallet_balance != $newWallet) {
        $customer->update([
            'total_due' => $newTotalDue,
            'wallet_balance' => $newWallet,
        ]);
        $updated++;
        echo "Updated Customer {$customer->id} ({$customer->name}): Due {$newTotalDue}, Wallet {$newWallet}\n";
    }
}

echo "Successfully synced balances for $updated customers.\n";
