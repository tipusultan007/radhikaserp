<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Journal;
use Illuminate\Support\Facades\DB;

$types = DB::table('journals')->select('reference_type')->distinct()->get();
echo "Distinct reference_types in journals:\n";
foreach($types as $t) {
    echo $t->reference_type . "\n";
}

$cId = 55;
$typesCust = DB::table('journals')
    ->join('journal_entries', 'journals.id', '=', 'journal_entries.journal_id')
    ->join('chart_of_accounts', 'journal_entries.account_id', '=', 'chart_of_accounts.id')
    ->where('chart_of_accounts.name', 'Accounts Receivable')
    ->select('journals.reference_type')
    ->distinct()
    ->get();
echo "\nReference types that hit AR:\n";
foreach($typesCust as $t) {
    echo $t->reference_type . "\n";
}
