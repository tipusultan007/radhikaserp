<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$sale = \App\Models\Sale::latest()->first();

// Simulate request
$request = new \Illuminate\Http\Request([
    'payment_status' => 'paid',
    'delivery_status' => 'dispatched',
    'notes' => 'Testing details update via script'
]);

$controller = new \App\Http\Controllers\SaleController();
$controller->updateDetails($request, $sale);

echo json_encode($sale->activities()->latest()->first());
