<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ResetPasswordNotification extends Notification
{
    use Queueable;

    public function __construct(private string $token) {}

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        // Absolute URL built from APP_URL — matches the password.reset route
        // (/reset-password/{token}?email=...). Fix APP_URL if the button points to the wrong host.
        $resetUrl = route('password.reset', [
            'token' => $this->token,
            'email' => $notifiable->getEmailForPasswordReset(),
        ]);

        $broker = config('auth.defaults.passwords');
        $expire = config("auth.passwords.{$broker}.expire", 60);

        return (new MailMessage)
            ->subject('Reset Password Akun Grab Maps')
            ->view('emails.reset-password', [
                'userName'  => $notifiable->name,
                'userEmail' => $notifiable->getEmailForPasswordReset(),
                'resetUrl'  => $resetUrl,
                'expire'    => $expire,
            ]);
    }
}
