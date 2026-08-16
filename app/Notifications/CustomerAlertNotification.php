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
    public $topic;

    /**
     * Create a new notification instance.
     */
    public function __construct($title, $message, $type = 'info', $data = [], $customerId = null, $topic = null)
    {
        $this->title = $title;
        $this->message = $message;
        $this->type = $type;
        $this->data = $data;
        $this->customerId = $customerId;
        $this->topic = $topic;

        $targetTopic = $this->topic ?? ($this->customerId ? 'customer_' . $this->customerId : null);

        if ($targetTopic) {
            try {
                $messaging = app('firebase.messaging');
                $payloadData = [];
                foreach (array_merge(['type' => $this->type], (array) $this->data) as $k => $v) {
                    $payloadData[(string) $k] = is_array($v) ? json_encode($v) : (string) $v;
                }

                $messageObj = \Kreait\Firebase\Messaging\CloudMessage::new()
                    ->withTopic($targetTopic)
                    ->withNotification(\Kreait\Firebase\Messaging\Notification::create($this->title, $this->message))
                    ->withData($payloadData);
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
