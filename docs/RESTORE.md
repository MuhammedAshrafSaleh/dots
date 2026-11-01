# Restoring a DOTS backup

A runbook for restoring a `spatie/laravel-backup` archive from Google Drive
when the site is down. **Follow the steps in order.** Backups are described in
[`docs/BACKUP.md`](BACKUP.md); this document is only about getting the data back.

> **An untested backup is not a backup.** This procedure was performed for real
> on **2026-11-01** against the latest archive (`DOTS/2026-11-01-19-31-46.zip`,
> 76.9 MB). The reference numbers at the bottom show what a healthy restore
> looks like.

---

## ⚠️ If `BACKUP_ARCHIVE_PASSWORD` is lost — STOP

Every archive is AES-256 encrypted. **There is no recovery path.** If the
password is gone, every backup is permanently unreadable and the data cannot be
restored by any means — not by us, not by Google, not by anyone. Nothing in this
runbook will work. The only remaining data is whatever is still live on the
server. Store the password in a password manager, separate from the Drive
account.

Everything below assumes you have the **same** `BACKUP_ARCHIVE_PASSWORD` that
was in `.env` when the archive was created. Changing the password does **not**
re-encrypt old archives — an old archive needs the password it was made with.

---

## What you need before you start

- The project checked out, `composer install` run, and a working `.env` with:
  - the **`GOOGLE_DRIVE_*`** credentials (to fetch the archive), and
  - the **`BACKUP_ARCHIVE_PASSWORD`** the archive was encrypted with.
- A MySQL server you can reach with the `DB_*` credentials.
- PHP with `ZipArchive` (libzip ≥ 1.x — ships with the app's PHP). This is what
  decrypts the AES-256 archive; you do **not** need `7z` installed.

---

## Step 1 — Fetch the latest archive from Drive

```bash
php artisan dots:backup-download --dest=/tmp/dots-restore
```

This downloads the **most recent** backup from the `google-drive` disk into
`/tmp/dots-restore/` and prints the exact path and size. It is **read-only**
against Drive — it never deletes anything there. If credentials are missing it
refuses to run.

To restore a specific older archive instead, download it by hand from the
`DOTS/` folder in the backup Google account and drop it in `/tmp/dots-restore/`.

## Step 2 — Decrypt and extract the archive

The archive is AES-256 encrypted, so a plain `unzip` will not open it. Use PHP's
`ZipArchive` with the password from `.env` (no external tools needed):

```bash
cd /tmp/dots-restore
ARCHIVE=$(ls -t *.zip | head -1)                     # newest .zip here
PW=$(grep -E '^BACKUP_ARCHIVE_PASSWORD=' /path/to/dots/.env | cut -d= -f2-)

php -r '
  $z = new ZipArchive();
  if ($z->open($argv[2]) !== true) { fwrite(STDERR, "open failed\n"); exit(1); }
  $z->setPassword($argv[1]);
  if (!$z->extractTo("extracted")) { fwrite(STDERR, "extract failed — wrong password?\n"); exit(1); }
  $z->close();
  echo "extracted ".$z->numFiles." entries\n";
' "$PW" "$ARCHIVE"
```

If extraction fails with "wrong password", the `BACKUP_ARCHIVE_PASSWORD` does
not match this archive — see the warning at the top.

> Operators who prefer a CLI tool can instead use `7z` (WinZip-AES capable):
> `7z x -p"$PW" "$ARCHIVE" -oextracted`. Install it with `brew install sevenzip`
> (macOS) or `apt-get install p7zip-full` (Debian/Ubuntu). Plain `unzip` will
> **not** work.

After extracting you have two things:

- `extracted/db-dumps/mysql-dots.sql` — the database dump.
- `extracted/Users/.../storage/app/public/` — the media files (the archive
  preserves the original absolute path).

## Step 3 — Restore the database

> **Safety:** restore into a **scratch** database first and verify it before you
> ever consider touching the live `dots` database. Never import straight over a
> running production DB.

The dump is a `mysqldump` that includes a `GTID_PURGED` statement, which a
different/empty server will reject. Strip the two GTID/binlog lines, then import:

```bash
cd /tmp/dots-restore/extracted/db-dumps
sed -E '/^SET @@GLOBAL\.GTID_PURGED=/d; /^SET @@SESSION\.SQL_LOG_BIN=/d' \
  mysql-dots.sql > mysql-dots.clean.sql

# scratch DB — NOT the live one
mysql -h 127.0.0.1 -u root -e "CREATE DATABASE dots_restore_check CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -h 127.0.0.1 -u root dots_restore_check < mysql-dots.clean.sql
```

Only once the scratch restore is verified (Step 5) and you have made an
independent safety copy of the current live DB should you import into the real
`dots` database.

## Step 4 — Restore the media files

Copy the extracted media into `storage/app/public`. Verify against a scratch
directory first; only overwrite the live path when you are sure.

```bash
# the media root inside the archive (absolute path preserved):
SRC=/tmp/dots-restore/extracted/Users/*/Herd/dots/storage/app/public

# verify into a scratch dir first
rsync -a --delete $SRC/ /tmp/dots-media-check/

# when ready, restore into the live app (run from the project root):
# rsync -a /tmp/dots-media-check/ storage/app/public/
php artisan storage:link   # ensure public/storage symlink exists
```

## Step 5 — Verify the restore objectively

Compare row counts between the source and the restored DB for the main tables,
and compare media file count + size. **Any mismatch is a failure** — do not
proceed to overwrite live data.

```bash
for t in products product_variants categories brands orders customers pages settings; do
  echo "$t: $(mysql -N -u root -e "SELECT COUNT(*) FROM dots_restore_check.$t;")"
done

# media
find /tmp/dots-media-check -type f | wc -l
du -sh /tmp/dots-media-check
```

## Step 6 — Confirm the site works

Point a scratch app environment (or, after the live cutover, the real site) at
the restored data and check:

- Home page and a category page load (products appear with images).
- A product detail page shows its variants and media.
- The admin dashboard loads and lists orders.
- Settings-driven content (header, footer, contact) renders.

## Step 7 — Clean up

```bash
mysql -h 127.0.0.1 -u root -e "DROP DATABASE dots_restore_check;"
rm -rf /tmp/dots-restore /tmp/dots-media-check
```

Never leave decrypted dumps or media archives lying around — they contain
customer data.

---

## Reference: a healthy restore (drill of 2026-11-01)

Archive `DOTS/2026-11-01-19-31-46.zip` (76.9 MB, 1204 entries), restored into
`dots_restore_check`. All numbers matched the live `dots` database exactly:

| Table              | `dots` | restored | match |
| ------------------ | -----: | -------: | :---: |
| products           |     18 |       18 |  ✅   |
| product_variants   |     30 |       30 |  ✅   |
| categories         |     65 |       65 |  ✅   |
| brands             |      8 |        8 |  ✅   |
| orders             |    108 |      108 |  ✅   |
| customers          |      9 |        9 |  ✅   |
| pages              |      6 |        6 |  ✅   |
| settings           |     37 |       37 |  ✅   |

Media: live `storage/app/public` = **861** files / 82,676 KB; archive =
**860** files / 82,664 KB. The single-file, 12 KB difference is exactly one
`.DS_Store` (a macOS Finder artifact, not application data) — every one of the
860 real media files was present and identical. A healthy restore.
