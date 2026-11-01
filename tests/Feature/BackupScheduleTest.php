<?php

use Illuminate\Console\Scheduling\Schedule;
use Spatie\Backup\Notifications\Notifications\BackupHasFailedNotification;
use Spatie\Backup\Notifications\Notifications\BackupWasSuccessfulNotification;
use Spatie\Backup\Notifications\Notifications\CleanupHasFailedNotification;
use Spatie\Backup\Notifications\Notifications\HealthyBackupWasFoundNotification;
use Spatie\Backup\Notifications\Notifications\UnhealthyBackupWasFoundNotification;

it('backs up the MySQL database and media to Google Drive only', function () {
    expect(config('backup.backup.destination.disks'))->toBe(['google-drive'])
        ->and(config('backup.backup.source.databases'))->toBe(['mysql'])
        ->and(config('backup.backup.source.files.include'))->toBe([storage_path('app/public')]);
});

it('excludes secrets and anything re-creatable from git', function () {
    $exclude = config('backup.backup.source.files.exclude');

    expect($exclude)->toContain(base_path('.env'))
        ->and($exclude)->toContain(storage_path('logs'))
        ->and($exclude)->toContain(base_path('node_modules'))
        ->and($exclude)->toContain(base_path('vendor'))
        ->and($exclude)->toContain(storage_path('framework'));
});

it('keeps only the last 7 days of backups and nothing longer', function () {
    $strategy = config('backup.cleanup.default_strategy');

    expect($strategy['keep_all_backups_for_days'])->toBe(7)
        ->and($strategy['keep_daily_backups_for_days'])->toBe(0)
        ->and($strategy['keep_weekly_backups_for_weeks'])->toBe(0)
        ->and($strategy['keep_monthly_backups_for_months'])->toBe(0)
        ->and($strategy['keep_yearly_backups_for_years'])->toBe(0);
});

it('reads the archive password from the BACKUP_ARCHIVE_PASSWORD env key', function () {
    $config = require base_path('config/backup.php');

    expect($config['backup']['password'])->toBe(env('BACKUP_ARCHIVE_PASSWORD'))
        ->and($config['backup']['encryption'])->toBe('default');
});

it('notifies by log then mail on failure, and stays silent on success', function () {
    $notifications = config('backup.notifications.notifications');

    expect($notifications[BackupHasFailedNotification::class])->toBe(['log', 'mail'])
        ->and($notifications[UnhealthyBackupWasFoundNotification::class])->toBe(['log', 'mail'])
        ->and($notifications[CleanupHasFailedNotification::class])->toBe(['log', 'mail'])
        ->and($notifications[BackupWasSuccessfulNotification::class])->toBe([])
        ->and($notifications[HealthyBackupWasFoundNotification::class])->toBe([]);
});

it('sends the failure mail to the owner', function () {
    expect(config('backup.notifications.mail.to'))->toBe('muhammedashrafsaleh@gmail.com');
});

it('monitors only the Google Drive backup', function () {
    expect(config('backup.monitor_backups.0.disks'))->toBe(['google-drive']);
});

it('exposes a Google Drive disk wired to the google-drive driver', function () {
    expect(config('filesystems.disks.google-drive.driver'))->toBe('google-drive');
});

it('schedules backups, sitemap and reservation cleanup', function () {
    $schedule = app(Schedule::class);

    $commands = collect($schedule->events())
        ->map(fn ($event) => $event->command)
        ->filter()
        ->implode(' ');

    expect($commands)->toContain('backup:run')
        ->and($commands)->toContain('backup:clean')
        ->and($commands)->toContain('backup:monitor')
        ->and($commands)->toContain('sitemap:generate')
        ->and($commands)->toContain('orders:release-reservations');
});

it('runs clean at 02:30 and run at 03:00, both in Africa/Cairo', function () {
    $schedule = app(Schedule::class);

    $event = fn (string $needle) => collect($schedule->events())
        ->first(fn ($e) => str_contains((string) $e->command, $needle));

    $clean = $event('backup:clean');
    $run = $event('backup:run');

    expect($clean)->not->toBeNull()
        ->and($clean->expression)->toBe('30 2 * * *')
        ->and($clean->timezone)->toBe('Africa/Cairo')
        ->and($run)->not->toBeNull()
        ->and($run->expression)->toBe('0 3 * * *')
        ->and($run->timezone)->toBe('Africa/Cairo');
});
