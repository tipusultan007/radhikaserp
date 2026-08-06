<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class GenerateCustomerPasswords extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'customer:generate-passwords {--force : Overwrite existing passwords}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate passwords for customers based on their phone number, or a random string if phone is empty';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $force = $this->option('force');
        
        $query = \App\Models\Customer::query();
        if (!$force) {
            $query->whereNull('password')->orWhere('password', '');
        }
        
        $customers = $query->get();
        $count = 0;

        foreach ($customers as $customer) {
            if (!empty($customer->phone)) {
                $password = $customer->phone;
                $this->info("Customer {$customer->id} ({$customer->name}): Password set to phone ({$password})");
            } else {
                $password = \Illuminate\Support\Str::random(8);
                $this->info("Customer {$customer->id} ({$customer->name}): Password set to random ({$password})");
            }
            
            $customer->password = \Illuminate\Support\Facades\Hash::make($password);
            // Save without triggering updated_at if possible, but standard save is fine.
            $customer->save();
            $count++;
        }

        $this->info("Successfully generated/updated passwords for {$count} customers.");
    }
}
