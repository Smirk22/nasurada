# Login page — complete code walkthrough

This document explains the login page of the IETI SEVS project line by line, lists every file connected to it, and explains the code in those files.

---

## 0. First, the most important fact: there are TWO login pages

| File | Status | Evidence |
|---|---|---|
| `resources/views/login.blade.php` | **LIVE / actually used** | `routes/web.php:19` → `AuthController@showLoginForm` → `return view('login', ...)` |
| `resources/js/pages/auth/login.tsx` | **Dead / legacy code** | No route renders `auth/login`. Verified with `php artisan route:list`: only `GET login → AuthController@showLoginForm` and `POST login → AuthController@login` exist. |

`app/Providers/FortifyServiceProvider.php:49` still tells Fortify to render the React page for its own login view, but Fortify's `/login` routes are overridden by the custom routes in `routes/web.php`, so that configuration is never reached for `/login`.

**Conclusion:** when you review the real login page, start with `resources/views/login.blade.php`. The React file is kept in this document as "unused leftovers" so you know why it exists and what it would do if someone enabled it.

---

## 1. The complete request flow (real login)

```
Browser GET /
  └─ routes/web.php:16  Route::redirect('/', '/login')->name('home')
       └─ Browser GET /login
            ├─ middleware (bootstrap/app.php:25-32):
            │    HandleAppearance → HandleInertiaRequests → AddLinkHeaders…
            │    → PreventBackHistory → EnsureSystemAvailable → EnsureTemporaryPasswordChanged
            └─ AuthController@showLoginForm
                 ├─ already logged in? → redirectToDashboard()
                 ├─ issueLoginCaptcha(): random 1-9 + 1-9, sum saved in session
                 ├─ resolveLoginLockout(): read flashed old('email'), check DB lock flags
                 └─ view('login', [...]) with no-store headers

Browser POST /login  (form action = route('login.store'))
  └─ AuthController@login
       ├─ validate email/password/captcha
       ├─ pull captcha from session, compare
       ├─ find User (accounts table)
       ├─ block if must_reset_password or locked_until is in the future
       ├─ Auth::validate() password check; on failure: registerFailedLoginAttempt() + ActivityLog
       ├─ on success: clear counters, Auth::login(), session()->regenerate()
       ├─ temporary password? → password.force-change page
       └─ redirectToDashboard() by role
```

---

## 2. `resources/views/login.blade.php` — the live login page (338 lines)

This is a single self-contained Blade view. It is **not** a React/Inertia page. It uses Bootstrap from a CDN plus a custom stylesheet compiled by Vite.

### 2.1 HTML head (lines 1–10)

| Line | Code | Explanation |
|---|---|---|
| 1 | `<!DOCTYPE html>` | Tells the browser this is an HTML5 document. |
| 2 | `<html lang="en">` | Declares English as the document language (helps screen readers and translation tools). |
| 3–4 | `<head>` … | Contains metadata and asset links, not visible content. |
| 4 | `<meta charset="UTF-8">` | Uses UTF-8 encoding so symbols/accents render correctly. |
| 5 | `<meta name="viewport" content="width=device-width, initial-scale=1.0">` | Makes the page scale properly on phones. Without this, mobile browsers pretend to be a desktop and zoom out. |
| 6 | `<title>Sign in \| IETI SEVS</title>` | Browser tab title. |
| 7 | `<link href="https://cdn.jsdelivr.net/npm/bootstrap@...css" rel="stylesheet">` | Loads Bootstrap 5.3.3 CSS from a CDN. It provides `.mb-3`, `.form-control`, `.input-group`, `.form-check`, etc. **Dependency:** this page needs internet access to look correct. |
| 8 | Bootstrap Icons CSS CDN | Provides the `bi bi-*` icon classes used throughout (`bi-envelope`, `bi-lock`, `bi-eye`, `bi-robot`, `bi-shield-check`, …). |
| 9 | `@vite(['resources/css/login.css'])` | Blade/Vite directive. In development it points to the Vite dev server; in production it injects `<link>` tags from `public/build/manifest.json`. This is the project's own styling for the auth pages. |
| 10 | `</head>` | Ends the head. |

### 2.2 Page shell and branding (lines 11–22)

| Line | Code | Explanation |
|---|---|---|
| 11 | `<body class="auth-page auth-page--login">` | `auth-page` gets the shared auth styles; `auth-page--login` activates the extra "lock to viewport" rules at the end of `login.css` (lines 815–952). |
| 12 | `<main class="auth-shell">` | Main layout container: a two-column split (branding on the left, form panel on the right). |
| 13 | `@include('partials.auth-branding')` | Inserts `resources/views/partials/auth-branding.blade.php` — the photo + overlay + school name on the left half. Explained in §5. |
| 15 | `<section class="auth-panel">` | Right white panel containing header bar, form body, footer bar. |
| 16 | HTML comment | Just a reminder that the dark green bar with logos is at the top. |
| 17 | `<header class="auth-panel__bar auth-panel__bar--header">` | Top dark-green strip; `--header` adds rounded bottom corners. |
| 18 | `<div class="auth-panel__logos">` | Flex container that pushes the logos to the right (`margin-left:auto` in CSS). |
| 19 | `<img class="auth-panel__logo" src="{{ asset('images/group-logo.png') }}" alt="Group logo">` | `asset()` builds a URL to `public/`. Note the folder on disk is `public/Images` (capital I) while the URL says lowercase `images`. This works on Windows but will 404 on a case-sensitive Linux production server. |
| 20 | Second logo `ietilogo.png`, class `--round` makes it circular | Same case-sensitivity note. |

### 2.3 Form intro and messages (lines 24–47)

| Line | Code | Explanation |
|---|---|---|
| 24–25 | `.auth-panel__body` > `.auth-form-wrap` | Scrollable area and the white card that holds the form. |
| 26–33 | `.auth-form-wrap__intro` | The text above the form: eyebrow badge with shield icon ("Secure access"), `<h1>Welcome, user!</h1>`, and a lead paragraph. `aria-hidden="true"` on icons hides decorative icons from screen readers. |
| 35–40 | `@if (session('success'))` | Shows a green success alert if a previous request flashed a `success` message (e.g. after account creation in `AuthController@register`). |
| 36 | `role="status"` | Tells assistive tech this is a status message, not an urgent alert. |
| 38 | `{{ session('success') }}` | Blade escapes the value (`htmlspecialchars`) before printing — safe against XSS. |
| 42–47 | `@if ($errors->any())` | Laravel flashes validation errors to the session after a failed POST. |
| 43 | `role="alert"` | Announces the error immediately to screen readers. |
| 45 | `{{ $errors->first() }}` | Prints **only the first** error message. That is why a wrong CAPTCHA and a wrong password appear in the same single box instead of one box per field. |

### 2.4 The form itself (lines 49–135)

