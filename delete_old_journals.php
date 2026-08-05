<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Journal;
use App\Models\JournalEntry;
use Illuminate\Support\Facades\DB;

DB::beginTransaction();
try {
    $journals = Journal::whereDate('created_at', '>=', '2026-08-05')
                       ->where('date', '<', '2026-05-20')
                       ->get();

    $count = 0;
    $entriesCount = 0;
    foreach($journals as $journal) {
        $deletedEntries = JournalEntry::where('journal_id', $journal->id)->delete();
        $entriesCount += $deletedEntries;
        
        $journal->delete();
        $count++;
    }

    DB::commit();
    echo "Successfully deleted $count migrated journals and $entriesCount related journal entries.\n";
} catch (\Exception $e) {
    DB::rollBack();
    echo "Error deleting journals: " . $e->getMessage() . "\n";
}
