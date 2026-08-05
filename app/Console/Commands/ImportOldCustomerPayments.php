<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\Customer;
use App\Models\Journal;
use App\Models\JournalEntry;
use App\Models\ChartOfAccount;
use Illuminate\Support\Str;

class ImportOldCustomerPayments extends Command
{
    protected $signature = 'import:old-customer-payments';
    protected $description = 'Import old customer due settlements (payments) into Journals without modifying customer balances';

    public function handle()
    {
        $this->info("Starting old customer payments migration...");

        try {
            $oldDb = DB::connection('old_mysql');
            $oldDb->getPdo();
        } catch (\Exception $e) {
            $this->error('Failed to connect to old database: ' . $e->getMessage());
            return 1;
        }

        $oldPayments = $oldDb->table('payments')
            ->where('paymentable_type', 'like', '%Customer%')
            ->orderBy('id', 'asc')
            ->get();

        $this->info('Found ' . count($oldPayments) . ' customer payments in old database.');

        // Get standard accounts
        $cashAcc = ChartOfAccount::firstOrCreate(['name' => 'Cash', 'type' => 'asset']);
        $arAcc = ChartOfAccount::firstOrCreate(['name' => 'Accounts Receivable', 'type' => 'asset']);

        $methodMap = [
            'cash' => $cashAcc->id,
            'bank' => 2,
            'bkash' => 20,
            'nagad' => 21,
            'rocket' => 22,
        ];

        $importedCount = 0;
        $totalAmount = 0;

        $bar = $this->output->createProgressBar(count($oldPayments));
        $bar->start();

        DB::beginTransaction();
        try {
            foreach ($oldPayments as $pay) {
                // Determine account ID based on payment_method string from old DB
                $methodStr = strtolower(trim($pay->payment_method ?? 'cash'));
                $accountId = $methodMap[$methodStr] ?? $cashAcc->id;

                $pDate = !empty($pay->date) ? date('Y-m-d', strtotime($pay->date)) : date('Y-m-d', strtotime($pay->created_at ?? now()));
                $customerId = $pay->customer_id ?? $pay->paymentable_id;

                // Ensure customer exists
                $customerExists = DB::table('customers')->where('id', $customerId)->exists();
                if (!$customerExists) {
                    continue; // Skip if customer doesn't exist
                }

                $amount = (float)$pay->amount;
                
                // Avoid duplicates by checking journal notes for a specific reference
                $notes = "Historical Payment Imported (Old ID: {$pay->id})";
                if (!empty($pay->notes)) {
                    $notes .= " - " . $pay->notes;
                }

                $exists = Journal::where('reference_type', Customer::class)
                    ->where('reference_id', $customerId)
                    ->where('notes', 'like', "%Old ID: {$pay->id}%")
                    ->exists();

                if (!$exists) {
                    $journal = Journal::create([
                        'journal_no' => 'RCV-OLD-' . $pay->id,
                        'date' => $pDate,
                        'reference_type' => Customer::class,
                        'reference_id' => $customerId,
                        'notes' => $notes,
                        'created_by' => 1,
                        'created_at' => $pay->created_at ?? now(),
                        'updated_at' => $pay->updated_at ?? now(),
                    ]);

                    if ($amount > 0) {
                        // Debit Cash/Bank
                        JournalEntry::create([
                            'journal_id' => $journal->id,
                            'account_id' => $accountId,
                            'type' => 'debit',
                            'amount' => $amount,
                            'created_at' => $pay->created_at ?? now(),
                            'updated_at' => $pay->updated_at ?? now(),
                        ]);

                        // Credit Accounts Receivable
                        JournalEntry::create([
                            'journal_id' => $journal->id,
                            'account_id' => $arAcc->id,
                            'type' => 'credit',
                            'amount' => $amount,
                            'created_at' => $pay->created_at ?? now(),
                            'updated_at' => $pay->updated_at ?? now(),
                        ]);
                    }

                    $importedCount++;
                    $totalAmount += $amount;
                }

                $bar->advance();
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error("\nMigration failed: " . $e->getMessage());
            return 1;
        }

        $bar->finish();
        $this->newLine(2);

        $this->info("Customer Payments migration completed successfully!");
        $this->table(
            ['Metric', 'Value'],
            [
                ['Total Payments Imported', $importedCount],
                ['Total Amount (BDT)', number_format($totalAmount, 2)],
            ]
        );

        return 0;
    }
}