| Line | Code | Explanation |
|---|---|---|
| 49 | `<form action="{{ route('login.store') }}" method="POST" class="auth-form" id="loginForm" autocomplete="off">` | Submits to the named route `login.store` = `POST /login` (see `routes/web.php:20`). `autocomplete="off"` asks the browser not to restore old values; individual fields override this where useful. |
| 50 | `@csrf` | Blade outputs a hidden `_token` input. Laravel rejects POSTs without a valid CSRF token. |
| 52–71 | Email field group | `.mb-3` = Bootstrap bottom margin. `.input-group` places an icon box next to the input. |
| 53 | `<label class="form-label" for="email">Email</label>` | `for="email"` matches the input `id="email"`, so clicking the label focuses the input. |
| 55–57 | `<span class="input-group-text" id="email-icon">` with `bi-envelope` | Decorative icon inside the input group. |
| 59–69 | `<input type="email" id="email" class="form-control" name="email" value="{{ old('email') }}" placeholder="Enter your email" aria-describedby="email-icon" required autofocus autocomplete="email">` | `name="email"` is the key used by `$request->validate()` in the controller. `value="{{ old('email') }}"` repopulates the email after a failed login (set by `->onlyInput('email')`). `required` gives browser-side validation. `autofocus` focuses it on load. `autocomplete="email"` lets password managers fill it. |
| 73–97 | Password field group | Same icon pattern plus a show/hide button. |
| 80–87 | password input attributes | `type="password"` masks characters; JS flips it to `text`. `name="password"` matches validation. `required`. `autocomplete="current-password"` is the correct hint for a sign-in form. **No `value` attribute** — passwords are never sent back to the browser. |
| 88–95 | `<button type="button" class="btn auth-toggle" id="togglePassword" aria-label="Show password">` | `type="button"` prevents submitting the form. The JS at lines 192–200 swaps the eye icon and toggles the input type. |
| 99–111 | Remember me checkbox | `value="1"`, `name="remember"`. `{{ old('remember') ? 'checked' : '' }}` keeps it checked after a validation error. The controller reads it with `$request->boolean('remember')` and passes it to `Auth::login($user, $remember)` → Laravel writes a persistent remember token cookie. |
| 113–129 | CAPTCHA block | A simple math challenge, not Google reCAPTCHA. |
| 115–117 | `bi-robot` + "I'm not a robot" | Cosmetic label to look like a human check. |
| 118 | `What is {{ $a }} + {{ $b }}?` | `$a` and `$b` come from the controller's `issueLoginCaptcha()` (random 1–9 each). The **sum is stored in the session**, never in the page, so the user cannot read the answer from HTML. |
| 119–128 | number input `name="captcha"` | `type="number" inputmode="numeric"` brings up a numeric keypad on mobile. `autocomplete="off"`, `required`. |
| 131–134 | Submit button `id="loginSubmit"` | `type="submit"` posts the form. The arrow icon is decorative. |
| 137 | `.auth-form__meta` | Help text: contact the administrator. |
| 141–145 | Footer bar | `{{ date('Y') }}` prints the current year dynamically; second span is hidden on very small screens via Bootstrap's `d-none d-sm-inline`. |

### 2.5 The lockout modal markup (lines 149–174)

This hidden dialog is shown by JavaScript when the account that just failed is temporarily or permanently locked.

| Line | Code | Explanation |
|---|---|---|
| 149–160 | `<div id="lockoutModal" class="auth-lockout-modal" hidden role="dialog" aria-modal="true" aria-labelledby="lockoutModalTitle">` | `hidden` means it is invisible initially. `role="dialog"` + `aria-modal="true"` + `aria-labelledby` describe it properly to screen readers. |
| 156 | `data-lockout-until="{{ $lockoutUntil ?? '' }}"` | Server passes a Unix timestamp (seconds) while the lock is temporary. `?? ''` avoids errors if the key is missing. The JS reads it with `dataset.lockoutUntil`. |
| 157 | `data-lockout-admin="{{ !empty($lockoutAdmin) ? '1' : '0' }}"` | `1` = permanent/admin lock (`must_reset_password`), `0` = timed lock. |
| 158 | `data-lockout-message="{{ $lockoutMessage ?? '' }}"` | The human-readable reason from the controller. Blade escapes it; JS later assigns it with `textContent` (double-safe). |
| 159 | `data-lockout-email="{{ $lockoutEmail ?? '' }}"` | Which account is locked, so JS only blocks submissions for that exact email. |
| 161 | `.auth-lockout-modal__backdrop` | Dark translucent overlay covering the page. |
| 162–173 | `.auth-lockout-modal__dialog` | White card with icon, title, account line, message, timer, and a button. |
| 167, 169 | `hidden` on account and timer paragraphs | JS reveals these only when relevant. |
| 170–172 | `#lockoutSwitchAccount` button | "Sign in with another account" — clears the form and focuses the email field. |

### 2.6 Bootstrap JS and the inline `<script>` (lines 176–336)

Line 176 loads Bootstrap's JS bundle from the CDN (needed by the 2FA page's collapse; this page doesn't use it, but it's loaded anyway).

**Element references (178–187):** `getElementById` caches references to the password input, toggle button, email input, form, and every part of the modal. Using `?.` (optional chaining) means an accidentally missing element won't throw an error.

**State (189–190):**
- `lockoutTimerId` holds the `setInterval` id so it can be cancelled.
- `lockedEmail` starts from the `data-lockout-email` attribute, trimmed and lowercased so comparisons are consistent.

**`passwordToggle` click handler (192–200):**
- Checks if the input is currently `password`.
- Switches `type` between `password` and `text`.
- Swaps the icon class between `bi-eye` and `bi-eye-slash`.
- Updates `aria-label` for accessibility.

**`formatRemaining(seconds)` (202–215):**
- Clamps negative values to 0 with `Math.max(0, seconds)`.
- Splits into hours/minutes/seconds.
- If hours > 0 returns `HH:MM:SS`, otherwise `MM:SS` (`padStart(2,'0')` adds leading zeros).

**`hideLockoutModal()` (217–223):** sets `hidden = true` and clears the interval timer.

**`prepareOtherAccountLogin()` (225–229):** hides the modal, clears secrets but keeps the email, and focuses the email box so the user can type a different account.

**`showTimedLockout(untilTs, message, email)` (231–256):**
- Unhides the modal, sets title/message, reveals the timer.
- Shows the "Locked account" line only if an email is known.
- Inner `tick()` computes `remaining = untilTs - current Unix seconds`, updates the timer text, and auto-closes the modal at zero.
- `tick()` runs once immediately, then every 1000 ms via `setInterval`.

**`showAdminLockout(message, email)` (258–272):** same idea but for a permanent lock: title "Account locked", no countdown, message tells the user to contact the administrator.

**`lockoutSwitchAccount` listener (274):** wires the button to `prepareOtherAccountLogin`.

