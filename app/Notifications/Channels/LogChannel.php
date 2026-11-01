<?php

namespace App\Notifications\Channels;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;

/**
 * A "log" notification channel.
 *
 * Laravel ships mail/slack/etc. channels but no log channel. spatie/laravel-
 * backup sends failure notifications through whatever channels it finds in
 * config('backup.notifications.notifications'); we register this one as "log"
 * (see App\Providers\AppServiceProvider) so backup/cleanup failures are always
 * written to the application log — independently of mail, which is not yet
 * configured (Phase 10 step 6).
 */
class LogChannel
{
    public function send(mixed $notifiable, Notification $notification): void
    {
        Log::channel(config('backup.log_channel') ?: config('logging.default'))
            ->error($this->format($notification, $notifiable));
    }

    /**
     * Reuse the notification's own mail body (built in-memory, nothing is sent)
     * so the log line carries the same subject + details as the email would.
     */
    private function format(Notification $notification, mixed $notifiable): string
    {
        if (! method_exists($notification, 'toMail')) {
            return 'Backup notification: '.$notification::class;
        }

        /** @var MailMessage $mail */
        $mail = $notification->toMail($notifiable);

        $lines = array_filter(array_merge(
            [$mail->subject],
            $mail->introLines,
            $mail->outroLines,
        ));

        return '[backup] '.implode(' ', $lines);
    }
}
