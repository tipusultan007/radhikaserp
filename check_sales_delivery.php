<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\InventoryTransaction;

echo "=== SALES BREAKDOWN BY DELIVERY STATUS ===\n\n";

$salesByStatus = Sale::select('delivery_status', 'delivery_method', DB::raw('count(*) as count'))
    ->groupBy('delivery_status', 'delivery_method')
    ->get();

foreach ($salesByStatus as $row) {
    $status = $row->delivery_status ?? 'NULL';
    $method = $row->delivery_method ?? 'NULL';
    echo "Delivery Status: {$status} | Delivery Method: {$method} | Count: {$row->count}\n";
}

echo "\n=== SALES WITH ITEMS BUT NO INVENTORY TRANSACTIONS ===\n";
$salesWithoutStockDeduction = Sale::whereNotIn('id', function($q) {
    $q->select('reference_id')->from('inventory_transactions')->where('reference_type', Sale::class);
})->get();

echo "Total Sales without Inventory Deduction: " . $salesWithoutStockDeduction->count() . "\n";

$pendingPickupSales = Sale::whereIn('delivery_status', ['pending', 'accepted', null])
    ->where(function($q) {
        $q->whereNull('delivery_method')->orWhere('delivery_method', 'pickup')->orWhere('delivery_method', 'own_delivery');
    })->get();

echo "Pending/Pickup/OwnDelivery Sales that need stock consumption: " . $pendingPickupSales->count() . "\n";