**`clearLoginForm(preserveEmail)` (276–283):**
- Empties `password` and `captcha` fields via `querySelectorAll`.
- Only empties the email when `preserveEmail` is false.

**`applyLockoutFromPage()` (285–303):**
- Reads the four `data-*` attributes.
- If `adminLocked` is `1` → permanent modal.
- Else if the timestamp is still in the future → timed modal.
- Otherwise returns false (no lock).

**Form submit listener (305–321):**
- Normalizes the typed email to lowercase/trimmed.
- Recomputes `adminLocked` and `stillTimedOut` from the dataset.
- If the typed email equals the locked email **and** the lock is still active, it calls `event.preventDefault()` and reopens the modal. This is a client-side guard so the user cannot submit the locked account from a cached page; the server independently blocks it too.

**`pageshow` listener (324–335):**
- Runs on a normal load **and** when the browser restores the page from the Back/Forward cache.
- Calls `applyLockoutFromPage()`, then clears password and captcha immediately and again after 50 ms. The double clear is a common trick to defeat browser/password-manager autofill that happens after the first clear.
- The empty `if (!locked && emailInput && !emailInput.value) { }` block at 332–334 does nothing — harmless dead code.

---

## 3. `app/Http/Controllers/AuthController.php` — the brain behind login (384 lines)

### 3.1 Imports (lines 1–12)
- `ActivityLog` — writes failed-login records.
- `User` — the account model (table `accounts`).
- `Carbon\Carbon` / `CarbonInterface` — date/time math for lockouts and the security guard's session timestamp.
- `Request`, `Auth` — HTTP input and authentication facade.
- `Password` — validation rule used in the (unused) `register()` method.
- `TwoFactorAuthenticationProvider` — Fortify's TOTP verifier for the 2FA challenge.

### 3.2 `showLoginForm(Request)` (16–35) — GET /login
1. Line 18: if someone is already authenticated, skip the login page and go straight to their dashboard.
2. Line 22: `[$a, $b] = $this->issueLoginCaptcha($request)` creates the math question.
3. Line 23: `resolveLoginLockout()` works out whether to show the lockout modal.
4. Lines 25–33: returns `view('login', …)` with five variables (`a`, `b`, `lockoutUntil`, `lockoutAdmin`, `lockoutMessage`, `lockoutEmail`) used by the Blade file above.
5. Line 34: sets `Cache-Control: no-store, no-cache, must-revalidate, max-age=0` so the login page is never cached (also set globally by `PreventBackHistory`).

### 3.3 `login(Request)` (37–133) — POST /login
**Validation (39–43):** `email` required + valid email format, `password` required, `captcha` required. A failure here redirects back with errors and repopulates only `email`.

**CAPTCHA check (45–52):**
- `session()->pull('login.captcha')` reads **and removes** the expected sum — one-time use.
- Rejects if the session value is missing, the input isn't digits only (`preg_match('/^\d+$/')`), or the integers differ.
- Error message: "The verification answer is incorrect."

**Account lookup and pre-checks (54–77):**
- `$remember = $request->boolean('remember')` safely converts checkbox/`"1"` to true.
- `User::where('email', …)->first()` finds the account.
- If `must_reset_password` is true → log the failure and show the permanent lockout.
- If `locked_until` is in the future → log and show a timed lockout with a countdown message.

**Password failure path (79–112):**
- `Auth::validate([...])` checks the password **without** logging the user in.
- If no user or bad password:
  - `registerFailedLoginAttempt($user)` increments the counter and may lock the account.
  - `$user->refresh()` reloads the freshly saved flags.
  - If the new state is `must_reset_password` or freshly time-locked, return the modal redirect; otherwise return to the form with `The provided credentials do not match our records.`
  - Every failure is written to `activity_logs` with the IP address.

