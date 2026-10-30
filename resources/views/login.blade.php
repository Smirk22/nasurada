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

                    <form action="{{ route('login.store') }}" method="POST" class="auth-form" autocomplete="off">
                        @csrf

                        <div class="mb-3">
                            <label class="form-label" for="username">Username</label>
                            <div class="input-group auth-input-group">
                                <span class="input-group-text" id="username-icon">
                                    <i class="bi bi-person" aria-hidden="true"></i>
                                </span>
                                <input
                                    type="text"
                                    id="username"
                                    class="form-control"
                                    name="username"
                                    value="{{ old('username') }}"
                                    placeholder="Enter your username"
                                    aria-describedby="username-icon"
                                    required
                                    autofocus
                                    autocomplete="username"
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

                        <div class="d-flex justify-content-between align-items-center mb-4">
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
                            <span class="auth-2fa-hint">
                                <i class="bi bi-shield-lock" aria-hidden="true"></i>
                                2FA ready
                            </span>
                        </div>

                        <button type="submit" class="auth-button w-100">
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const passwordInput = document.getElementById('password');
        const passwordToggle = document.getElementById('togglePassword');

        passwordToggle?.addEventListener('click', function () {
            const isHidden = passwordInput.type === 'password';

            passwordInput.type = isHidden ? 'text' : 'password';
            passwordToggle.innerHTML = isHidden
                ? '<i class="bi bi-eye-slash" aria-hidden="true"></i>'
                : '<i class="bi bi-eye" aria-hidden="true"></i>';
            passwordToggle.setAttribute('aria-label', isHidden ? 'Hide password' : 'Show password');
        });

        function clearLoginForm() {
            document.querySelectorAll('form.auth-form input[name="username"], form.auth-form input[name="password"]')
                .forEach(function (input) { input.value = ''; });
        }

        // Runs on normal load and when the browser restores the page with Back/Forward
        window.addEventListener('pageshow', function () {
            clearLoginForm();
            setTimeout(clearLoginForm, 50);   // some browsers refill the fields a moment after restoring
        });
    </script>
</body>
</html>
