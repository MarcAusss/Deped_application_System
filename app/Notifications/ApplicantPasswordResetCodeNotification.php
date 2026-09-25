<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\HtmlString;

class ApplicantPasswordResetCodeNotification extends Notification
{
    public function __construct(public string $code)
    {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->from('cares.reset@depedalbay.com', 'SDO Albay CARES - Password Reset')
            ->subject('Your Password Reset Code')
            ->greeting('Hello '.$notifiable->name.',')
            ->line('Use the verification code below to reset your SDO Albay CARES account password.')
            ->line(new HtmlString(
                '<div style="font-size:28px;font-weight:700;letter-spacing:8px;text-align:center;margin:24px 0;color:#123B6D;">'.$this->code.'</div>'
            ))
            ->line('This code will expire in 60 minutes.')
            ->line('If you did not request a password reset, no further action is required.');
    }
}
