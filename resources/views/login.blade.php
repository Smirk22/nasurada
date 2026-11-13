<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign in | IETI SEVS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    @vite(['resources/css/login.css'])
</head>
<body class="auth-page auth-page--login">
    <main class="auth-shell">
        @include('partials.auth-branding')

        <section class="auth-panel">
            <!-- Top dark green header bar with the school logos aligned right -->
            <header class="auth-panel__bar auth-panel__bar--header">
                <span class="auth-panel__bar-label d-none d-sm-inline">Secure staff portal</span>
                <div class="auth-panel__logos">
                    <img class="auth-panel__logo" src="{{ asset('images/group-logo.png') }}" alt="Group logo">
                    <img class="auth-panel__logo auth-panel__logo--round" src="{{ asset('images/ietilogo.png') }}" alt="IETI College logo">
                </div>
            </header>

            <div class="auth-panel__body">
                <div class="auth-form-wrap">
                    <div class="auth-form-wrap__intro">
                        <span class="auth-form-wrap__eyebrow">
                            <i class="bi bi-shield-check" aria-hidden="true"></i>
                            Secure access
                        </span>
                        <h1 class="auth-form-wrap__heading">Welcome, user!</h1>
                        <p class="auth-form-wrap__lead">Enter your credentials to continue to the IETI SEVS portal.</p>
                    </div>

                    @if (session('success'))
                        <div class="auth-alert auth-alert--success" role="status">
                            <i class="bi bi-check-circle" aria-hidden="true"></i>
                            <span>{{ session('success') }}</span>
                        </div>
                    @endif

                    @if ($errors->any())
                        <div class="auth-alert" role="alert">
                            <i class="bi bi-exclamation-triangle" aria-hidden="true"></i>
                            <span>{{ $errors->first() }}</span>
                        </div>
                    @endif

                    <form action="{{ route('login.store') }}" method="POST" class="auth-form" id="loginForm" autocomplete="off">
                        @csrf

                        <div class="mb-3">
                            <label class="form-label" for="email">Email</label>
                            <div class="input-group auth-input-group">
                                <span class="input-group-text" id="email-icon">
                                    <i class="bi bi-envelope" aria-hidden="true"></i>
                                </span>
                                <input
                                    type="email"
                                    id="email"
                                    class="form-control"
                                    name="email"
                                    value="{{ old('email') }}"
                                    placeholder="Enter your email"
                                    aria-describedby="email-icon"
                                    required
                                    autofocus
                                    autocomplete="email"
                                >
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="password">Password</label>
                            <div class="input-group auth-input-group">
                                <span class="input-group-text">
                                    <i class="bi bi-lock" aria-hidden="true"></i>
                                </span>
                                <input
                                    type="password"
                                    id="password"
                                    class="form-control"
                                    name="password"
                                    placeholder="Enter your password"
                                    required
                                    autocomplete="current-password"
                                >
                                <button
                                    type="button"
                                    class="btn auth-toggle"
                                    id="togglePassword"
                                    aria-label="Show password"
                                >
                                    <i class="bi bi-eye" aria-hidden="true"></i>
                                </button>
                            </div>
                        </div>

                        <div class="mb-3">
                            <div class="form-check">
                                <input
                                    class="form-check-input"
                                    type="checkbox"
                                    value="1"
                                    id="remember"
                                    name="remember"
                                    {{ old('remember') ? 'checked' : '' }}
                                >
                                <label class="form-check-label" for="remember">Remember me</label>
                            </div>
                        </div>

                        <div class="auth-captcha mb-4">
                            <div class="auth-captcha__label">
                                <i class="bi bi-robot" aria-hidden="true"></i>
                                I'm not a robot
                            </div>
                            <label class="form-label" for="captcha">What is {{ $a }} + {{ $b }}?</label>
                            <input
                                type="number"
                                inputmode="numeric"
                                id="captcha"
                                class="form-control"
                                name="captcha"
                                placeholder="Enter the answer"
                                required
                                autocomplete="off"
                            >
                        </div>

                        <button type="submit" class="auth-button w-100" id="loginSubmit">
                            Sign in
                            <i class="bi bi-arrow-right ms-1" aria-hidden="true"></i>
                        </button>
                    </form>

                    <p class="auth-form__meta">Having trouble signing in? Contact the system administrator.</p>
                </div>
            </div>

            <!-- Bottom dark green footer bar -->
            <footer class="auth-panel__bar auth-panel__bar--footer">
                <span>&copy; {{ date('Y') }} IETI College</span>
                <span class="d-none d-sm-inline">Student Entry Verification System</span>
            </footer>
        </section>
    </main>

    <div
        id="lockoutModal"
        class="auth-lockout-modal"
        hidden
        role="dialog"
        aria-modal="true"
        aria-labelledby="lockoutModalTitle"
        data-lockout-until="{{ $lockoutUntil ?? '' }}"
        data-lockout-admin="{{ !empty($lockoutAdmin) ? '1' : '0' }}"
        data-lockout-message="{{ $lockoutMessage ?? '' }}"
        data-lockout-email="{{ $lockoutEmail ?? '' }}"
    >
        <div class="auth-lockout-modal__backdrop"></div>
        <div class="auth-lockout-modal__dialog">
            <div class="auth-lockout-modal__icon" aria-hidden="true">
                <i class="bi bi-shield-lock"></i>
            </div>
            <h2 id="lockoutModalTitle" class="auth-lockout-modal__title">Account temporarily locked</h2>
            <p id="lockoutModalAccount" class="auth-lockout-modal__account" hidden></p>
            <p id="lockoutModalMessage" class="auth-lockout-modal__message"></p>
            <p id="lockoutModalTimer" class="auth-lockout-modal__timer" hidden></p>
            <button type="button" class="auth-button auth-lockout-modal__btn" id="lockoutSwitchAccount">
                Sign in with another account
            </button>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const passwordInput = document.getElementById('password');
        const passwordToggle = document.getElementById('togglePassword');
        const emailInput = document.getElementById('email');
        const loginForm = document.getElementById('loginForm');
        const lockoutModal = document.getElementById('lockoutModal');
        const lockoutModalMessage = document.getElementById('lockoutModalMessage');
        const lockoutModalTimer = document.getElementById('lockoutModalTimer');
        const lockoutModalAccount = document.getElementById('lockoutModalAccount');
        const lockoutSwitchAccount = document.getElementById('lockoutSwitchAccount');
        const lockoutModalTitle = document.getElementById('lockoutModalTitle');

        let lockoutTimerId = null;
        let lockedEmail = (lockoutModal?.dataset.lockoutEmail || '').trim().toLowerCase();

        passwordToggle?.addEventListener('click', function () {
            const isHidden = passwordInput.type === 'password';

            passwordInput.type = isHidden ? 'text' : 'password';
            passwordToggle.innerHTML = isHidden
                ? '<i class="bi bi-eye-slash" aria-hidden="true"></i>'
                : '<i class="bi bi-eye" aria-hidden="true"></i>';
            passwordToggle.setAttribute('aria-label', isHidden ? 'Hide password' : 'Show password');
        });

        function formatRemaining(seconds) {
            const total = Math.max(0, seconds);
            const hours = Math.floor(total / 3600);
            const minutes = Math.floor((total % 3600) / 60);
            const secs = total % 60;

            if (hours > 0) {
                return String(hours).padStart(2, '0') + ':' +
                    String(minutes).padStart(2, '0') + ':' +
                    String(secs).padStart(2, '0');
            }

            return String(minutes).padStart(2, '0') + ':' + String(secs).padStart(2, '0');
        }

        function hideLockoutModal() {
            lockoutModal.hidden = true;
            if (lockoutTimerId) {
                clearInterval(lockoutTimerId);
                lockoutTimerId = null;
            }
        }

        function prepareOtherAccountLogin() {
            hideLockoutModal();
            clearLoginForm(false);
            emailInput?.focus();
        }

        function showTimedLockout(untilTs, message, email) {
            lockoutModal.hidden = false;
            lockoutModalTitle.textContent = 'Account temporarily locked';
            lockoutModalMessage.textContent = message || 'Too many failed attempts. Please wait before trying again.';
            lockoutModalTimer.hidden = false;
            lockedEmail = (email || '').trim().toLowerCase();

            if (lockedEmail) {
                lockoutModalAccount.hidden = false;
                lockoutModalAccount.textContent = 'Locked account: ' + email;
            } else {
                lockoutModalAccount.hidden = true;
            }

            function tick() {
                const remaining = Math.max(0, untilTs - Math.floor(Date.now() / 1000));
                lockoutModalTimer.textContent = 'Time remaining: ' + formatRemaining(remaining);

                if (remaining <= 0) {
                    hideLockoutModal();
                }
            }

            tick();
            lockoutTimerId = setInterval(tick, 1000);
        }

        function showAdminLockout(message, email) {
            lockoutModal.hidden = false;
            lockoutModalTitle.textContent = 'Account locked';
            lockoutModalMessage.textContent = message ||
                'This account is locked. Please contact the system administrator to reset your password.';
            lockoutModalTimer.hidden = true;
            lockedEmail = (email || '').trim().toLowerCase();

            if (lockedEmail) {
                lockoutModalAccount.hidden = false;
                lockoutModalAccount.textContent = 'Locked account: ' + email;
            } else {
                lockoutModalAccount.hidden = true;
            }
        }

        lockoutSwitchAccount?.addEventListener('click', prepareOtherAccountLogin);

        function clearLoginForm(preserveEmail) {
            document.querySelectorAll('form.auth-form input[name="password"], form.auth-form input[name="captcha"]')
                .forEach(function (input) { input.value = ''; });

            if (!preserveEmail && emailInput) {
                emailInput.value = '';
            }
        }

        function applyLockoutFromPage() {
            const adminLocked = lockoutModal?.dataset.lockoutAdmin === '1';
            const untilRaw = lockoutModal?.dataset.lockoutUntil || '';
            const message = lockoutModal?.dataset.lockoutMessage || '';
            const email = lockoutModal?.dataset.lockoutEmail || '';
            const untilTs = untilRaw ? parseInt(untilRaw, 10) : 0;

            if (adminLocked) {
                showAdminLockout(message, email);
                return true;
            }

            if (untilTs && untilTs > Math.floor(Date.now() / 1000)) {
                showTimedLockout(untilTs, message, email);
                return true;
            }

            return false;
        }

        loginForm?.addEventListener('submit', function (event) {
            const typed = (emailInput?.value || '').trim().toLowerCase();
            const adminLocked = lockoutModal?.dataset.lockoutAdmin === '1';
            const untilRaw = lockoutModal?.dataset.lockoutUntil || '';
            const untilTs = untilRaw ? parseInt(untilRaw, 10) : 0;
            const stillTimedOut = untilTs > Math.floor(Date.now() / 1000);
            const sameLockedAccount = lockedEmail && typed === lockedEmail;

            if (sameLockedAccount && (adminLocked || stillTimedOut)) {
                event.preventDefault();
                if (adminLocked) {
                    showAdminLockout(lockoutModal.dataset.lockoutMessage || '', lockoutModal.dataset.lockoutEmail || '');
                } else {
                    showTimedLockout(untilTs, lockoutModal.dataset.lockoutMessage || '', lockoutModal.dataset.lockoutEmail || '');
                }
            }
        });

        // Runs on normal load and when the browser restores the page with Back/Forward
        window.addEventListener('pageshow', function () {
            const locked = applyLockoutFromPage();
            // Keep the locked email visible in the modal context, but clear secrets.
            clearLoginForm(true);
            setTimeout(function () {
                clearLoginForm(true);
            }, 50);

            if (!locked && emailInput && !emailInput.value) {
                // normal empty login
            }
        });
    </script>
</body>
</html>
