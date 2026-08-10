<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$batches = \App\Models\Batch::whereNotNull('product_variant_id')->get(['id', 'product_id', 'product_variant_id', 'qty_in', 'qty_out', 'remaining_qty']);

echo json_encode($batches, JSON_PRETTY_PRINT);
