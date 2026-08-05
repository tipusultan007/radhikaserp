<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();
use App\Models\Sale;
use App\Models\Journal;
$missingCount = 0;
$sales = Sale::all();
foreach($sales as $s) {
    if (!Journal::where('reference_type', Sale::class)->where('reference_id', $s->id)->exists()) {
        $missingCount++;
    }
}
echo "Sales missing journals: $missingCount\n";
