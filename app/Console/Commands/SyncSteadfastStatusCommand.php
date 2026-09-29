<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\SteadfastService;

class SyncSteadfastStatusCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'steadfast:sync {--all : Sync all consignments, including delivered and cancelled}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Poll Steadfast Courier API and synchronize delivery statuses for orders';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $all = (bool) $this->option('all');
        $this->info($all ? 'Synchronizing ALL Steadfast consignments...' : 'Synchronizing pending Steadfast consignments...');

        $result = SteadfastService::syncPendingSales($all);

        $this->info("Steadfast sync complete!");
        $this->line("Total checked: {$result['total']}");
        $this->line("Updated / Consumed: {$result['updated']}");
        if ($result['errors'] > 0) {
            $this->warn("Errors encountered: {$result['errors']}");
        }

        return Command::SUCCESS;
    }
}

