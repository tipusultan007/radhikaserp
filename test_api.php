<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

try {
    $sale = \App\Models\Sale::where('consignment_id', 280240799)->first();
    if(!$sale) die("Sale not found\n");
    $updates = $sale->tracking_updates ?? [];
    $updates[] = [
        'status' => 'in_transit',
        'message' => 'Parcel is in transit to destination',
        'date' => now()->toDateTimeString()
    ];
    $sale->tracking_updates = $updates;
    $sale->save();
    echo "Saved successfully.\n";
} catch (\Exception $e) {
    echo "Exception: " . $e->getMessage() . "\n";
}
