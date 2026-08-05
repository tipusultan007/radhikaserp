<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Customer;
use App\Models\Journal;
use App\Models\Sale;
use App\Models\SalePayment;
use App\Models\DueSettlement;

$customer = Customer::find(55);
echo "Customer: " . $customer->name . "\n";

$sales = Sale::where('customer_id', 55)->get();
echo "Sales for Customer 55:\n";
foreach($sales as $s) {
    echo "Sale ID: {$s->id}, Invoice: {$s->invoice_no}, Total: {$s->total}, Paid: {$s->paid_amount}\n";
}

$journals = Journal::where(function($q) use ($customer) {
    $q->where('reference_type', Customer::class)->where('reference_id', $customer->id);
})->orWhere(function($q) use ($customer) {
    $q->where('reference_type', Sale::class)->whereIn('reference_id', $customer->sales()->pluck('id'));
})->get();

echo "\nJournals found in CustomerController logic:\n";
foreach($journals as $j) {
    echo "Journal ID: {$j->id}, RefType: {$j->reference_type}, RefID: {$j->reference_id}, Notes: {$j->notes}\n";
}

$payments = SalePayment::whereHas('sale', function($q) {
    $q->where('customer_id', 55);
})->get();
echo "\nSalePayments for Customer 55:\n";
foreach($payments as $p) {
    echo "Payment ID: {$p->id}, Sale ID: {$p->sale_id}, Amount: {$p->amount}\n";
}

$settlements = DueSettlement::where('customer_id', 55)->get();
echo "\nDue Settlements for Customer 55:\n";
foreach($settlements as $s) {
    echo "Settlement ID: {$s->id}, Amount: {$s->amount}\n";
}
