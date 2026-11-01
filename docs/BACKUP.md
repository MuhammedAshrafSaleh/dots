# Backups

Off-site backups for DOTS, powered by [`spatie/laravel-backup`](https://spatie.be/docs/laravel-backup).
Configured in `config/backup.php`; scheduled in `routes/console.php`.

## What is backed up

1. **Database** — the MySQL `dots` connection (a full SQL dump).
2. **Media files** — `storage/app/public` only. This is where Spatie
   MediaLibrary stores every model's media: `config/media-library.php` sets
   `disk_name` to `MEDIA_DISK` (default `public`), no model overrides it, and
   `conversions_disk` is `null` so generated conversions live on the same disk.
   The `public` disk's root is `storage/app/public`.

Everything else is intentionally **excluded**, because it is either a secret or
re-creatable from git / a package manager:

- `.env` (secrets — must never leave the server)
- `storage/logs` (runtime logs)
- `node_modules` (re-installable via `npm`)
- `vendor` (re-installable via `composer`)
- `storage/framework` (compiled views / cache / sessions)

Application code is **not** backed up — it lives in git.

## Where it goes

The **`google-drive` disk only** (`config/filesystems.php`). The OAuth refresh
token is minted with the `drive.file` scope, so the app can only see files and
folders it created itself. Backups land in a folder named after `APP_NAME`
inside the Drive folder our own code creates on first write. **Never create the
backup folder by hand in the Drive UI** — with `drive.file` scope the app would
not be able to see it.

Verify connectivity any time with `php artisan dots:drive-check` (BK.1).

## Encryption (required)

Every archive is AES-256 encrypted with `BACKUP_ARCHIVE_PASSWORD`.

> ## ⚠️ LOSING `BACKUP_ARCHIVE_PASSWORD` IS UNRECOVERABLE
> There is **no** recovery path. If you lose this password, **every existing
> backup becomes permanently unreadable** — the data is gone. Store it in a
> password manager, separately from the Drive account. Changing it does not
> re-encrypt old backups; archives made with the old password still need the
> old password.

If `BACKUP_ARCHIVE_PASSWORD` is empty, `php artisan backup:run` **aborts loudly**
(guard in `app/Providers/AppServiceProvider.php`) rather than silently writing an
unencrypted archive off-site.

## Retention

Keep only the **last 7 daily backups**. The cleanup uses Spatie's
`DefaultStrategy` with every longer-term tier disabled
(`keep_all_backups_for_days = 7`; daily/weekly/monthly/yearly all `0`), so
nothing older than a week survives. Cleanup only ever operates inside our own
backup folder on the `google-drive` disk (scoped by the Spatie backup name, and
further by the `drive.file` scope which hides every file the app did not
create), so it can never delete anything outside it. The newest backup is never
deleted.

## Schedule

Registered in `routes/console.php`, pinned explicitly to **Africa/Cairo** (the
app default is UTC, so we do not rely on it):

| Time (Africa/Cairo) | Command          | Purpose                          |
| ------------------- | ---------------- | -------------------------------- |
| 02:30 daily         | `backup:clean`   | prune archives beyond retention  |
| 03:00 daily         | `backup:run`     | DB + media backup to Google Drive |
| 03:30 daily         | `backup:monitor` | health-check the latest backup   |

The scheduler requires a cron entry running `php artisan schedule:run` every
minute on the server.

## Notifications

On **backup failure**, **cleanup failure**, and an **unhealthy backup**, DOTS:

1. writes the failure to the **application log** (custom `log` notification
   channel, `app/Notifications/Channels/LogChannel.php`), and
2. emails **muhammedashrafsaleh@gmail.com** (`BACKUP_NOTIFICATION_EMAIL`).

The log channel is listed first and runs independently of mail, so failures are
recorded even though mail is **not configured yet** (it arrives in Phase 10
step 6). Successful and healthy runs are silent.

## Environment keys

| Key                         | Required | Notes                                                   |
| --------------------------- | -------- | ------------------------------------------------------- |
| `BACKUP_ARCHIVE_PASSWORD`   | **yes**  | Archive encryption. Empty ⇒ `backup:run` aborts.        |
| `BACKUP_NOTIFICATION_EMAIL` | no       | Defaults to `muhammedashrafsaleh@gmail.com`.            |
| `GOOGLE_DRIVE_CLIENT_ID`    | **yes**  | Google Drive OAuth client id.                           |
| `GOOGLE_DRIVE_CLIENT_SECRET`| **yes**  | Google Drive OAuth client secret.                       |
| `GOOGLE_DRIVE_REFRESH_TOKEN`| **yes**  | OAuth refresh token (`drive.file` scope).               |
| `GOOGLE_DRIVE_FOLDER`       | no       | Leave empty — created on first write.                   |

## Restoring

Restoring is documented as a step-by-step runbook in **[`docs/RESTORE.md`](RESTORE.md)**:
how to fetch the archive from Drive (`php artisan dots:backup-download`), decrypt
and extract it, import the DB into a scratch database, restore media, and verify.

**Verified on 2026-11-01** — a real archive was downloaded, decrypted, and
restored into a scratch database; all main-table row counts and media matched
the live data exactly. See `docs/RESTORE.md` for the reference numbers.
