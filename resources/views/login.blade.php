<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    @vite(['resources/css/login.css'])
</head>
<body>
    <div class="auth-shell">
        @include('partials.auth-branding')

        <div class="auth-panel">
            <!-- Top Dark Green Header Bar with Logos aligned Right -->
            <header class="auth-panel__bar auth-panel__bar--header">
                <div class="auth-panel__logos">
                    <img class="auth-panel__logo" src="{{ asset('images/group-logo.png') }}" alt="Group logo">
                    <img class="auth-panel__logo auth-panel__logo--round" src="{{ asset('images/ietilogo.png') }}" alt="IETI College logo">
                </div>
            </header>

            <!-- Form Content -->
            <div class="auth-panel__body">
                <div class="auth-form-wrap">
                    <h1 class="auth-form-wrap__heading">Welcome, user!</h1>
                    <p class="auth-form-wrap__lead">Enter your credentials to continue.</p>

                    <form action="{{ route('login') }}" method="POST" class="auth-form">
                        @csrf

                        <label class="auth-field">
                            <span class="auth-field__label">Username</span>
                            <input type="text" name="username" placeholder="Enter your username" required autofocus>
                        </label>

                        <label class="auth-field">
                            <span class="auth-field__label">Password</span>
                            <input type="password" name="password" placeholder="Enter your password" required>
                        </label>

                        @if ($errors->any())
                            <p class="auth-form__error">{{ $errors->first() }}</p>
                        @endif

                        <button type="submit" class="auth-button">Login</button>
                    </form>

                    <a href="{{ route('register') }}" class="auth-link">Don't have an account? Register</a>
                </div>
            </div>

            <!-- Bottom Dark Green Footer Bar -->
            <footer class="auth-panel__bar auth-panel__bar--footer"></footer>
        </div>
    </div>
</body>
</html>