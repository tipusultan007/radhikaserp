<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CustomerAlertNotification extends Notification
{
    use Queueable;

    public $title;
    public $message;
    public $type;
    public $data;
    public $customerId;

    /**
     * Create a new notification instance.
     */
    public function __construct($title, $message, $type = 'info', $data = [], $customerId = null)
    {
        $this->title = $title;
        $this->message = $message;
        $this->type = $type;
        $this->data = $data;
        $this->customerId = $customerId;

        if ($this->customerId) {
            try {
                $messaging = app('firebase.messaging');
                $messageObj = \Kreait\Firebase\Messaging\CloudMessage::withTarget('topic', 'customer_' . $this->customerId)
                    ->withNotification(\Kreait\Firebase\Messaging\Notification::create($this->title, $this->message))
                    ->withData(array_merge(['type' => $this->type], $this->data));
                $messaging->send($messageObj);
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error('Firebase Customer Push Error: ' . $e->getMessage());
            }
        }
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
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
