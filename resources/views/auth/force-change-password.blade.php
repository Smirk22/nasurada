<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Change password | IETI SEVS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    @vite(['resources/css/login.css'])
</head>
<body class="auth-page auth-page--login">
    <main class="auth-shell">
        @include('partials.auth-branding')

        <section class="auth-panel">
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
                            <i class="bi bi-key" aria-hidden="true"></i>
                            One-time temporary password
                        </span>
                        <h1 class="auth-form-wrap__heading">Set a new password</h1>
                        <p class="auth-form-wrap__lead">
                            Your temporary password can only be used once. Create a strong permanent password to continue.
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

                    <form action="{{ route('password.force-change.update') }}" method="POST" class="auth-form" autocomplete="off">
                        @csrf

                        <div class="mb-3">
                            <label class="form-label" for="password">New password</label>
                            <div class="input-group auth-input-group">
                                <span class="input-group-text">
                                    <i class="bi bi-lock" aria-hidden="true"></i>
                                </span>
                                <input
                                    type="password"
                                    id="password"
                                    class="form-control"
                                    name="password"
                                    placeholder="Enter a strong new password"
                                    required
                                    autofocus
                                    autocomplete="new-password"
                                >
                            </div>
                            <p class="form-text text-muted mb-0 mt-1">
                                At least 12 characters, with uppercase, lowercase, a number, and a symbol.
                            </p>
                        </div>

                        <div class="mb-4">
                            <label class="form-label" for="password_confirmation">Confirm new password</label>
                            <div class="input-group auth-input-group">
                                <span class="input-group-text">
                                    <i class="bi bi-lock-fill" aria-hidden="true"></i>
                                </span>
                                <input
                                    type="password"
                                    id="password_confirmation"
                                    class="form-control"
                                    name="password_confirmation"
                                    placeholder="Re-enter new password"
                                    required
                                    autocomplete="new-password"
                                >
                            </div>
                        </div>

                        <button type="submit" class="auth-button w-100">Save password &amp; continue</button>
                    </form>

                    <form action="{{ route('logout') }}" method="POST" class="mt-3 text-center">
                        @csrf
                        <button type="submit" class="btn btn-link text-decoration-none text-muted btn-sm">
                            Sign out instead
                        </button>
                    </form>
                </div>
            </div>
        </section>
    </main>
</body>
</html>
