<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Password-reset email for storefront customers. Links to the locale-prefixed
 * customer reset route (`password.reset`). Customer-initiated reset email is
 * explicitly allowed (Decisions Q8 — no other automatic customer mail).
 */
class CustomerResetPasswordNotification extends Notification
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
        $url = route('password.reset', [
            'locale' => app()->getLocale(),
            'token' => $this->token,
            'email' => $notifiable->getEmailForPasswordReset(),
        ]);

        $expire = config('auth.passwords.customers.expire', 30);

        return (new MailMessage)
            ->subject(__('account.reset.mail_subject'))
            ->greeting(__('account.reset.mail_greeting'))
            ->line(__('account.reset.mail_intro'))
            ->action(__('account.reset.mail_action'), $url)
            ->line(__('account.reset.mail_expire', ['count' => $expire]))
            ->line(__('account.reset.mail_ignore'));
    }
}
