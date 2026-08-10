<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$sale = \App\Models\Sale::latest()->first();
echo "Activities count: " . $sale->activities()->count() . "\n";
foreach($sale->activities as $act) {
    echo $act->action . ": " . $act->description . "\n";
}
