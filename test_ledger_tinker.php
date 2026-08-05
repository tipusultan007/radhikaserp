<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$controller = new \App\Http\Controllers\CustomerController();
$response = $controller->show(\App\Models\Customer::find(55));
$view = $response;
$ledgerEntries = $view->gatherData()['ledgerEntries'];

echo "Total ledger entries passed to view: " . count($ledgerEntries) . "\n";
$salesCount = 0;
$payCount = 0;
foreach($ledgerEntries as $entry) {
    if (strpos($entry->id, '_sale') !== false) {
        $salesCount++;
    } elseif (strpos($entry->id, '_pay') !== false) {
        $payCount++;
    }
}
echo "Sales: $salesCount, Payments during sale: $payCount, Other: " . (count($ledgerEntries) - $salesCount - $payCount) . "\n";
