<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class ImportOldAll extends Command
{
    protected $signature = 'import:old-all {--after=2026-05-20 : Filter transactions created on or after date}';
    protected $description = 'Run full data migration (Customers, Products, Expenses, Stocks, Sales, Repackaging) from old project';

    public function handle()
    {
        $afterDate = $this->option('after');
        $this->info("=================================================");
        $this->info("      STARTING FULL DATA MIGRATION PIPELINE     ");
        $this->info("=================================================");
        $this->info("Filter Date for Transactions: {$afterDate}\n");

        $this->call('import:old-customers');
        $this->newLine();

        $this->call('import:old-products');
        $this->newLine();

        $this->call('import:old-expenses', ['--after' => $afterDate]);
        $this->newLine();

        $this->call('import:old-stocks', ['--after' => $afterDate]);
        $this->newLine();

        $this->call('import:old-sales', ['--after' => $afterDate]);
        $this->newLine();

        $this->call('import:old-repackaging', ['--after' => $afterDate]);
        $this->newLine();

        $this->info("=================================================");
        $this->info("      FULL DATA MIGRATION COMPLETED SUCCESSFULLY!");
        $this->info("=================================================");

        return 0;
    }
}
