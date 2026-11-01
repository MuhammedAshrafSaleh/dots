# Off-site backups — BK.1 → BK.3

**Goal:** Automated, off-site backups of the database and media to Google Drive via `spatie/laravel-backup`, with a proven restore procedure (PRD §13). `spatie/laravel-backup` is already installed.

**Branch:** `backups` (off up-to-date main, local only). Release works like a phase: on owner's "approved, push backups" → push branch, `git merge --no-ff` into main, tag `backups-done`, push main --tags.

**Rules (review-gated, same as phases):** one task per session, in order. After each task `composer gates` must pass (unit+feature+Larastan+Pint+build) → tick it here → **commit locally** `BK.<n>: <title>` (one commit per task, no push). Browser tests stay paused — write feature tests instead. Update PROGRESS.md "Current state" before stopping. STOP after each task for owner review.

**Google Drive constraints (`drive.file` scope — honour everywhere):**
- The OAuth refresh token is scoped to `drive.file` only: the app can see and manage **only** files and folders it created itself.
- NEVER instruct the owner to create the backup folder by hand in the Drive web UI — the app would not be able to see it, and every list/cleanup call would fail.
- The backup folder MUST be created by our own code on first write.
- Listing Drive will only ever return our own files. That is expected, not a bug.
- Credentials live only in `.env` (`GOOGLE_DRIVE_CLIENT_ID`, `GOOGLE_DRIVE_CLIENT_SECRET`, `GOOGLE_DRIVE_REFRESH_TOKEN`, `GOOGLE_DRIVE_FOLDER` — the folder intentionally empty). Never print, log, echo, or commit any credential value.

**Package decision:** off-site adapter is `jeffersongoncalves/flysystem-google-drive` (driver name `google-drive`), **not** yaza/masbug. yaza 5.x → masbug 2.x caps `guzzlehttp/guzzle` at `^6.3|^7.0`, which collides with this project's Guzzle 8.2.0 (required by Laravel 13 + spatie/crawler) — installing it would force a Guzzle major downgrade. The jeffersongoncalves adapter declares no direct Guzzle constraint and was written specifically to fix the masbug bug where `BackupDestination::isReachable()` throws on a backup folder that does not exist yet. See PROGRESS "Decisions".

---

## BK.1 — Google Drive disk + `dots:drive-check` command  _(Opus)_
- [x] Done

Add a `google-drive` storage disk reading the four `GOOGLE_DRIVE_*` env keys, and a `php artisan dots:drive-check` command that proves the Drive connection works end to end. This task does **not** configure backups yet. Remove the stale masbug-convention stub disk and its `GOOGLE_DRIVE_FOLDER_ID` key.

**Acceptance criteria**
- `config/filesystems.php` has a `google-drive` disk (driver `google-drive`, keys `clientId`/`clientSecret`/`refreshToken`/`folder`); the old `google`/`folderId` stub is gone.
- `.env.example` carries the four keys with empty values; `GOOGLE_DRIVE_FOLDER_ID` removed.
- `dots:drive-check` fails immediately with a clear message if any credential is empty; otherwise it writes → lists → reads-back → deletes one file under `DOTS-Backups/`, prints a numbered PASS/FAIL per step, and exits non-zero if any step fails. It deletes only the file it created.
- No credential value is ever printed, logged, or committed.
- Full unit+feature suite passes on a machine with **no** Drive credentials (the command's feature test skips when credentials are absent).

**Required tests (feature)**
- `dots:drive-check` fails with code 1 and a clear message when credentials are empty (no Drive calls made).
- A live round-trip test that is **skipped** when `GOOGLE_DRIVE_*` env values are absent.

**Owner clicks / runs:** `php artisan dots:drive-check` with real credentials in `.env` → every numbered step PASS, exit 0, and the connection-check file is gone from Drive afterwards.

---

## BK.2 — Configure spatie/laravel-backup  _(Sonnet)_
- [x] Done

Configure `spatie/laravel-backup` to back up the database **and** media files to the `google-drive` disk, keep only the last 7 backups, and send a failure notification. Document the whole setup and schedule in `docs/BACKUP.md`.

**Acceptance criteria**
- Backup includes DB dump + media; destination disk switches via `BACKUP_DISK=google-drive`.
- Cleanup strategy keeps only the last 7 backups.
- A failed backup notifies `BACKUP_NOTIFICATION_EMAIL`.
- `docs/BACKUP.md` explains config, scheduling, env, and how to run a manual backup.

---

## BK.3 — Real restore drill  _(Opus)_
- [x] Done

Perform a real restore of a backup into a **scratch local database** (never the `dots` DB) and document the exact, repeatable procedure in `docs/RESTORE.md`.

**Acceptance criteria**
- A backup is restored into a throwaway local database and verified (row counts / spot checks).
- `docs/RESTORE.md` is a step-by-step runbook a new operator can follow, including how to fetch the archive from Drive and the DB-safety guardrails.

---

## Checklist
- [x] BK.1 done
- [x] BK.2 done
- [x] BK.3 done
