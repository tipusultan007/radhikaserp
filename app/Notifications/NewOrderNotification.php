<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewOrderNotification extends Notification
{
    use Queueable;

    protected $sale;

    /**
     * Create a new notification instance.
     */
    public function __construct($sale)
    {
        $this->sale = $sale;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
                    ->subject('New Customer Order: ' . $this->sale->invoice_no)
                    ->greeting('Hello ' . $notifiable->name . ',')
                    ->line('A new order has been placed by a customer.')
                    ->line('Invoice No: ' . $this->sale->invoice_no)
                    ->line('Total Amount: ৳' . number_format($this->sale->total, 2))
                    ->action('Open App', url('/'))
                    ->line('Please review the order in the admin dashboard.');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'sale_id' => $this->sale->id,
            'invoice_no' => $this->sale->invoice_no,
            'total' => $this->sale->total,
            'message' => 'New customer order: ' . $this->sale->invoice_no
        ];
    }
}
