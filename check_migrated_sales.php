<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Sale;
use App\Models\InventoryTransaction;
use App\Services\StockReconciliationService;

echo "=== CHECKING DELIVERED SALES WITHOUT INVENTORY TRANSACTIONS ===\n\n";

$pendingSales = Sale::whereIn('delivery_status', ['delivered', 'dispatched'])
    ->whereNotIn('id', function($q) {
        $q->select('reference_id')->from('inventory_transactions')->where('reference_type', Sale::class);
    })->get();

echo "Total delivered/dispatched sales requiring stock deduction: " . $pendingSales->count() . "\n";

$totalItemsCount = 0;
foreach ($pendingSales as $sale) {
    $totalItemsCount += $sale->items->count();
}
echo "Total Sale Items across these sales: {$totalItemsCount}\n\n";
