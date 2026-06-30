<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ChartOfAccountsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $accounts = [
            ['name' => 'Cash', 'type' => 'asset', 'is_payment_method' => true],
            ['name' => 'Bank', 'type' => 'asset', 'is_payment_method' => true],
            ['name' => 'Inventory', 'type' => 'asset', 'is_payment_method' => false],
            ['name' => 'Accounts Receivable', 'type' => 'asset', 'is_payment_method' => false],
            
            ['name' => 'Accounts Payable', 'type' => 'liability', 'opening_balance' => 0, 'is_payment_method' => false],
            ['name' => 'Customer Advance', 'type' => 'liability', 'opening_balance' => 0, 'is_payment_method' => false],
            
            ['name' => 'Owner Equity', 'type' => 'equity', 'is_payment_method' => false],
            
            ['name' => 'Sales Revenue', 'type' => 'income', 'is_payment_method' => false],
            
            ['name' => 'Cost of Goods Sold', 'type' => 'expense', 'is_payment_method' => false],
            ['name' => 'Operational Expenses', 'type' => 'expense', 'is_payment_method' => false],
        ];

        foreach ($accounts as $account) {
            \App\Models\ChartOfAccount::firstOrCreate(
                ['name' => $account['name']],
                $account
            );
        }
    }
}
