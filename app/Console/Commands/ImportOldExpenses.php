<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportOldExpenses extends Command
{
    protected $signature = 'import:old-expenses {--after=2026-05-20 : Filter expenses created on or after date}';
    protected $description = 'Import expense categories and expenses (filtered after specified date) from old project database';

    public function handle()
    {
        $afterDate = $this->option('after');
        $this->info("Starting expense categories and expenses migration (after {$afterDate})...");

        try {
            $oldDb = DB::connection('old_mysql');
            $oldDb->getPdo();
        } catch (\Exception $e) {
            $this->error('Failed to connect to old database: ' . $e->getMessage());
            return 1;
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        // 1. Import Expense Categories
        $oldCategories = $oldDb->table('expense_categories')->orderBy('id', 'asc')->get();
        $this->info('Found ' . count($oldCategories) . ' expense categories in old database.');

        foreach ($oldCategories as $cat) {
            DB::table('expense_categories')->updateOrInsert(
                ['id' => $cat->id],
                [
                    'name' => $cat->name,
                    'code' => 'EXP-CAT-' . $cat->id,
                    'status' => 1,
                    'parent_id' => $cat->parent_id ?? null,
                    'created_at' => $cat->created_at ?? now(),
                    'updated_at' => $cat->updated_at ?? now(),
                ]
            );
        }

        $maxCatId = DB::table('expense_categories')->max('id') ?? 0;
        DB::statement("ALTER TABLE expense_categories AUTO_INCREMENT = " . ($maxCatId + 1));

        // 2. Import Expenses created/dated >= afterDate
        $oldExpenses = $oldDb->table('expenses')
            ->where('created_at', '>=', $afterDate . ' 00:00:00')
            ->orWhere('date', '>=', $afterDate)
            ->orderBy('id', 'asc')
            ->get();

        $this->info('Found ' . count($oldExpenses) . ' expenses created on or after ' . $afterDate);

        $importedExpenseCount = 0;
        $totalExpenseAmount = 0;

        foreach ($oldExpenses as $exp) {
            $expDate = !empty($exp->date) ? date('Y-m-d', strtotime($exp->date)) : date('Y-m-d', strtotime($exp->created_at));

            DB::table('expenses')->updateOrInsert(
                ['id' => $exp->id],
                [
                    'expense_category_id' => $exp->expense_category_id,
                    'warehouse_id' => 1,
                    'amount' => (float)$exp->amount,
                    'notes' => $exp->notes ?? '',
                    'date' => $expDate,
                    'reference_type' => $exp->reference_type ?? null,
                    'reference_id' => $exp->reference_id ?? null,
                    'created_by' => 1,
                    'created_at' => $exp->created_at ?? now(),
                    'updated_at' => $exp->updated_at ?? now(),
                ]
            );

            $importedExpenseCount++;
            $totalExpenseAmount += (float)$exp->amount;
        }

        $maxExpId = DB::table('expenses')->max('id') ?? 0;
        DB::statement("ALTER TABLE expenses AUTO_INCREMENT = " . ($maxExpId + 1));

        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $this->info("Expenses migration completed successfully!");
        $this->table(
            ['Metric', 'Value'],
            [
                ['Expense Categories Imported', count($oldCategories)],
                ['Expenses Imported (>= ' . $afterDate . ')', $importedExpenseCount],
                ['Total Expense Amount (BDT)', number_format($totalExpenseAmount, 2)],
            ]
        );

        return 0;
    }
}
