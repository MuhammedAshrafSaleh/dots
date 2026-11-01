<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Proves the Google Drive (`google-drive` disk) connection works end to end:
 * write → list → read-back → delete, all under DOTS-Backups/.
 *
 * The OAuth refresh token is scoped to `drive.file`, so the app only ever sees
 * files and folders it created itself. We never pre-create DOTS-Backups by hand
 * in the Drive UI — the folder is created automatically on the first write here.
 * The command deletes only the single file it created, nothing else.
 */
class DriveCheckCommand extends Command
{
    protected $signature = 'dots:drive-check';

    protected $description = 'Verify the Google Drive connection (google-drive disk) by writing, listing, reading and deleting one file under DOTS-Backups/';

    private const DISK = 'google-drive';

    private const FOLDER = 'DOTS-Backups';

    public function handle(): int
    {
        // Preflight: the three secrets must be present. GOOGLE_DRIVE_FOLDER is
        // intentionally allowed to be empty — our code creates the folder on
        // first write. We never print any credential value.
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

        $disk = Storage::disk(self::DISK);
        $path = self::FOLDER.'/connection-check-'.now()->format('Ymd-His').'.txt';
        $content = 'DOTS drive-check '.now()->toIso8601String().' '.bin2hex(random_bytes(8));

        $ok = true;
        $written = false;

        // 1. WRITE — creates DOTS-Backups/ on first write.
        try {
            $disk->put($path, $content);
            $written = true;
            $this->line('1. WRITE   PASS  '.$path);
        } catch (Throwable $e) {
            $ok = false;
            $this->line('1. WRITE   FAIL  '.$e->getMessage());
        }

        // 2. LIST — the file must appear when listing DOTS-Backups/.
        if ($written) {
            try {
                $names = array_map('basename', $disk->files(self::FOLDER));
                if (in_array(basename($path), $names, true)) {
                    $this->line('2. LIST    PASS  found in '.self::FOLDER.'/');
                } else {
                    $ok = false;
                    $this->line('2. LIST    FAIL  not found when listing '.self::FOLDER.'/');
                }
            } catch (Throwable $e) {
                $ok = false;
                $this->line('2. LIST    FAIL  '.$e->getMessage());
            }
        } else {
            $this->line('2. LIST    SKIP  nothing was written');
        }

        // 3. READ — content must match exactly.
        if ($written) {
            try {
                if ($disk->get($path) === $content) {
                    $this->line('3. READ    PASS  content matches exactly');
                } else {
                    $ok = false;
                    $this->line('3. READ    FAIL  content mismatch on read-back');
                }
            } catch (Throwable $e) {
                $ok = false;
                $this->line('3. READ    FAIL  '.$e->getMessage());
            }
        } else {
            $this->line('3. READ    SKIP  nothing was written');
        }

        // 4. DELETE — remove only the file we created.
        if ($written) {
            try {
                $disk->delete($path);
                $this->line('4. DELETE  PASS  removed '.$path);
            } catch (Throwable $e) {
                $ok = false;
                $this->line('4. DELETE  FAIL  '.$e->getMessage());
            }
        } else {
            $this->line('4. DELETE  SKIP  nothing was written');
        }

        if ($ok) {
            $this->newLine();
            $this->info('Google Drive connection OK.');

            return self::SUCCESS;
        }

        $this->newLine();
        $this->error('Google Drive connection check FAILED.');

        return self::FAILURE;
    }
}
