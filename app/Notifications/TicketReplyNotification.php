<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class TicketReplyNotification extends Notification
{
    use Queueable;

    public $ticket;

    public function __construct($ticket)
    {
        $this->ticket = $ticket;
    }

    public function via($notifiable)
    {
        return ['database', 'mail'];
    }

    public function toMail($notifiable)
    {
        return (new MailMessage)
                    ->subject('Support Ticket Reply: ' . $this->ticket->ticket_number)
                    ->greeting('Hello ' . $notifiable->name . ',')
                    ->line('Admin has replied to your support ticket.')
                    ->line('Subject: ' . $this->ticket->subject)
                    ->action('View Ticket', url('/dashboard/tickets/' . $this->ticket->id));
    }

    public function toArray($notifiable)
    {
        return [
            'title' => 'Support Ticket Reply',
            'message' => 'New reply on ticket #' . $this->ticket->ticket_number,
            'ticket_id' => $this->ticket->id,
            'type' => 'ticket'
        ];
    }
}
