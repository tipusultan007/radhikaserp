<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Customer;
use App\Notifications\CustomerAlertNotification;
use Illuminate\Support\Facades\Log;

class SendDailyDueReminders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dues:send-reminders';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send daily push notifications and in-app reminders to customers with unpaid due balances';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting daily customer due reminders check...');

        $customersWithDue = Customer::where('total_due', '>', 0)->get();
        $count = 0;

        foreach ($customersWithDue as $customer) {
            try {
                $formattedDue = number_format((float)$customer->total_due, 2);
                $title = 'Daily Payment Reminder';
                $message = "Dear {$customer->name}, you have a pending due balance of ৳{$formattedDue}. Please make your payment at your earliest convenience.";

                // Dispatch notification (saves in DB & sends FCM push to customer_{id} topic)
                $customer->notify(new CustomerAlertNotification(
                    $title,
                    $message,
                    'due_reminder',
                    [
                        'due_amount' => (float)$customer->total_due,
                        'customer_id' => $customer->id,
                    ],
                    $customer->id
                ));

                $count++;
            } catch (\Exception $e) {
                Log::error("Failed to send due reminder to Customer ID {$customer->id}: " . $e->getMessage());
            }
        }

        $this->info("Successfully sent daily due reminders to {$count} customer(s).");
        return Command::SUCCESS;
    }
}
