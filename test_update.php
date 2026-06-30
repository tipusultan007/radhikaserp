<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

$request = Illuminate\Http\Request::create(
    '/api/admin/product-variants/1',
    'PUT',
    ['product_id' => 1, 'name' => 'Test', 'sku' => 'TEST-SKU', 'unit_qty' => '1', 'unit_type' => 'pcs', 'status' => 1]
);

$response = $kernel->handle($request);
echo "STATUS: " . $response->getStatusCode() . "\n";
echo "BODY: " . $response->getContent() . "\n";
