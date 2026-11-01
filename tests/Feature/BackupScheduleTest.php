<?php

use Illuminate\Console\Scheduling\Schedule;
use Spatie\Backup\Notifications\Notifications\BackupHasFailedNotification;
use Spatie\Backup\Notifications\Notifications\BackupWasSuccessfulNotification;
use Spatie\Backup\Notifications\Notifications\CleanupHasFailedNotification;
use Spatie\Backup\Notifications\Notifications\HealthyBackupWasFoundNotification;
use Spatie\Backup\Notifications\Notifications\UnhealthyBackupWasFoundNotification;

it('backs up the database and media on the local disk only', function () {
    expect(config('backup.backup.destination.disks'))->toBe(['local'])
        ->and(config('backup.backup.source.databases'))->toContain('mysql')
        ->and(config('backup.backup.source.files.include'))->toContain(storage_path('app/public'));
});

it('keeps only the last 7 days of backups', function () {
    $strategy = config('backup.cleanup.default_strategy');

    expect($strategy['keep_all_backups_for_days'])->toBe(7)
        ->and($strategy['keep_daily_backups_for_days'])->toBe(0)
        ->and($strategy['keep_weekly_backups_for_weeks'])->toBe(0)
        ->and($strategy['keep_monthly_backups_for_months'])->toBe(0)
        ->and($strategy['keep_yearly_backups_for_years'])->toBe(0);
});

it('emails on failure only, not on success', function () {
    $notifications = config('backup.notifications.notifications');

    expect($notifications[BackupHasFailedNotification::class])->toBe(['mail'])
        ->and($notifications[UnhealthyBackupWasFoundNotification::class])->toBe(['mail'])
        ->and($notifications[CleanupHasFailedNotification::class])->toBe(['mail'])
        ->and($notifications[BackupWasSuccessfulNotification::class])->toBe([])
        ->and($notifications[HealthyBackupWasFoundNotification::class])->toBe([]);
});

it('exposes a Google Drive disk that is wired but off by default', function () {
    expect(config('filesystems.disks.google-drive.driver'))->toBe('google-drive')
        ->and(config('backup.backup.destination.disks'))->not->toContain('google-drive');
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

it('runs the daily backup at 03:00', function () {
    $schedule = app(Schedule::class);

    $backupRun = collect($schedule->events())
        ->first(fn ($event) => str_contains((string) $event->command, 'backup:run'));

    expect($backupRun)->not->toBeNull()
        ->and($backupRun->expression)->toBe('0 3 * * *');
});
