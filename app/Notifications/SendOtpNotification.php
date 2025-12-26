<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SendOtpNotification extends Notification
{
    use Queueable;

    protected $otp;

    public function __construct($otp)
    {
        $this->otp = $otp;
    }

    public function via($notifiable)
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject('Your Email Verification Code')
            ->greeting('Hello ' . $notifiable->name . '!')
            ->line('Your verification code is: **' . $this->otp . '**')
            ->line('This code will expire in 10 minutes.')
            ->line('If you did not request this code, please ignore this email.')
            ->salutation('Regards, ' . config('app.name'));
    }
}