<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SendCredentialsNotification extends Notification
{
    use Queueable;

    public function __construct(
        private string $adminName,
        private string $password,
        private string $recipientEmail,
    ) {}

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Your Grab Maps Account Credentials')
            ->view('emails.account-credentials', [
                'userName'       => $notifiable->name,
                'userEmail'      => $notifiable->email,
                'recipientEmail' => $this->recipientEmail,
                'password'       => $this->password,
                'adminName'      => $this->adminName,
                'loginUrl'       => url('/admin'),
                'isReset'        => false,
                'isSend'         => true,
            ]);
    }
}
