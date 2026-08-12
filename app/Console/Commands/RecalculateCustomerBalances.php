<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use App\Models\Customer;
use App\Models\ChartOfAccount;
use App\Models\Journal;
use App\Models\Sale;

#[Signature('customers:recalculate-balances')]
#[Description('Recalculate balances for all customers')]
class RecalculateCustomerBalances extends Command
{
    /**
     * Execute the console command.
     */
    public function handle()
    {
        $customers = Customer::all();
        $count = $customers->count();

        $this->info("Found {$count} customers. Starting recalculation...");
        
        $bar = $this->output->createProgressBar($count);
        $bar->start();

        foreach ($customers as $customer) {
            $this->recalculateBalances($customer);
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info('All customer balances recalculated successfully!');
    }

    private function recalculateBalances(Customer $customer)
    {
        $customer->load(['sales']);

        $arAcc = ChartOfAccount::where('name', 'Accounts Receivable')->first();
        $advAcc = ChartOfAccount::where('name', 'Customer Advance')->first();
        $arId = $arAcc ? $arAcc->id : 0;
        $advId = $advAcc ? $advAcc->id : 0;
        
        $journals = Journal::with(['entries', 'reference'])
            ->where(function($q) use ($customer) {
                $q->where('reference_type', Customer::class)->where('reference_id', $customer->id);
            })->orWhere(function($q) use ($customer) {
                $q->where('reference_type', Sale::class)->whereIn('reference_id', $customer->sales()->pluck('id'));
            })->orWhere(function($q) use ($customer) {
                $q->where('reference_type', \App\Models\SalePayment::class)->whereIn('reference_id', \App\Models\SalePayment::whereIn('sale_id', $customer->sales()->pluck('id'))->pluck('id'));
            })
            ->get();

        $calculatedDue = $customer->opening_balance + $customer->sales()->sum('total');
        $advCredit = 0;
        $advDebit = 0;

        foreach ($journals as $journal) {
            if ($journal->notes != 'Opening Balance') {
                $advCredit += $journal->entries->where('account_id', $advId)->where('type', 'credit')->sum('amount');
                $advDebit += $journal->entries->where('account_id', $advId)->where('type', 'debit')->sum('amount');
            }
            
            // Subtract payments recorded via non-sale journals (like Customer or SalePayment)
            if ($journal->reference_type != Sale::class && $journal->notes != 'Opening Balance') {
                $credit = $journal->entries->where('account_id', $arId)->where('type', 'credit')->sum('amount');
                $debit = $journal->entries->where('account_id', $arId)->where('type', 'debit')->sum('amount');
                
                if ($debit > 0 && $credit > 0) {
                    if ($debit > $credit) { $debit = $debit - $credit; $credit = 0; }
                    else if ($credit > $debit) { $credit = $credit - $debit; $debit = 0; }
                    else { $debit = 0; $credit = 0; }
                }

                $calculatedDue -= $credit;
                $calculatedDue += $debit;
            }
        }

        // Subtract fallback POS payments for sales
        $posPayments = 0;
        foreach ($customer->sales as $sale) {
            $initialPaymentAmount = \App\Models\SalePayment::where('sale_id', $sale->id)
                ->where(function($q) {
                    $q->whereNull('reference')
                      ->orWhereIn('reference', ['POS Payment', 'Wallet Payment']);
                })
                ->sum('amount');
                
            $hasJournal = $journals->contains(function($j) use ($sale) {
                return str_contains($j->notes, 'Payment for POS Sale ' . $sale->invoice_no);
            });
            
            if ($initialPaymentAmount > 0 && !$hasJournal) {
                $posPayments += $initialPaymentAmount;
            }
        }
            
        $calculatedDue -= $posPayments;

        $calculatedWallet = $advCredit - $advDebit;

        $customer->total_due = $calculatedDue;
        $customer->wallet_balance = $calculatedWallet;
        $customer->save();
    }
}
