<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AdminAlertNotification extends Notification
{
    use Queueable;

    public $title;
    public $message;
    public $type;
    public $data;

    /**
     * Create a new notification instance.
     */
    public function __construct($title, $message, $type = 'info', $data = [])
    {
        $this->title = $title;
        $this->message = $message;
        $this->type = $type; // e.g., 'order', 'stock', 'alert'
        $this->data = $data; // extra context

        $sendFcm = function () use ($title, $message, $type, $data) {
            if ($type === 'expense') {
                return; // Do not send push notification for expenses
            }
            try {
                $messaging = app('firebase.messaging');
                $payloadData = [];
                foreach (array_merge(['type' => $type, 'title' => $title, 'message' => $message], (array) $data) as $k => $v) {
                    $payloadData[(string) $k] = is_array($v) ? json_encode($v) : (string) $v;
                }

                $messageObj = \Kreait\Firebase\Messaging\CloudMessage::new()
                    ->withTopic('admins')
                    ->withNotification(\Kreait\Firebase\Messaging\Notification::create($title, $message))
                    ->withData($payloadData);
                $messaging->send($messageObj);
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::error('Firebase Error: ' . $e->getMessage());
            }
        };

        if (app()->runningInConsole()) {
            $sendFcm();
        } else {
            app()->terminating($sendFcm);
        }
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database']; // we are only using database for in-app for now
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => $this->title,
            'message' => $this->message,
            'type' => $this->type,
            'data' => $this->data,
        ];
    }
}
