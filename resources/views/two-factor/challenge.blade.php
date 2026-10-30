<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Two-factor verification | IETI SEVS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    @vite(['resources/css/login.css'])
</head>
<body class="auth-page">
    <main class="auth-shell">
        @include('partials.auth-branding')

        <section class="auth-panel">
            <header class="auth-panel__bar auth-panel__bar--header">
                <span class="auth-panel__bar-label d-none d-sm-inline">Identity check</span>
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
                            Two-factor authentication
                        </span>
                        <h1 class="auth-form-wrap__heading">Verify it's you</h1>
                        <p class="auth-form-wrap__lead">
                            Open your authenticator app and enter the 6-digit code for
                            <strong>{{ $username }}</strong>.
                        </p>
                    </div>

                    @if ($errors->any())
                        <div class="auth-alert" role="alert">
                            <i class="bi bi-exclamation-triangle" aria-hidden="true"></i>
                            <span>{{ $errors->first() }}</span>
                        </div>
                    @endif

                    <form action="{{ route('two-factor.challenge.verify') }}" method="POST" class="auth-form" autocomplete="off">
                        @csrf

                        <div class="mb-4">
                            <label class="form-label" for="code">Authentication code</label>
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
                                autofocus
                            >
                        </div>

                        <button type="submit" class="auth-button w-100">
                            Verify and continue
                            <i class="bi bi-arrow-right ms-1" aria-hidden="true"></i>
                        </button>
                    </form>

                    <div class="auth-divider"><span>or</span></div>

                    <div class="text-center">
                        <a class="auth-link" data-bs-toggle="collapse" href="#recoveryForm" role="button" aria-expanded="false" aria-controls="recoveryForm">
                            Use a recovery code instead
                        </a>
                    </div>

                    <div class="collapse" id="recoveryForm">
                        <form action="{{ route('two-factor.challenge.verify') }}" method="POST" class="auth-form mt-3" autocomplete="off">
                            @csrf

                            <div class="mb-3">
                                <label class="form-label" for="recovery_code">Recovery code</label>
                                <div class="input-group auth-input-group">
                                    <span class="input-group-text">
                                        <i class="bi bi-key" aria-hidden="true"></i>
                                    </span>
                                    <input
                                        type="text"
                                        id="recovery_code"
                                        name="recovery_code"
                                        class="form-control"
                                        placeholder="xxxx-xxxx"
                                        autocomplete="off"
                                    >
                                </div>
                            </div>

                            <button type="submit" class="auth-button auth-button--secondary w-100">
                                Use recovery code
                            </button>
                        </form>
                    </div>

                    <form action="{{ route('two-factor.challenge.cancel') }}" method="POST" class="mt-4 text-center">
                        @csrf
                        <button type="submit" class="auth-text-button">
                            <i class="bi bi-arrow-left me-1" aria-hidden="true"></i>
                            Back to sign in
                        </button>
                    </form>
                </div>
            </div>

            <footer class="auth-panel__bar auth-panel__bar--footer">
                <span>&copy; {{ date('Y') }} IETI College</span>
                <span class="d-none d-sm-inline">Your account is protected with 2FA</span>
            </footer>
        </section>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
