# How to open IETI SEVS on another PC

Follow these steps to move the whole system (program + data) to a new Windows computer.

## 1. Install these on the new PC

| Software | Version | Download |
|---|---|---|
| PHP | 8.3 or newer (8.5 works) | https://windows.php.net/download/ |
| Composer | latest | https://getcomposer.org/download/ |
| Node.js | 20.19+ or 22+ (24 LTS works) | https://nodejs.org/ |

When installing PHP:

- Extract it to a simple folder, for example `C:\php`.
- Add that folder to **Path** (Windows Settings → System → About → Advanced system settings → Environment Variables → Path → New → `C:\php`).
- In `C:\php\php.ini`, remove the `;` in front of these lines so the extensions are enabled:

```ini
extension=pdo_sqlite
extension=sqlite3
extension=mbstring
extension=openssl
extension=curl
extension=fileinfo
extension=zip
extension=intl
```

Check it worked by opening Command Prompt and typing `php -v`, `composer --version`, and `node -v`.

## 2. Copy the whole project folder

On the **old PC**, copy the entire `nasurada` folder to a USB drive. Do not copy only some files — hidden files and the data folders matter:

- `.env` (contains the app key and settings)
- `database\database.sqlite` (all student/account data)
- `storage\app\public\student-photos` (uploaded photos)
- `storage\app\backups` (previous backups — optional but recommended)

Easiest way: in File Explorer, right-click the `nasurada` folder → **Copy**, paste it to the USB drive. Hidden files are copied with the folder.

On the **new PC**, paste it somewhere simple, for example `C:\SEVS\nasurada`.

> If you cloned from Git instead of copying the folder, `.env`, the database, and the photos are **not** included. Copy them separately from the old PC (or from a recent backup ZIP) before continuing.

## 3. Prepare the project on the new PC

Open Command Prompt in `C:\SEVS\nasurada` (click the address bar, type `cmd`, press Enter) and run these one at a time:

```bat
composer install
npm install
npm run build
php artisan migrate --force
php artisan storage:link
```

- The first two commands can take a few minutes.
- The database is just one file: `database\database.sqlite`. If you copied it, there is nothing to create. If you did not copy it, create an empty file first: `type nul > database\database.sqlite` (in Command Prompt). You do **not** need to install MySQL.
- `migrate` only applies missing changes; it does **not** erase your copied data. It creates the tables inside that file if it is empty.
- **Do not run `php artisan db:seed`** when using the copied database. The seeders reset the demo account passwords and add sample students/entry logs.
- Only run `php artisan db:seed` if you want a brand-new empty system with demo data (after creating the empty file above). Change the seeded passwords after logging in.
- If you did **not** copy `.env`, run `copy .env.example .env` and then `php artisan key:generate` **before** `npm run build`.
- If you **did** copy `.env`, do **not** run `key:generate` — a new key breaks saved logins and encrypted data.
- The shortcut `ops\start-sevs.bat` runs `npm install` and `npm run build` **automatically** if `node_modules` or `public\build\manifest.json` is missing. Type them yourself only if you want to see errors, or after changing front-end code.

## 4. Open the system

Double-click:

```text
C:\SEVS\nasurada\ops\start-sevs.bat
```

This starts the server and opens the login page in your browser at:

```text
http://localhost:8000/login
```

On a freshly copied folder, the first launch may take a few minutes with no visible progress while the hidden `npm` commands finish. Later launches are fast. Run the commands in step 3 manually once if you want to see the progress/errors.

A small popup also shows a LAN address (like `http://192.168.x.x:8000/login`). Use that address if you want to open the system from a **phone or another PC on the same Wi‑Fi/network**. If it cannot connect, run `ops\allow-lan-access.bat` on the PC that is running the system and confirm the Windows Firewall prompt.

Log in with one of the accounts from the copied database. After moving, it is a good idea to create a fresh backup (Admin Dashboard → Database Backups → Backup Now).

## 5. If something goes wrong

| Problem | Fix |
|---|---|
| `php is not recognized` | PHP is not in Path. Add `C:\php` to Environment Variables, then close and reopen Command Prompt. |
| Browser shows errors / blank page | In the project folder run `npm run build`, then `php artisan config:clear`. Refresh the page. |
| Port 8000 already in use | The launcher will not start a second server; just open `http://localhost:8000/login`. To use a different port, change `PORT = 8000` in `ops\start-sevs.vbs` and `APP_URL` in `.env`. |
| Login works but saved data looks unreadable | You used a different `APP_KEY`. Copy the original `.env` from the old PC back into the project. |
| Need the old data on the new PC | Copy the latest ZIP from `storage\app\backups`, then run `php artisan backup:restore <zip-name> --force`. See `ops\BACKUP.md`. |

## 6. To stop the system

Close the black "PHP Development Server" command window, or press `Ctrl + C` in it. Double-click `start-sevs.bat` again to start it later.
