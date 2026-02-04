<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class NewOrderNotification extends Notification
{
    use Queueable;

    public $order;

    public function __construct($order)
    {
        $this->order = $order;
    }

    // কোন কোন চ্যানেলে যাবে? (Database & Mail)
    public function via($notifiable)
    {
        return ['database', 'mail'];
    }

    // 📧 ইমেইল ফরম্যাট
    public function toMail($notifiable)
    {
        return (new MailMessage)
                    ->subject('New Order Received: ' . $this->order->order_number)
                    ->greeting('Hello Admin,')
                    ->line('You have received a new order.')
                    ->line('Order Number: ' . $this->order->order_number)
                    ->line('Amount: ' . $this->order->grand_total . ' TK')
                    ->action('View Order', url('/admin/orders/' . $this->order->id));
    }

    // 🔔 ডাটাবেস (Bell Icon) ফরম্যাট
    public function toArray($notifiable)
    {
        return [
            'title' => 'New Order Received',
            'message' => 'Order ' . $this->order->order_number . ' placed by ' . $this->order->customer_name,
            'order_id' => $this->order->id,
            'amount' => $this->order->grand_total,
            'type' => 'order'
        ];
    }
}
