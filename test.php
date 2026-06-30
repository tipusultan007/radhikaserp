<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$journal = App\Models\Journal::find(18);
if(!$journal) { echo "Not found"; exit;}

DB::beginTransaction();
try {
    $arEntry = $journal->entries()->where('type', 'credit')->whereHas('account', function($q) { $q->where('name', 'Accounts Receivable'); })->first();
    $duePayment = $arEntry ? $arEntry->amount : 0;
    
    $advCredit = $journal->entries()->where('type', 'credit')->whereHas('account', function($q) { $q->where('name', 'Customer Advance'); })->sum('amount');
    $advDebit = $journal->entries()->where('type', 'debit')->whereHas('account', function($q) { $q->where('name', 'Customer Advance'); })->sum('amount');
    $netAdvance = $advCredit - $advDebit;

    $customer = $journal->reference;
    if ($customer && $customer instanceof App\Models\Customer) {
        if ($netAdvance > 0) {
            $customer->decrement('wallet_balance', $netAdvance);
        } elseif ($netAdvance < 0) {
            $customer->increment('wallet_balance', abs($netAdvance));
        }
        if ($duePayment > 0) {
            $customer->increment('total_due', $duePayment);
            
            // Attempt to reverse SalePayments
            $ref = '';
            if (preg_match('/\(Ref: (.*?)\)/', $journal->notes, $matches)) {
                $ref = $matches[1];
            }
            $salePaymentRef = 'Due Settlement ' . $ref;
            
            $salePayments = \App\Models\SalePayment::where('date', $journal->date)
                ->where('reference', $salePaymentRef)
                ->whereHas('sale', function($q) use ($customer) {
                    $q->where('customer_id', $customer->id);
                })->get();
                
            foreach($salePayments as $sp) {
                $sale = $sp->sale;
                $sale->paid_amount -= $sp->amount;
                $sale->due_amount += $sp->amount;
                $sale->payment_status = $sale->paid_amount == 0 ? 'unpaid' : 'partial';
                $sale->save();
                $sp->delete();
            }
        }
    }

    $journal->entries()->delete();
    $journal->delete();

    DB::commit();
    echo "Success!";
} catch (\Exception $e) {
    DB::rollBack();
    echo "Error: " . $e->getMessage();
}
