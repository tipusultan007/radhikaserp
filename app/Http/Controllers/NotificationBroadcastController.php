<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Customer;
use App\Notifications\CustomerAlertNotification;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class NotificationBroadcastController extends Controller
{
    /**
     * Show the Push Notification Broadcast dashboard.
     */
    public function index()
    {
        $customers = Customer::orderBy('name')->get();
        
        // Fetch paginated customer notifications from DB
        $recentNotifications = \Illuminate\Support\Facades\DB::table('notifications')
            ->where('notifiable_type', Customer::class)
            ->orderBy('created_at', 'desc')
            ->paginate(15)
            ->through(function ($item) {
                $item->data = json_decode($item->data, true);
                $customer = Customer::find($item->notifiable_id);
                $item->customer_name = $customer ? $customer->name : 'All Customers (Broadcast)';
                return $item;
            });

        return view('notifications.broadcast', compact('customers', 'recentNotifications'));
    }

    /**
     * Send a custom push notification to all customers or a specific customer.
     */
    public function send(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'message' => 'required|string',
            'target' => 'required|string|in:all,specific',
            'customer_id' => 'required_if:target,specific|nullable|integer',
            'type' => 'required|string|in:promotion,announcement,info,due_reminder',
        ]);

        $title = $request->input('title');
        $message = $request->input('message');
        $target = $request->input('target');
        $type = $request->input('type');
        $customerId = $request->input('customer_id');

        try {
            if ($target === 'specific' && $customerId) {
                $customer = Customer::findOrFail($customerId);
                $customer->notify(new CustomerAlertNotification(
                    $title,
                    $message,
                    $type,
                    ['type' => $type],
                    $customer->id
                ));

                return redirect()->back()->with('success', "Push notification sent successfully to customer: {$customer->name}");
            } else {
                // Send broadcast FCM push to 'promotions' topic
                new CustomerAlertNotification(
                    $title,
                    $message,
                    $type,
                    ['type' => $type],
                    null,
                    'promotions'
                );

                // Save in-app notification records for all customers
                $customers = Customer::all();
                foreach ($customers as $cust) {
                    $cust->notifications()->create([
                        'id' => (string) Str::uuid(),
                        'type' => CustomerAlertNotification::class,
                        'data' => [
                            'title' => $title,
                            'message' => $message,
                            'type' => $type,
                        ],
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }

                return redirect()->back()->with('success', "Broadcast push notification successfully sent to ALL customers!");
            }
        } catch (\Exception $e) {
            Log::error('Web Push Notification Error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to send notification: ' . $e->getMessage());
        }
    }

    /**
     * Manually trigger daily due reminders push to all customers with due balance.
     */
    public function triggerDueReminders()
    {
        try {
            \Illuminate\Support\Facades\Artisan::call('dues:send-reminders');
            $output = \Illuminate\Support\Facades\Artisan::output();

            return redirect()->back()->with('success', 'Due Reminders Triggered! ' . trim($output));
        } catch (\Exception $e) {
            Log::error('Manual Due Reminder Trigger Error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to trigger due reminders: ' . $e->getMessage());
        }
    }
}
