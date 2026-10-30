<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Two-factor authentication | IETI SEVS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    @vite(['resources/css/login.css'])
</head>
<body class="auth-page">
    <main class="auth-shell">
        @include('partials.auth-branding')

        <section class="auth-panel">
            <header class="auth-panel__bar auth-panel__bar--header">
                <span class="auth-panel__bar-label d-none d-sm-inline">Account security</span>
                <div class="auth-panel__logos">
                    <img class="auth-panel__logo" src="{{ asset('images/group-logo.png') }}" alt="Group logo">
                    <img class="auth-panel__logo auth-panel__logo--round" src="{{ asset('images/ietilogo.png') }}" alt="IETI College logo">
                </div>
            </header>

            <div class="auth-panel__body">
                <div class="auth-form-wrap auth-form-wrap--wide">
                    <div class="auth-form-wrap__intro">
                        <span class="auth-form-wrap__eyebrow">
                            <i class="bi bi-shield-lock" aria-hidden="true"></i>
                            Account security
                        </span>
                        <h1 class="auth-form-wrap__heading">Two-factor authentication</h1>
                        <p class="auth-form-wrap__lead">
                            Add a second step to your sign-in using an authenticator app such as Google Authenticator,
                            Microsoft Authenticator, or Authy.
                        </p>
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

                    @if (! $user->two_factor_secret)
                        <div class="auth-empty-state">
                            <div class="auth-empty-state__icon">
                                <i class="bi bi-shield-plus" aria-hidden="true"></i>
                            </div>
                            <h2 class="auth-empty-state__title">Two-factor is not enabled</h2>
                            <p class="auth-empty-state__text">
                                Protect your account with a one-time code in addition to your password.
                            </p>

                            <form action="{{ route('two-factor.setup.enable') }}" method="POST">
                                @csrf
                                <button type="submit" class="auth-button w-100">
                                    Enable two-factor authentication
                                    <i class="bi bi-arrow-right ms-1" aria-hidden="true"></i>
                                </button>
                            </form>
                        </div>
                    @elseif (! $enabled)
                        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
                            <span class="two-factor-badge two-factor-badge--pending">
                                <i class="bi bi-hourglass-split" aria-hidden="true"></i>
                                Pending confirmation
                            </span>
                            <span class="auth-form__meta mb-0">Not active until you confirm a code.</span>
                        </div>

                        <ol class="auth-steps">
                            <li>Scan the QR code using your authenticator app.</li>
                            <li>Enter the 6-digit code shown in the app.</li>
                            <li>Save your recovery codes somewhere safe.</li>
                        </ol>

                        <div class="row g-4 align-items-center mt-1">
                            <div class="col-md-5 text-center">
                                <div class="two-factor-qr">
                                    {!! $user->twoFactorQrCodeSvg() !!}
                                </div>
                            </div>
                            <div class="col-md-7">
                                <p class="auth-section-title">Manual setup key</p>
                                <div class="d-flex gap-2 align-items-stretch">
                                    <code class="secret-key flex-grow-1" id="secretKey">{{ $secret }}</code>
                                    <button type="button" class="btn auth-copy-button" data-copy-target="secretKey">
                                        <i class="bi bi-clipboard" aria-hidden="true"></i>
                                        <span class="visually-hidden">Copy setup key</span>
                                    </button>
                                </div>
                                <p class="auth-form__meta">
                                    Can't scan the code? Add the key manually in your authenticator app.
                                </p>
                            </div>
                        </div>

                        <div class="mt-4">
                            <p class="auth-section-title">Recovery codes</p>
                            <p class="auth-form__meta">
                                Each code can be used once if you lose access to your authenticator app.
                            </p>
                            <ul class="recovery-codes">
                                @foreach ($recoveryCodes as $code)
                                    <li>{{ $code }}</li>
                                @endforeach
                            </ul>
                        </div>

                        <hr class="auth-rule">

                        <form action="{{ route('two-factor.setup.confirm') }}" method="POST" class="auth-form" autocomplete="off">
                            @csrf

                            <div class="mb-3">
                                <label class="form-label" for="code">Confirm the 6-digit code</label>
                                <input
                                    type="text"
                                    id="code"
                                    name="code"
                                    class="form-control auth-code-input"
                                    inputmode="numeric"
                                    pattern="[0-9 ]*"
                                    maxlength="7"
                                    placeholder="000000"
                                    autocomplete="one-time-code"
                                    required
                                    autofocus
                                >
                            </div>

                            <button type="submit" class="auth-button w-100">
                                <i class="bi bi-check2-circle me-1" aria-hidden="true"></i>
                                Confirm and enable
                            </button>
                        </form>
                    @else
                        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
                            <span class="two-factor-badge">
                                <i class="bi bi-shield-check" aria-hidden="true"></i>
                                Enabled
                            </span>
                            <span class="auth-form__meta mb-0">
                                Active since {{ $user->two_factor_confirmed_at?->format('M d, Y') }}
                            </span>
                        </div>

                        <p class="auth-form__lead mb-4">
                            You will be asked for a code from your authenticator app each time you sign in.
                        </p>

                        <div class="mb-4">
                            <p class="auth-section-title">Recovery codes</p>
                            <p class="auth-form__meta">
                                Keep these somewhere safe. Each code can be used once if you lose your authenticator.
                            </p>
                            <ul class="recovery-codes">
                                @foreach ($recoveryCodes as $code)
                                    <li>{{ $code }}</li>
                                @endforeach
                            </ul>
                        </div>

                        <div class="auth-action-block">
                            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
                                <div>
                                    <h2 class="auth-action-block__title">Regenerate recovery codes</h2>
                                    <p class="auth-form__meta mb-0">Old recovery codes will stop working.</p>
                                </div>
                                <button class="btn auth-outline-button" type="button" data-bs-toggle="collapse" data-bs-target="#recoveryCodesForm" aria-expanded="false" aria-controls="recoveryCodesForm">
                                    Regenerate
                                </button>
                            </div>

                            <div class="collapse mt-3" id="recoveryCodesForm">
                                <form action="{{ route('two-factor.setup.recovery-codes') }}" method="POST" class="auth-form" autocomplete="off">
                                    @csrf

                                    <label class="form-label" for="recovery_password">Confirm your password</label>
                                    <div class="input-group auth-input-group mb-3">
                                        <span class="input-group-text">
                                            <i class="bi bi-lock" aria-hidden="true"></i>
                                        </span>
                                        <input
                                            type="password"
                                            id="recovery_password"
                                            name="password"
                                            class="form-control"
                                            placeholder="Enter your password"
                                            autocomplete="current-password"
                                            required
                                        >
                                    </div>

                                    <button type="submit" class="auth-button auth-button--secondary w-100">
                                        Generate new recovery codes
                                    </button>
                                </form>
                            </div>
                        </div>

                        <div class="auth-action-block auth-action-block--danger">
                            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
                                <div>
                                    <h2 class="auth-action-block__title">Disable two-factor authentication</h2>
                                    <p class="auth-form__meta mb-0">Your account will only require a password to sign in.</p>
                                </div>
                                <button class="btn auth-danger-button" type="button" data-bs-toggle="collapse" data-bs-target="#disableTwoFactorForm" aria-expanded="false" aria-controls="disableTwoFactorForm">
                                    Disable
                                </button>
                            </div>

                            <div class="collapse mt-3" id="disableTwoFactorForm">
                                <form action="{{ route('two-factor.setup.disable') }}" method="POST" class="auth-form" autocomplete="off">
                                    @csrf
                                    @method('DELETE')

                                    <label class="form-label" for="disable_password">Confirm your password</label>
                                    <div class="input-group auth-input-group mb-3">
                                        <span class="input-group-text">
                                            <i class="bi bi-lock" aria-hidden="true"></i>
                                        </span>
                                        <input
                                            type="password"
                                            id="disable_password"
                                            name="password"
                                            class="form-control"
                                            placeholder="Enter your password"
                                            autocomplete="current-password"
                                            required
                                        >
                                    </div>

                                    <button type="submit" class="btn auth-danger-button w-100">
                                        Disable two-factor authentication
                                    </button>
                                </form>
                            </div>
                        </div>
                    @endif

                    <hr class="auth-rule">

                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                        <a class="auth-link" href="{{ $dashboardUrl }}">
                            <i class="bi bi-arrow-left me-1" aria-hidden="true"></i>
                            Back to dashboard
                        </a>

                        <form action="{{ route('logout') }}" method="POST">
                            @csrf
                            <button type="submit" class="auth-text-button">Sign out</button>
                        </form>
                    </div>
                </div>
            </div>

            <footer class="auth-panel__bar auth-panel__bar--footer">
                <span>&copy; {{ date('Y') }} IETI College</span>
                <span class="d-none d-sm-inline">Student Entry Verification System</span>
            </footer>
        </section>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.querySelectorAll('[data-copy-target]').forEach(function (button) {
            button.addEventListener('click', function () {
                const target = document.getElementById(button.dataset.copyTarget);

                if (!target) {
                    return;
                }

                navigator.clipboard?.writeText(target.textContent.trim()).then(function () {
                    const original = button.innerHTML;
                    button.innerHTML = '<i class="bi bi-check2" aria-hidden="true"></i><span class="visually-hidden">Copied</span>';
                    setTimeout(function () { button.innerHTML = original; }, 1500);
                });
            });
        });
    </script>
</body>
</html>
