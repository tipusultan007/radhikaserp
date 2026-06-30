<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$journal = \App\Models\Journal::where('reference_type', App\Models\Customer::class)->first();
if ($journal) {
    echo "Attempting to delete journal {$journal->id}\n";
    $controller = new \App\Http\Controllers\DueSettlementController();
    $response = $controller->deleteCustomerPayment($journal);
    echo get_class($response) . "\n";
    
    if ($response instanceof \Illuminate\Http\RedirectResponse) {
        $session = session();
        if ($session->has('error')) {
            echo "Error: " . $session->get('error') . "\n";
        }
        if ($session->has('success')) {
            echo "Success: " . $session->get('success') . "\n";
        }
    }
}
