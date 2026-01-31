<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CustomResetPasswordNotification extends Notification
{
    use Queueable;

    public $token;

    // টোকেন রিসিভ করা হচ্ছে
    public function __construct($token)
    {
        $this->token = $token;
    }

    public function via($notifiable)
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {
        // ফ্রন্টএন্ডের লিঙ্ক জেনারেট করা (আপনার কনফিগারেশন অনুযায়ী)
        $frontendUrl = config('app.frontend_url');
        $url = "{$frontendUrl}/reset-password?token={$this->token}&email={$notifiable->getEmailForPasswordReset()}";

        // কাস্টম ভিউ রিটার্ন করা
        return (new MailMessage)
            ->subject('Reset Your Password Notification') // ইমেইলের সাবজেক্ট
            ->view('emails.reset_password', [
                'url' => $url,
                'user' => $notifiable
            ]);
    }
}
