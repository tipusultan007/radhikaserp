<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\SaleItem;
use App\Models\Sale;

echo "Finding sale items with missing total_weight...\n";

$items = SaleItem::whereNull('total_weight')->orWhere('total_weight', 0)->with(['productVariant.product', 'batch.product'])->get();
$count = 0;

$salesToUpdate = [];

foreach ($items as $item) {
    $qty = $item->qty;
    $weight = 0;

    if ($item->productVariant) {
        $weight = $item->productVariant->getBaseQuantity() * $qty;
    } elseif ($item->batch && $item->batch->product) {
        // For raw product without variant, qty is usually the weight in base unit
        $weight = $qty; 
    }

    if ($weight > 0) {
        $item->total_weight = $weight;
        $item->save();
        $count++;
        $salesToUpdate[$item->sale_id] = true;
    }
}

echo "Updated total_weight for $count sale items.\n";

echo "Updating parent Sale total_weight...\n";
$salesCount = 0;
foreach (array_keys($salesToUpdate) as $saleId) {
    $sale = Sale::find($saleId);
    if ($sale) {
        $sale->total_weight = $sale->items()->sum('total_weight');
        $sale->save();
        $salesCount++;
    }
}

echo "Updated total_weight for $salesCount sales.\n";
