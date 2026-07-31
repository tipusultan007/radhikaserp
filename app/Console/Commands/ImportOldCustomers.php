<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\Customer;

class ImportOldCustomers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import:old-customers';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import all customer list data from old project (radhikas) database';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting customer data migration from old database...');

        try {
            $oldDb = DB::connection('old_mysql');
            $oldDb->getPdo();
        } catch (\Exception $e) {
            $this->error('Failed to connect to old database: ' . $e->getMessage());
            return 1;
        }

        $oldCustomers = $oldDb->table('customers')->orderBy('id', 'asc')->get();
        $this->info('Found ' . count($oldCustomers) . ' customers in old database.');

        // Pre-fetch customer due sums from old sales table
        $duesMap = [];
        try {
            $salesDues = $oldDb->table('sales')
                ->select('customer_id', DB::raw('SUM(due_amount) as total_due'))
                ->whereNotNull('customer_id')
                ->groupBy('customer_id')
                ->get();

            foreach ($salesDues as $saleDue) {
                $duesMap[$saleDue->customer_id] = (float) $saleDue->total_due;
            }
            $this->info('Calculated dues for ' . count($duesMap) . ' customers from old sales table.');
        } catch (\Exception $e) {
            $this->warn('Could not read sales table for dues calculation: ' . $e->getMessage());
        }

        $importedCount = 0;
        $totalDuesSum = 0;
        $totalWalletSum = 0;

        $bar = $this->output->createProgressBar(count($oldCustomers));
        $bar->start();

        foreach ($oldCustomers as $oldCust) {
            $customerType = strtolower(trim($oldCust->customer_type ?? 'customer'));
            if ($customerType !== 'dealer') {
                $customerType = 'customer';
            }

            $phone = trim($oldCust->phone ?? '');
            $email = trim($oldCust->email ?? '');
            $address = trim($oldCust->address ?? '');

            $dueAmount = $duesMap[$oldCust->id] ?? 0.00;
            $walletBalance = (float) ($oldCust->wallet_balance ?? 0.00);

            DB::table('customers')->updateOrInsert(
                ['id' => $oldCust->id],
                [
                    'name' => $oldCust->name,
                    'customer_type' => $customerType,
                    'email' => $email !== '' ? $email : null,
                    'phone' => $phone !== '' ? $phone : null,
                    'address' => $address !== '' ? $address : null,
                    'opening_balance' => 0.00,
                    'credit_limit' => 0.00,
                    'total_due' => $dueAmount,
                    'wallet_balance' => $walletBalance,
                    'created_at' => $oldCust->created_at ?? now(),
                    'updated_at' => $oldCust->updated_at ?? now(),
                ]
            );

            $importedCount++;
            $totalDuesSum += $dueAmount;
            $totalWalletSum += $walletBalance;

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        // Adjust auto increment on customers table
        $maxId = DB::table('customers')->max('id') ?? 0;
        $nextId = $maxId + 1;
        DB::statement("ALTER TABLE customers AUTO_INCREMENT = {$nextId}");

        $this->info("Customer data migration completed successfully!");
        $this->table(
            ['Metric', 'Value'],
            [
                ['Total Customers Imported', $importedCount],
                ['Total Customer Dues (BDT)', number_format($totalDuesSum, 2)],
                ['Total Wallet Balance (BDT)', number_format($totalWalletSum, 2)],
                ['Next Auto-Increment ID', $nextId],
            ]
        );

        return 0;
    }
}
