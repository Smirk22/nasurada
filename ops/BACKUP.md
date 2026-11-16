# Database Backups (IETI SEVS)

SEVS uses a single SQLite database. Every backup is a ZIP archive containing:

| File in archive | Notes |
|---|---|
| `database.sqlite` | Consistent snapshot made with SQLite `VACUUM INTO` (safe while the app is running) |
| `storage/app/public/...` | Student photos and other uploads (the database only stores their paths) |
| `env/.env` | Environment file — contains `APP_KEY`, needed to decrypt 2FA secrets |
| `manifest.json` | Backup date, app/PHP/Laravel versions, file counts |

Archives are written to `storage/app/backups` (outside the public web root) and are
only downloadable by an Admin from the dashboard.

## From the admin dashboard

1. Sign in as an Admin and open **Admin Dashboard**.
2. Find the **Database Backups** card.
3. Click **Backup Now** to create a snapshot.
4. Use **Download** to copy an archive somewhere else, or **Delete** to remove it.

The dashboard shows the last backup time, storage used, and the retention count.

## From the command line

```bat
php artisan backup:run
php artisan backup:list
```

`backup:run` prunes archives beyond `BACKUP_KEEP` (default 14).

## Automatic backups

### Option A — Windows Task Scheduler (recommended for this setup)

Run `install-backup-task.bat` once. It registers a daily 11:00 PM task that runs
`backup-sevs.bat`.

Test it immediately with:

```bat
schtasks /Run /TN "IETI SEVS Daily Backup"
```

### Option B — Laravel scheduler

`routes/console.php` already schedules `backup:run` daily at 23:00. This only runs
while the scheduler is running:

```bat
php artisan schedule:work
```

## Restoring a backup

> Restoring replaces the live database. Stop the SEVS server first if the command
> reports a file-lock error.

```bat
php artisan backup:list
php artisan backup:restore                          REM newest backup
php artisan backup:restore sevs-backup-2026-11-13_230000.zip
php artisan backup:restore sevs-backup-2026-11-13_230000.zip --force
```

The command:

1. Puts the app into maintenance mode.
2. Extracts the archive to a temporary folder.
3. Runs `PRAGMA integrity_check` on the snapshot.
4. Saves the current database as `pre-restore-*.sqlite` in `storage/app/backups`.
5. Replaces the database file and restores `storage/app/public`.
6. Clears caches and brings the app back online.

`.env` is **not** overwritten automatically. If the backup came from a different
installation, compare `env/.env` with the current `.env` before restoring it manually.

After restoring, sign in again — sessions from the live database are gone.

## Important

- **Copy backups off this computer.** A backup on the same disk does not survive a
  disk failure, theft, or ransomware. Download the ZIPs or copy the folder to an
  external drive / cloud folder regularly.
- **Treat backups as sensitive.** They contain student personal data, password
  hashes, and `APP_KEY`.
- **Test a restore** at least once per school year, using a spare copy of the
  project so you do not overwrite live data.
- Photos can make archives large. To exclude them, set `BACKUP_INCLUDE_PHOTOS=false`
  in `.env` (but then photo paths in restored records will be broken).

## Configuration (`.env`)

```dotenv
BACKUP_KEEP=14
BACKUP_INCLUDE_PHOTOS=true
BACKUP_INCLUDE_ENV=true
```
