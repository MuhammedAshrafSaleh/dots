<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Downloads the most recent backup archive from the `google-drive` disk to a
 * local path and prints where it landed. This is the first step of a restore
 * drill (BK.3) — see docs/RESTORE.md.
 *
 * Read-only against Drive: it lists and reads, it NEVER writes or deletes
 * anything on Drive. Like dots:drive-check it refuses to run when Drive
 * credentials are missing, and never prints any credential value.
 */
class BackupDownloadCommand extends Command
{
    protected $signature = 'dots:backup-download {--dest= : Local directory to download into (default: a temp dir)}';

    protected $description = 'Download the most recent backup archive from the google-drive disk to a local path (read-only; never deletes on Drive)';

    private const DISK = 'google-drive';

    public function handle(): int
    {
        // Preflight: the three Drive secrets must be present. We never print any
        // credential value. GOOGLE_DRIVE_FOLDER may legitimately be empty.
        $config = config('filesystems.disks.'.self::DISK);
        $missing = array_values(array_filter(
            ['clientId', 'clientSecret', 'refreshToken'],
            fn (string $key): bool => blank($config[$key] ?? null),
        ));

        if ($missing !== []) {
            $this->error('Google Drive credentials are not configured. Missing/empty: '.implode(', ', $missing).'.');
            $this->line('Set GOOGLE_DRIVE_CLIENT_ID, GOOGLE_DRIVE_CLIENT_SECRET and GOOGLE_DRIVE_REFRESH_TOKEN in .env, then re-run.');

            return self::FAILURE;
        }

        // Backups live under a folder named after the Spatie backup name
        // (APP_NAME, e.g. "DOTS") on the google-drive disk.
        $folder = (string) config('backup.backup.name');
        $disk = Storage::disk(self::DISK);

        try {
            $archives = array_values(array_filter(
                $disk->files($folder),
                fn (string $f): bool => str_ends_with(strtolower($f), '.zip'),
            ));
        } catch (Throwable $e) {
            $this->error('Could not list backups on Drive: '.$e->getMessage());

            return self::FAILURE;
        }

        if ($archives === []) {
            $this->error('No backup archives found in "'.$folder.'/" on the '.self::DISK.' disk.');

            return self::FAILURE;
        }

        // Pick the most recently modified archive.
        $latest = null;
        $latestTime = -1;
        foreach ($archives as $archive) {
            try {
                $time = $disk->lastModified($archive);
            } catch (Throwable) {
                $time = 0;
            }
            if ($time > $latestTime) {
                $latestTime = $time;
                $latest = $archive;
            }
        }

        $latest ??= $archives[0];

        // Resolve destination directory (never inside storage/app/public).
        $dest = $this->option('dest');
        if (blank($dest)) {
            $dest = rtrim(sys_get_temp_dir(), '/').'/dots-restore-'.now()->format('Ymd-His');
        }
        $dest = rtrim((string) $dest, '/');

        if (! is_dir($dest) && ! @mkdir($dest, 0755, true) && ! is_dir($dest)) {
            $this->error('Could not create destination directory: '.$dest);

            return self::FAILURE;
        }

        $target = $dest.'/'.basename($latest);

        $this->line('Downloading '.$latest.' ...');

        try {
            $stream = $disk->readStream($latest);
            if ($stream === null) {
                throw new \RuntimeException('read stream was null');
            }
            $out = fopen($target, 'wb');
            if ($out === false) {
                throw new \RuntimeException('could not open local file for writing: '.$target);
            }
            stream_copy_to_stream($stream, $out);
            fclose($out);
            if (is_resource($stream)) {
                fclose($stream);
            }
        } catch (Throwable $e) {
            $this->error('Download failed: '.$e->getMessage());

            return self::FAILURE;
        }

        $bytes = (int) (@filesize($target) ?: 0);

        $this->newLine();
        $this->info('Downloaded latest backup.');
        $this->line('  Archive : '.$latest);
        $this->line('  Saved to: '.$target);
        $this->line('  Size    : '.number_format($bytes / 1048576, 1).' MB ('.number_format($bytes).' bytes)');
        $this->newLine();
        $this->line('Next: decrypt & extract it (needs BACKUP_ARCHIVE_PASSWORD). See docs/RESTORE.md.');

        return self::SUCCESS;
    }
}
