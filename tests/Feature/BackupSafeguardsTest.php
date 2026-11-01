<?php

use App\Notifications\Channels\LogChannel;
use Illuminate\Console\Events\CommandStarting;
use Illuminate\Notifications\ChannelManager;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;

function fireCommandStarting(string $command): void
{
    event(new CommandStarting($command, new ArrayInput([]), new NullOutput));
}

it('aborts backup:run when the archive password is empty', function () {
    config()->set('backup.backup.password', '');

    expect(fn () => fireCommandStarting('backup:run'))
        ->toThrow(RuntimeException::class, 'BACKUP_ARCHIVE_PASSWORD is not set');
});

it('aborts backup:run when the archive password is null', function () {
    config()->set('backup.backup.password', null);

    expect(fn () => fireCommandStarting('backup:run'))
        ->toThrow(RuntimeException::class);
});

it('allows backup:run when the archive password is set', function () {
    config()->set('backup.backup.password', 'a-strong-secret');

    fireCommandStarting('backup:run');
})->throwsNoExceptions();

it('does not guard unrelated commands even without a password', function () {
    config()->set('backup.backup.password', '');

    fireCommandStarting('migrate');
})->throwsNoExceptions();

it('registers a "log" notification channel backed by LogChannel', function () {
    expect(app(ChannelManager::class)->driver('log'))->toBeInstanceOf(LogChannel::class);
});

it('writes the failure notification body to the log', function () {
    $notification = new class extends Notification
    {
        public function toMail(mixed $notifiable): MailMessage
        {
            return (new MailMessage)
                ->subject('Backup failed')
                ->line('The disk google-drive is unreachable');
        }
    };

    Log::shouldReceive('channel')->once()->andReturnSelf();
    Log::shouldReceive('error')->once()->withArgs(
        fn ($message) => str_contains($message, 'Backup failed')
            && str_contains($message, 'unreachable')
    );

    (new LogChannel)->send(null, $notification);
});
