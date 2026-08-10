<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$batches = \App\Models\Batch::whereHas('product', function($q) {
    $q->where('type', 'raw');
})->get(['id', 'product_id', 'qty_in', 'qty_out', 'remaining_qty']);

echo json_encode($batches, JSON_PRETTY_PRINT);
