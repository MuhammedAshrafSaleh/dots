<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Password-reset email for admin users. Mirrors Laravel's built-in reset
 * notification but links to the admin reset route (`admin.password.reset`).
 * Admin-initiated reset email is explicitly allowed (Decisions Q8).
 */
class AdminResetPasswordNotification extends Notification
{
    use Queueable;

    public function __construct(public string $token) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $url = route('admin.password.reset', [
            'token' => $this->token,
            'email' => $notifiable->getEmailForPasswordReset(),
        ]);

        $expire = config('auth.passwords.'.config('auth.defaults.passwords').'.expire', 60);

        return (new MailMessage)
            ->subject(__('Reset your DOTS admin password'))
            ->line(__('You are receiving this email because we received a password reset request for your admin account.'))
            ->action(__('Reset password'), $url)
            ->line(__('This password reset link will expire in :count minutes.', ['count' => $expire]))
            ->line(__('If you did not request a password reset, no further action is required.'));
    }
}
