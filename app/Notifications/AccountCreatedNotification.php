<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AccountCreatedNotification extends Notification
{
    use Queueable;

    public function __construct(
        private string $password,
        private string $adminName,
        private string $recipientEmail,
        private bool $isReset = false,
    ) {}

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        $subject = $this->isReset
            ? 'Your Grab Maps Password Has Been Reset'
            : 'Welcome to Grab Maps — Your Account is Ready';

        return (new MailMessage)
            ->subject($subject)
            ->view('emails.account-credentials', [
                'userName'       => $notifiable->name,
                'userEmail'      => $notifiable->email,
                'recipientEmail' => $this->recipientEmail,
                'password'       => $this->password,
                'adminName'      => $this->adminName,
                'loginUrl'       => url('/admin'),
                'isReset'        => $this->isReset,
                'isSend'         => false,
            ]);
    }
}
