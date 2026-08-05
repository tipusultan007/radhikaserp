<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Schema;

echo "Journal entries columns:\n";
print_r(Schema::getColumnListing('journal_entries'));

echo "\nJournals columns:\n";
print_r(Schema::getColumnListing('journals'));

echo "\nSalePayments columns:\n";
print_r(Schema::getColumnListing('sale_payments'));

echo "\nDueSettlements columns:\n";
print_r(Schema::getColumnListing('due_settlements'));
