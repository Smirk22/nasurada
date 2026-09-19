<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register</title>
    @vite(['resources/css/register.css'])
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
                    <h1 class="auth-form-wrap__heading">Create an account</h1>
                    <p class="auth-form-wrap__lead">Fill in your details to register.</p>

                    <form action="{{ route('register') }}" method="POST" class="auth-form">
                        @csrf

                        <label class="auth-field">
                            <span class="auth-field__label">Username</span>
                            <input type="text" name="username" placeholder="Choose a username" required autofocus>
                        </label>

                        <label class="auth-field">
                            <span class="auth-field__label">Email</span>
                            <input type="email" name="email" placeholder="you@example.com" required>
                        </label>

                        <label class="auth-field">
                            <span class="auth-field__label">Password</span>
                            <input type="password" name="password" placeholder="At least 8 characters" required>
                        </label>

                        @if ($errors->any())
                            <ul class="auth-form__errors">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        @endif

                        <button type="submit" class="auth-button">Register</button>
                    </form>

                    <a href="{{ route('login') }}" class="auth-link">Already have an account? Log in</a>
                </div>
            </div>

            <!-- Bottom Dark Green Footer Bar -->
            <footer class="auth-panel__bar auth-panel__bar--footer"></footer>
        </div>
    </div>
</body>
</html>