**Success path (114–132):**
- Line 114: remembers whether a temporary password is present **before** clearing anything.
- Lines 116–119: resets `failed_login_attempts` to 0 and `locked_until` to null.
- Line 121: forgets `login.lockout_email` (see finding #6 in §15 — this session key is never actually read).
- Line 123: `Auth::login($user, $remember)` logs in and, if requested, sets the remember-me cookie.
- Line 124: `session()->regenerate()` prevents session fixation.
- Lines 126–130: a temporary password forces the one-time password-change page with a success flash.
- Line 132: otherwise `redirectToDashboard()`.

### 3.4 Two-factor methods (135–201)
- `showTwoFactorChallenge()` (135–146): gets the pending user from the session (`login.id`), redirects to login if absent, otherwise renders `two-factor.challenge` with the email and no-store headers.
- `verifyTwoFactorChallenge()` (148–194): validates `code`/`recovery_code`; checks a recovery code with `hash_equals` (timing-safe) and rotates it via `replaceRecoveryCode()`, or verifies a TOTP code using Fortify's provider after `decrypt($user->two_factor_secret)`. On success it pulls `login.remember`, clears `login.id`, logs the user in, regenerates the session, and redirects to the dashboard.
- `cancelTwoFactorChallenge()` (196–201): clears the pending session keys and returns to login.
- **Important:** nothing in `login()` ever sets `login.id` or redirects here, so these three methods are currently unreachable from the normal login flow (see finding #1 in §15).

### 3.5 `logout()` (203–210)
Logs out, invalidates the session, regenerates the CSRF token, redirects to `/login`. **Not wired to a route** — `route:list` shows `POST logout` is handled by Fortify's `AuthenticatedSessionController@destroy`. The code remains as an alternative.

### 3.6 `showRegisterForm()` / `register()` (212–233)
Legacy registration page/controller methods. `register()` validates username/email/password, creates a user with role `registrar`, hashes the password through the model cast, and redirects to login with a success message. No route points to these methods anymore, and `resources/views/register.blade.php` does not exist.

### 3.7 Private helpers
- `issueLoginCaptcha()` (238–245): `random_int(1, 9)` twice (cryptographically secure randomness), stores `a + b` in `login.captcha`, returns the two operands to the view.
- `logFailedLogin()` (247–255): calls `ActivityLog::record()` with the username (or the posted email / "Unknown"), role, `ACTION_FAILED_LOGIN`, and a reason ending with the request IP.
- `registerFailedLoginAttempt()` (257–273): increments `failed_login_attempts`; at 3 attempts → 30-second lock, at 8 → 1 minute, at 10+ → 1 hour **plus** `must_reset_password = true` and `temporary_password = null` (admin must reset).
- `lockoutMessage()` (275–292): formats a human message with ceil-rounded hours/minutes/seconds and correct singular/plural.
- `resolveLoginLockout()` (297–335): uses `old('email')` (the flashed input from the previous failed POST) to decide which account's lockout to display. Returns an array with `until`, `admin`, `message`, `email`. It deliberately only shows lockout UI for the account that just failed, so other staff can still sign in.
- `lockoutRedirect()` (337–344): stores the locked email in the session and redirects back with the error and only the email input preserved.
- `pendingTwoFactorUser()` (346–353): looks up the user id stored in `login.id`.
- `redirectToDashboard()` (355–383): if a temporary password exists → force-change page; otherwise lowercases/trims the role and redirects using `redirect()->intended()` (so a previously requested URL is honoured):
  - `superadmin` → `/superadmin`
  - `admin` → `/adashboard`
  - `security` → `/Security/dashboard`, and refreshes the `guard_logged_in_at` session timestamp so the live entry feed starts from this login
  - anything else → `/rdashboard` (default fallback)

---

## 4. `routes/web.php` — how the login URL is wired (126 lines)

| Lines | Code | Explanation |
|---|---|---|
| 1–13 | `use` imports | Bring the controllers and `Route` facade into scope. |
| 16 | `Route::redirect('/', '/login')->name('home')` | The root URL permanently/quickly redirects to login; named `home` for `route('home')`. |
| 19 | `Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login')` | Renders the Blade login page. |
| 20 | `Route::post('/login', [AuthController::class, 'login'])->name('login.store')` | Handles form submission. The odd name `login.store` is what the Blade form and Wayfinder helper use. |
| 22–25 | `/two-factor-verify` GET/POST + `/cancel` | Custom 2FA challenge routes (currently not reached from login). |
| 27 | Comment | Explains why logout uses Fortify's route instead. |
| 30–33 | `auth` group for `/password/change-required` | Forced password change after a temporary-password login. |
| 36–42 | `auth` group for `/settings/two-factor` | 2FA setup/disable/recovery routes. |
| 44–55 | `auth, role:Admin` group | Admin dashboard, user management, report exports. |
| 57–79 | `auth, role:SuperAdmin` group with `prefix('superadmin')` | Highest-privilege area, backups, settings. |
| 81–97 | `auth, role:SuperAdmin,Admin,Registrar` group | Registrar dashboard, student records, RFID scan mode. |
| 99–106 | `auth, role:Admin,Registrar,Security` group | Entry logs and summaries. |
| 108–124 | `auth, role:Admin,Security` group with `prefix('Security')` | Guard dashboard, visitors, kiosk, visitor RFID. |
| 126 | `require __DIR__.'/settings.php'` | Loads profile/security/appearance settings routes. |

All of these groups depend on login: the `auth` middleware sends guests to `route('login')`, and `role:...` sends wrong-role users back to their own dashboard.

---

## 5. `resources/views/partials/auth-branding.blade.php` (12 lines) — included on line 13 of login

| Line | Code | Explanation |
|---|---|---|
| 1 | `<div class="auth-branding">` | Left half of the split screen. |
| 2 | `<img class="auth-branding__bg" src="{{ asset('images/school.jpg') }}" alt="">` | Full-bleed background photo. Empty `alt` because it's decorative. Same lowercase `images/` vs `public/Images` case issue as the logos. |
| 3 | `<div class="auth-branding__overlay">` | Green gradient over the photo so the white text is readable. |
| 5 | `<div class="auth-branding__content">` | Holds the text, `z-index: 1` puts it above the overlay. |
| 6 | eyebrow span | "IETI College Marikina" in small uppercase letter-spaced text. |
| 7 | `<h1>` | "Student Entry Verification System". |
| 8–10 | `<p>` | Short description of the system. |
| 11–12 | closing tags | Ends the partial. |

---

## 6. Models used by login

### 6.1 `app/Models/User.php` (58 lines)

| Lines | Code | Explanation |
|---|---|---|
| 5–13 | imports | `HasFactory` for tests/seeders, `Notifiable`, `Authenticatable` (base class for auth), Fortify's `TwoFactorAuthenticatable` trait (recovery codes, secret handling), Carbon. |
| 15–32 | PHPDoc `@property` list | Documents all DB columns for IDE/static analysis: id, name, username, email, verification timestamp, password, failure counter, lock timestamp, reset flag, temporary password, 2FA fields, remember token, timestamps. |
| 33 | `#[Fillable([...])]` | PHP 8 attribute (Laravel 11+) listing mass-assignable fields: name, username, email, password, role, failed_login_attempts, locked_until, must_reset_password, temporary_password. |
| 34 | `#[Hidden([...])]` | These fields are omitted whenever the model is serialized to JSON: password, temporary password, 2FA secret/recovery codes, remember token. Important because 2FA shares user data with Inertia. |
| 35 | `class User extends Authenticatable` | The authentication model. |
| 38 | `use HasFactory, Notifiable, TwoFactorAuthenticatable;` | Adds factory support, notifications, and Fortify 2FA helpers (`recoveryCodes()`, `replaceRecoveryCode()`, etc.). |
| 40 | `protected $table = 'accounts';` | Laravel would normally look for `users`; this overrides it to the actual table. |
| 47–57 | `casts()` | `email_verified_at`, `two_factor_confirmed_at`, `locked_until` → Carbon dates (so `->isFuture()` works); `password` → `hashed` (auto `Hash::make` on assign); `must_reset_password` → bool; `failed_login_attempts` → int. |

### 6.2 `app/Models/ActivityLog.php` (123 lines)

| Lines | Code | Explanation |
|---|---|---|
| 9–11 | class + `HasFactory` | Standard Eloquent model for the `activity_logs` table. |
| 13–27 | constants | Named action strings: backup actions, `ACTION_LOGGED_IN`, `ACTION_FAILED_LOGIN`, `ACTION_LOGGED_OUT`. Using constants avoids typos. |
| 30–36 | `BACKUP_ACTIONS` | Array of backup-related actions. |
| 39–43 | `ACCESS_ACTIONS` | Array of login/logout/failed-login actions. |
| 45–50 | `$fillable` | Columns that can be mass-created: user_name, role, action, details. |
| 58–61 | `scopeBackupHistory()` | Local query scope: `ActivityLog::backupHistory()` filters to backup actions. |
| 69–72 | `scopeAccessHistory()` | Filters to login/logout events (used by admin access logs). |
| 80–83 | `scopeWithoutAccessActions()` | Filters them out (general activity feed). |
| 88–98 | `log()` | Helper that reads the currently authenticated user and calls `record()`. |
| 104–112 | `logAs()` | Helper for events where auth may already be cleared (logout) — takes an explicit user. Used by `AppServiceProvider`. |
| 114–122 | `record()` | The actual insert. `AuthController@logFailedLogin()` calls this with the email and IP. |

### 6.3 `app/Models/Setting.php` (67 lines) — used by maintenance middleware

- Line 11: `$fillable = ['key','value']`.
- `value()` (16–29): safely reads one setting; checks the table exists, catches any `Throwable`, returns a default.
- `boolean()` (34–37): converts `"1"/"0"/"true"/"false"` to a real bool with `filter_var`. `EnsureSystemAvailable` calls `Setting::boolean('maintenance_mode', false)`.
- `put()` (42–48): upserts a key/value.
- `allSettings()` (55–66): returns all rows as a key → value map; used by `AppServiceProvider`.

---

## 7. `resources/css/login.css` — every section explained (952 lines)

The file is shared by login, 2FA challenge, 2FA setup, and forced-password-change pages. Only the final block (lines 811–952) is login-specific.

### 7.1 Variables and reset (1–29)
- `:root` defines the palette: five greens, off-white background, muted text, soft border, error red. All later rules reference these with `var(--…)`, so changing one variable re-themes the whole page.
- `* { box-sizing: border-box }` makes padding/borders count inside declared widths.
- `html, body` reset margins, set full height, a system font stack, and the off-white background.

### 7.2 Split layout (35–185)
- `.auth-shell` — flex row, full viewport height.
- `.auth-branding` — left column, grows to 55%, photo aligned to the bottom, content hidden if it overflows.
- `.auth-branding__bg` — absolutely fills the column, `object-fit: cover` crops the photo, `brightness(0.55)` darkens it.
- `.auth-branding__overlay` — vertical green gradient from 35% to 90% opacity.
- `.auth-branding__content` — `z-index:1` above the overlay, fluid padding via `clamp()`, max width 640px.
- `.auth-branding__eyebrow / __title / __subtitle` — typography for the three text elements in the partial.
- `.auth-panel` — right column, 45%, white, shadow on its left edge, flex column with `justify-content: space-between` (bar at top, body, bar at bottom).
- `.auth-panel__bar` — dark green 64px strip. `--header` rounds the bottom corners, `--footer` rounds the top corners and uses smaller muted text.
- `.auth-panel__bar-label` — uppercase label used on the 2FA/force-change headers.
- `.auth-panel__logos / __logo / __logo--round` — logo alignment, height 38px, round styling for the college seal.
- `.auth-panel__body` — flex:1, centred content, fluid padding, internal vertical scroll with a thin custom scrollbar (WebKit rules).

### 7.3 Form card (191–241)
- `.auth-form-wrap` — max 420px white card, rounded 18px, 4px green top border, layered shadow, vertically centred with `margin-block:auto`.
- `.auth-form-wrap--wide` — 580px variant for the 2FA page.
- `.auth-form-wrap__intro` — spacing below the heading block.
- `.auth-form-wrap__eyebrow` — pill badge with light green background.
- `.auth-form-wrap__heading / __lead` — green heading and muted description.

### 7.4 Bootstrap control theming (247–355)
- `.auth-form .form-label` — small bold dark-green labels.
- `.auth-input-group .input-group-text` — icon box with no right border, left-rounded corners.
- `.auth-form .form-control` — 48px tall, soft border, off-white background.
- `.auth-input-group .form-control` — right-rounded to complete the group; `:focus` turns green with a soft halo.
- `::placeholder` — muted grey.
- `.auth-toggle` — the eye button: no left border, right-rounded, hover/focus states.
- `.form-check-input` rules — green checked state and focus halo.
- `.form-check-label` — muted small text.
- `.auth-captcha` — boxed area with border and rounded corners; its label, input, and focus state.

### 7.5 Buttons, modal, alerts, misc (357–602)
- `.auth-button` — 48px green primary button with hover darkening, active scale, and focus-visible ring.
- `.auth-lockout-modal*` — fixed full-screen overlay (`z-index:2000`), `[hidden]` override, backdrop, centred dialog, red circular icon, title/account/message/timer typography (tabular numbers keep the countdown from jittering).
- `.auth-button--secondary`, `.auth-outline-button`, `.auth-danger-button`, `.auth-text-button`, `.auth-link` — additional button/link variants used on the 2FA pages.
- `.auth-alert` / `.auth-alert--success` — error and success message boxes (these are what the Blade `@if ($errors->any())` and `session('success')` blocks render).
- `.auth-form__meta` — small help text.
- `.auth-divider` — "or" line with rules on both sides made from `::before`/`::after`.
- `.auth-rule` — generic separator.
- `.auth-code-input` — large centered 6-digit input with wide letter spacing.

### 7.6 2FA setup page styles (604–759)
Badges (`.two-factor-badge`, `--pending`), step list, QR container, section titles, secret key box, copy button, two-column recovery code grid, empty state, action blocks, and a `prefers-reduced-motion` rule that removes transitions for users who ask for less animation.

### 7.7 Responsive rules (760–809)
- `max-height` isn't handled here; width breakpoints at 860px (stack the columns, shrink branding to 240px) and 420px (smaller bars/logos, single-column recovery codes).

### 7.8 Login-only viewport lock (811–952)
- `.auth-page--login` — `height: 100dvh; overflow: hidden` (dvh = dynamic viewport height, better on mobile browsers with toolbars).
- `.auth-page--login .auth-shell / .auth-branding / .auth-panel` — all forced to exactly the viewport height so the page itself never scrolls; only `.auth-panel__body` scrolls (`align-items: safe center`, `overflow-y:auto`).
- `.auth-form-wrap` — reduced padding/margins with `clamp()` so the form fits short screens.
- `.auth-form .mb-3 / .mb-4` — Bootstrap margins tightened with `!important`.
- `@media (max-height: 720px)` — shorter bars/logos, smaller heading, 44px inputs/buttons, tighter CAPTCHA box.
- `@media (max-height: 640px)` — hides the eyebrow badge and help text entirely.
- `@media (max-width: 860px)` — branding takes 26vh, panel 74vh, subtitle hidden.
- `@media (max-width: 860px) and (max-height: 720px)` — branding 20vh, panel 80vh, footer bar hidden.

---

## 8. Middleware that runs on every login request

All are registered in `bootstrap/app.php` lines 25–32.

### 8.1 `app/Http/Middleware/HandleAppearance.php` (23 lines)
- Line 19: shares the `appearance` cookie value (or `'system'`) with every Blade view via `View::share`.
- Line 21: continues the request. The login view doesn't use it, but it runs anyway; Inertia's root view does.

### 8.2 `app/Http/Middleware/HandleInertiaRequests.php` (47 lines)
- Line 17: `$rootView = 'app'` → first Inertia visit loads `resources/views/app.blade.php`.
- `version()` (24–27): delegates to the parent for asset versioning.
- `share()` (36–46): adds `name`, `auth.user`, and `sidebarOpen` to all Inertia responses. Irrelevant to the Blade login page but globally registered.

### 8.3 `app/Http/Middleware/PreventBackHistory.php` (21 lines)
- Line 13: runs the request and captures the response.
- Lines 15–17: adds `Cache-Control: no-store, no-cache, must-revalidate, max-age=0`, `Pragma: no-cache`, `Expires: <past date>` headers so browsers don't show cached authenticated pages after logout. This is why the login page's own `Cache-Control` header in the controller is somewhat redundant.

### 8.4 `app/Http/Middleware/EnsureSystemAvailable.php` (42 lines)
- Line 20: if `maintenance_mode` is off → continue.
- Lines 24–32: even in maintenance mode, allow `login`, `login.store`, all `two-factor.*`, both force-change routes, `logout`, and all `superadmin.*` routes.
- Lines 36–38: also allow any signed-in Super Admin.
- Line 40: everyone else gets HTTP 503 (`resources/views/errors/503.blade.php`).
- **Effect on login:** the sign-in page always stays reachable during maintenance.

### 8.5 `app/Http/Middleware/EnsureTemporaryPasswordChanged.php` (29 lines)
- Line 13: gets the current user.
- Lines 15–25: if that user still has a `temporary_password`, they may only visit force-change, logout, or the login routes; everything else redirects to `password.force-change`.
- **Effect on login:** a user with a temp password can still reach login (needed to log in), but after login they cannot reach dashboards until they set a permanent password.

### 8.6 `app/Http/Middleware/RoleMiddleware.php` (42 lines) — alias `role`
- Line 11: `handle($request, $next, string ...$roles)` accepts one or more allowed roles from the route definition, e.g. `role:SuperAdmin,Admin,Registrar`.
- Lines 15–17: guest → redirect to `route('login')`.
- Lines 19–21: temp-password user → force-change page.
- Lines 23–24: normalizes both the user's role and allowed roles to lowercase/trimmed, so `SuperAdmin` and `superadmin` match.
- Lines 26–29: a Super Admin bypasses every role restriction.
- Lines 31–38: wrong role → redirected to that role's own dashboard; unknown role → 403.
- Line 40: allowed → continue.

### 8.7 `bootstrap/app.php` (60 lines) — the wiring
- Lines 3–13: imports for all middleware and the application builder.
- Lines 15–21: configures route files (`routes/web.php`, `api.php`, `console.php`) and the `/up` health check.
- Line 23: cookies `appearance` and `sidebar_state` are the only ones not encrypted (JS needs to read them).
- Lines 25–32: the six web middleware appended after Laravel's defaults, in execution order.
- Lines 34–37: registers the `role` and `password.changed` aliases.
- Lines 40–54: `redirectUsersTo()` — where Laravel's guest middleware sends already-authenticated users (temp password → force-change, else role-based route). The custom login route doesn't use guest middleware, so `AuthController@showLoginForm` performs the same check itself.
- Lines 56–60: JSON error rendering for API requests or `Accept: application/json`.

---

## 9. Providers and configuration connected to login

### 9.1 `app/Providers/AppServiceProvider.php` (118 lines)
- `boot()` (32–37) calls three setup methods.
- `logAccessEvents()` (43–60) listens to Laravel's `Login` and `Logout` events and writes `ACTION_LOGGED_IN` / `ACTION_LOGGED_OUT` rows with the guard name and IP. This covers Fortify's logout route even though `AuthController@logout` is unused.
- `configureDefaults()` (65–84): uses immutable Carbon dates, blocks destructive DB commands in production, and sets the global password rule (min 12, mixed case, letters, numbers, symbols, plus `uncompromised()` in production).
- `configureApplicationSettings()` (92–117): safely shares `siteName` and `maintenanceMode` with views (with a schema check for fresh installs) and applies backup settings from the DB.

### 9.2 `app/Providers/FortifyServiceProvider.php` (80 lines)
- Line 41: custom password-reset action.
- Lines 49–52: **unused for `/login`** — configures Fortify's login view as the React page with `canResetPassword` and `status` props.
- Lines 54–64: Fortify's reset/forgot/confirm password views (also React).
- Lines 73–77: defines Fortify's `login` rate limiter: 5 requests/minute keyed by lowercased username + IP. Because the custom `POST /login` route wins, **this limiter is not applied to the real login endpoint**.

### 9.3 `config/auth.php` (117 lines)
- Line 19: default guard from `AUTH_GUARD` (defaults to `web`).
- Lines 40–45: `web` guard uses the session driver and the `users` provider.
- Lines 64–68: provider is Eloquent with `App\Models\User` (via `AUTH_MODEL` env).
- Lines 95–102: password reset broker table `password_reset_tokens`, 60-minute token expiry, 60-second throttle.
- Line 115: password confirmation window = 10800 seconds (3 hours).

### 9.4 `config/session.php` (233 lines — key values)
- Line 21: driver defaults to `database` (the `sessions` table from the first migration).
- Line 35: lifetime 120 minutes.
- Line 37: `expire_on_close` false by default.
- Line 50: `encrypt` false by default.
- Line 89: table name `sessions`.
- Line 130: cookie name from env.
- Line 172: `secure` from env (should be true in production HTTPS).
- Line 185: `http_only` true → JavaScript cannot read the session cookie.
- Line 202: `same_site` `lax` → balances CSRF protection with normal navigation.

### 9.5 `config/fortify.php` (153 lines — key values)
- Line 18: guard `web`; line 31: password broker `users`.
- Line 48: Fortify's username field is `email` (matches the form).
- Line 63: usernames lowercased when saved.
- Line 76: Fortify `home` is `/dashboard` (not used by custom controller, which has its own role routing).
- Line 89/104: no prefix, `web` middleware.
- Lines 117–119: login limiter name `login`.
- Line 132: `views => true`.
- Lines 145–151: email verification and 2FA features enabled (with confirmation).

### 9.6 `vite.config.ts` (31 lines)
- Lines 1–7: plugins — Laravel Vite, Inertia, React (with the React Compiler babel plugin), Tailwind v4, Bunny fonts, Wayfinder (generates TS route helpers with `formVariants: true`).
- Line 12: entry points include `resources/css/login.css`, which is why `@vite(['resources/css/login.css'])` works in the Blade view.
- Lines 20–29: the other plugins power the React/Inertia side of the app.

### 9.7 `public/index.php` (20 lines)
Front controller for every request: records start time, honours Laravel maintenance mode file, loads Composer autoload, bootstraps `bootstrap/app.php`, and calls `$app->handleRequest(Request::capture())`. All login requests pass through this file (via the web server's rewrite rules in `public/.htaccess`).

---

## 10. Database migrations behind login

| File | Lines | What it adds |
|---|---|---|
| `0001_01_01_000000_create_users_table.php` | 14–23 | Creates `accounts` with id, unique username, unique email, email_verified_at, password, role (default `registrar`), rememberToken, timestamps. |
| same file | 25–29 | `password_reset_tokens` table (email primary key, token, created_at). |
| same file | 31–38 | `sessions` table: id, user_id, ip_address, user_agent, payload, last_activity — the database session store used by login. |
| same file | 44–49 | `down()` drops all three tables. |
| `2026_09_19_071233_add_role_to_accounts_table.php` | 11–15 | Guarded `hasColumn` check, adds `role` varchar(20) default `Registrar` after email (idempotent re-run). |
| `2026_10_31_000000_add_two_factor_columns_to_accounts_table.php` | 11–15 | Adds `two_factor_secret`, `two_factor_recovery_codes`, `two_factor_confirmed_at`. |
| `2026_11_13_000000_add_login_lockout_columns_to_accounts_table.php` | 11–15 | Adds `failed_login_attempts` (unsigned int, default 0), `locked_until` (nullable timestamp), `must_reset_password` (bool, default false). These are the columns the lockout logic uses. |
| `2026_11_13_000001_add_temporary_password_to_accounts_table.php` | 11–13 | Adds nullable `temporary_password` — the one-time password forced-change flow. |
| `2026_11_19_000000_create_settings_table.php` | 11–18 | Creates `settings` key/value table used by the maintenance middleware. |
| `2026_11_26_000000_add_name_to_accounts_table.php` | 11–15 | Adds nullable `name` (guarded by `hasColumn`). |
| `2026_09_19_041253_create_activity_logs_table.php` | 11–18 | Creates `activity_logs` (user_name, role, action, details, timestamps) where failed logins are recorded. Also creates `incidents`. |

Seeders that create accounts you can log in with:
- `database/seeders/AccountSeeder.php` lines 13–29: `admin@ieti.edu.ph / admin12345` (Admin) and `andrew@ieti.edu.ph / andrew12345` (Registrar), inserted with `updateOrInsert` and `Hash::make`.
- `database/seeders/SuperAdminSeeder.php` lines 20–33: `superadmin@ieti.edu.ph / superadmin12345` (SuperAdmin), with lock flags explicitly reset. The class docblock warns to change these before public deployment.
- `database/factories/UserFactory.php` lines 25–33: test accounts with password `password`. It does **not** set the required `username` column and its `withTwoFactor()` method (line 49) is an empty stub — this is why the auth tests fail.

---

## 11. Post-login pages connected to login

### 11.1 `app/Http/Controllers/ForcedPasswordChangeController.php` (68 lines)
- `edit()` (12–23): redirects away if there is no user or no temporary password; otherwise renders `auth.force-change-password` with no-store headers.
- `update()` (25–57): re-checks the temp password, validates `password` with `confirmed` + `Password::default()`, rejects reusing the temporary password or the current password (`hash_equals` / `Hash::check`), then saves a new hash and clears `temporary_password`, failure counter, lock timestamp, and `must_reset_password`. Regenerates the session and redirects to the role dashboard with a success message.
- `dashboardPath()` (59–67): role → URL map, same as `AuthController@redirectToDashboard`.

### 11.2 `resources/views/auth/force-change-password.blade.php` (108 lines)
Same shell/CSS/partial as login. Lines 37–42 show the flashed `success` message, 44–49 show errors, 51–95 are the two-password form (`password`, `password_confirmation`) posting to `password.force-change.update`, and 97–102 are a POST logout form.

### 11.3 `resources/views/two-factor/challenge.blade.php` (124 lines)
Same shell/CSS/partial. Line 34 prints the pending user's email; 38–43 show errors; 45–68 are the 6-digit code form; 70–76 are a Bootstrap `collapse` link for the recovery-code form; 78–103 are the hidden recovery form; 105–111 are the "Back to sign in" cancel form; 122 loads Bootstrap JS (needed for the collapse). Reached only via `AuthController@showTwoFactorChallenge`, which nothing currently calls.

---

## 12. The unused React login page (for completeness)

### 12.1 `resources/js/pages/auth/login.tsx` (94 lines)
| Lines | Code | Explanation |
|---|---|---|
| 1 | `import { Form, Head } from '@inertiajs/react'` | Inertia's React helpers: `Head` sets the page title, `Form` builds a form bound to a route. |
| 2–8 | component imports | `InputError`, `PasswordInput`, UI primitives (`Button`, `Checkbox`, `Input`, `Label`, `Spinner`). |
| 9 | `import { store } from '@/routes/login'` | Wayfinder-generated helper for `POST /login`. |
| 11–13 | `type Props = { status?: string }` | Optional flash status from Fortify. |
| 15 | `export default function Login({ status }: Props)` | React component. |
| 18 | `<Head title="Log in" />` | Sets browser title via Inertia. |
| 20–24 | `<Form {...store.form()} resetOnSuccess={['password']} …>` | Builds a form with action `/login`, method POST (from `store.form()`), clears the password field after success, and lays children out with a flex gap. |
| 25 | `{({ processing, errors }) => (…)}` | Render-prop giving submit state and validation errors. |
| 29–40 | Email field | Label + `Input` with `name="email"`, required, autofocus, tab index 1, autocomplete; `InputError` shows `errors.email`. |
| 43–56 | Password field | `PasswordInput` with show/hide eye, tab index 2; `InputError` for `errors.password`. |
| 58–65 | Remember me | Radix `Checkbox` with `name="remember"`, tab index 3, plus label. |
| 67–76 | Submit `Button` | Disabled while `processing`, shows `Spinner`, tab index 4, `data-test` hook for tests. |
| 82–86 | `status` block | Green status message if present. |
| 91–94 | `Login.layout` metadata | Title/description used by `AuthLayout` if this page were rendered. |
| **Missing** | **no CAPTCHA field** | The live controller requires `captcha`, so if this page were wired up, every login would fail validation. More proof it's stale. |

`resources/js/app.tsx` lines 11–37 is the Inertia app bootstrap: it chooses `AuthLayout` for pages whose name starts with `auth/`, wraps the app in a tooltip provider and toaster, sets progress colour, and calls `initializeTheme()`.

### 12.2 Components imported by the React login page
- `components/input-error.tsx` (17 lines): renders a red `<p>` only when `message` exists; `cn()` merges Tailwind classes.
- `components/password-input.tsx` (37 lines): wraps `Input`, keeps `showPassword` state, toggles `type` between text/password, renders an `Eye`/`EyeOff` button with correct `aria-label` and `tabIndex={-1}`.
- `components/ui/button.tsx` (58 lines): shadcn-style button using `class-variance-authority` (`cva`) for variant/size classes; `asChild` swaps in Radix `Slot`.
- `components/ui/checkbox.tsx` (30 lines): Radix checkbox wrapper with a check icon and Tailwind state classes.
- `components/ui/input.tsx` (21 lines): plain `<input>` with theme/invalid/focus Tailwind classes.
- `components/ui/label.tsx` (22 lines): Radix label wrapper.
- `components/ui/spinner.tsx` (16 lines): animated `Loader2Icon` with `role="status"`.
- `lib/utils.ts` (12 lines): `cn()` = `twMerge(clsx(...))` for conditional Tailwind classes, and `toUrl()` helper.
- `routes/login/index.ts` (61 lines): generated Wayfinder helper. `store` returns `{ url:'/login', method:'post' }`; `store.form()` returns `{ action:'/login', method:'post' }` used by `<Form>`. Its docblocks mention `AuthController::store`, but the controller method is actually `login` — stale generated comment.
- `resources/js/actions/App/Http/Controllers/AuthController.ts` (325 lines): another generated Wayfinder file exporting typed helpers for all AuthController methods. Not imported by the login page.

---

## 13. Tests connected to login

`tests/Feature/Auth/AuthenticationTest.php` (92 lines) — **currently broken/stale**. I ran `php artisan test --filter=AuthenticationTest`: 6 tests, 1 passed, 5 errors.

| Lines | Test | Explanation / problem |
|---|---|---|
| 15–20 | `test_login_screen_can_be_rendered` | GETs `route('login')` and asserts 200. This is the only passing test. |
| 22–33 | `test_users_can_authenticate_using_the_login_screen` | Creates a factory user, POSTs email/password, asserts authenticated and redirect to `route('dashboard')` — but the test DB insert fails because `username` is NOT NULL and the factory doesn't set it. It also omits the required `captcha`, and `route('dashboard')` doesn't exist in `route:list`. |
| 35–54 | `test_users_with_two_factor_enabled_are_redirected_to_two_factor_challenge` | Expects Fortify's `two-factor.login` behaviour, but `UserFactory::withTwoFactor()` is an empty stub, so it throws a return-type error. |
| 56–66 | `test_users_can_not_authenticate_with_invalid_password` | Same missing `username` error; also missing `captcha`. |
| 68–77 | `test_users_can_logout` | `POST route('logout')` exists (Fortify), but factory insert fails first. |
| 79–91 | `test_users_are_rate_limited` | Manually increments Fortify's limiter key and expects 429. The custom `POST /login` doesn't use that limiter, so even after fixing the factory this assertion would need rethinking. |

`phpunit.xml` lines 20–35 configure an in-memory SQLite test DB, so running the tests does not touch your real `database/database.sqlite`.

---

## 14. Full connected-file list (quick reference)

**Directly on the login request path**
1. `routes/web.php` (lines 16, 19–20)
2. `app/Http/Controllers/AuthController.php`
3. `resources/views/login.blade.php`
4. `resources/views/partials/auth-branding.blade.php`
5. `resources/css/login.css`
6. `app/Models/User.php`
7. `app/Models/ActivityLog.php`
8. `app/Models/Setting.php`
9. `public/index.php`
10. `bootstrap/app.php`
11. `app/Http/Middleware/{HandleAppearance,HandleInertiaRequests,AddLinkHeadersForPreloadedAssets(vendor),PreventBackHistory,EnsureSystemAvailable,EnsureTemporaryPasswordChanged,RoleMiddleware}.php`
12. `config/{auth,session,fortify,app}.php`
13. `app/Providers/{AppServiceProvider,FortifyServiceProvider}.php`
14. `vite.config.ts`
15. `public/build/manifest.json` + `public/build/assets/login-*.css/js` (built output)
16. `public/{Images/group-logo.png,Images/ietilogo.png,Images/school.jpg}`

**Database / state**
17. `database/migrations/0001_01_01_000000_create_users_table.php`
18. `database/migrations/2026_09_19_071233_add_role_to_accounts_table.php`
19. `database/migrations/2026_09_19_041253_create_activity_logs_table.php`
20. `database/migrations/2026_10_31_000000_add_two_factor_columns_to_accounts_table.php`
21. `database/migrations/2026_11_13_000000_add_login_lockout_columns_to_accounts_table.php`
22. `database/migrations/2026_11_13_000001_add_temporary_password_to_accounts_table.php`
23. `database/migrations/2026_11_19_000000_create_settings_table.php`
24. `database/migrations/2026_11_26_000000_add_name_to_accounts_table.php`
25. `database/seeders/{AccountSeeder,SuperAdminSeeder}.php`
26. `database/factories/UserFactory.php`

**After login / alternatives**
27. `app/Http/Controllers/ForcedPasswordChangeController.php`
28. `resources/views/auth/force-change-password.blade.php`
29. `resources/views/two-factor/challenge.blade.php`
30. `app/Http/Controllers/SuperAdminController.php` (unlock via temporary password), `app/Http/Controllers/AdminController.php`
31. `tests/Feature/Auth/AuthenticationTest.php`

**Unused React/Inertia login stack (legacy)**
32. `resources/js/pages/auth/login.tsx`
33. `resources/js/app.tsx`, `resources/js/layouts/auth-layout.tsx`, `resources/js/layouts/auth/auth-*.tsx`
34. `resources/js/components/{input-error,password-input}.tsx`, `resources/js/components/ui/{button,checkbox,input,label,spinner}.tsx`, `resources/js/lib/utils.ts`
35. `resources/js/routes/login/index.ts`, `resources/js/actions/App/Http/Controllers/AuthController.ts`
36. `resources/views/app.blade.php` (Inertia root view)

---

## 15. Review findings (things worth discussing)

1. **2FA is not enforced at login.** `AuthController@login()` logs the user in directly and never sets `login.id` or redirects to the challenge. Fortify's own `POST /login` (which would do that) is shadowed by the custom route. The custom `/two-factor-verify` pages and `showTwoFactorChallenge()` are therefore unreachable in practice, even though 2FA can be enabled in settings.
2. **Fortify's login rate limiter is bypassed.** The limiter in `FortifyServiceProvider` (5/min) only guards Fortify's route. The real custom endpoint has per-account lockout (3/8/10 attempts) but no global per-IP throttle, so an attacker can spray many different emails without being throttled.
3. **The React login page is dead code** and has no CAPTCHA field; if someone re-enables it, login breaks. `Fortify::loginView()` and the Wayfinder docblocks are stale for the same reason.
4. **`login.lockout_email` session key is written but never read** (`AuthController` lines 121, 309, 332, 339). The modal gets its email from `old('email')`/`data-lockout-email` instead. It can be removed or actually used.
5. **Case-sensitive asset paths:** Blade requests `images/...` but the folder is `public/Images`. Works on Windows; will break on Linux production.
6. **Hard-coded seeded passwords** (`admin12345`, `andrew12345`, `superadmin12345`) in seeders — the SuperAdminSeeder comment acknowledges this but they should be changed/removed before deployment.
7. **Temporary passwords are stored in plain text** in `accounts.temporary_password` so `hash_equals()` can compare them. This is intentional for the one-time flow, but it means DB read access reveals active temp passwords.
8. **Auth tests are stale** and currently fail (1/6 passing) — missing `username` in the factory, empty `withTwoFactor()`, missing CAPTCHA in posts, and references to routes/behaviour that no longer exist.
9. Minor: the empty `if` block in `login.blade.php:332–334` and `AuthController@register`/`showRegisterForm` are leftovers with no routes.
