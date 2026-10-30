<?php

namespace App\Http\Controllers;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Fortify\Contracts\TwoFactorAuthenticationProvider;

class AuthController extends Controller
{
    public function showLoginForm(Request $request)
    {
        if (Auth::check()) {
            $request->session()->forget('guard_logged_in_at');
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        return response()
            ->view('login')
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'username' => ['required', 'string'],
            'password' => ['required'],
        ]);

        $remember = $request->boolean('remember');

        if (! Auth::validate($credentials)) {
            return back()->withErrors([
                'username' => 'The provided credentials do not match our records.',
            ])->onlyInput('username');
        }

        $user = User::where('username', $credentials['username'])->firstOrFail();

        if ($user->hasEnabledTwoFactorAuthentication()) {
            $request->session()->put('login.id', $user->id);
            $request->session()->put('login.remember', $remember);

            return redirect()->route('two-factor.challenge');
        }

        Auth::login($user, $remember);
        $request->session()->regenerate();

        return $this->redirectToDashboard($request, $user);
    }

    public function showTwoFactorChallenge(Request $request)
    {
        $user = $this->pendingTwoFactorUser($request);

        if (! $user) {
            return redirect()->route('login');
        }

        return response()
            ->view('two-factor.challenge', ['username' => $user->username])
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
    }

    public function verifyTwoFactorChallenge(Request $request)
    {
        $request->validate([
            'code' => ['nullable', 'string'],
            'recovery_code' => ['nullable', 'string'],
        ]);

        $user = $this->pendingTwoFactorUser($request);

        if (! $user) {
            return redirect()->route('login')->withErrors([
                'code' => 'Your verification session has expired. Please sign in again.',
            ]);
        }

        $valid = false;

        if ($request->filled('recovery_code')) {
            $recoveryCode = trim((string) $request->input('recovery_code'));
            $matched = collect($user->recoveryCodes())->first(
                fn (string $code) => hash_equals($code, $recoveryCode)
            );

            if ($matched) {
                $user->replaceRecoveryCode($matched);
                $valid = true;
            }
        } elseif ($request->filled('code')) {
            $secret = decrypt($user->two_factor_secret);
            $code = preg_replace('/\s+/', '', (string) $request->input('code')) ?? '';
            $valid = app(TwoFactorAuthenticationProvider::class)->verify($secret, $code);
        }

        if (! $valid) {
            return back()->withErrors([
                'code' => 'The provided two-factor code is invalid. Please try again.',
            ]);
        }

        $remember = (bool) $request->session()->pull('login.remember', false);
        $request->session()->forget('login.id');

        Auth::login($user, $remember);
        $request->session()->regenerate();

        return $this->redirectToDashboard($request, $user);
    }

    public function cancelTwoFactorChallenge(Request $request)
    {
        $request->session()->forget(['login.id', 'login.remember']);

        return redirect()->route('login');
    }

    public function logout(Request $request)
    {
        $request->session()->forget('feed_reset_after_id');
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/login');
    }

    public function showRegisterForm()
    {
        return view('register');
    }

    public function register(Request $request)
    {
        $validated = $request->validate([
            'username' => ['required', 'string', 'max:100', 'unique:accounts,username'],
            'email' => ['required', 'email', 'unique:accounts,email'],
            'password' => ['required', 'min:8'],
        ]);

        User::create([
            'username' => $validated['username'],
            'email' => $validated['email'],
            'password' => $validated['password'], // Automatically hashed by User model casting
            'role' => 'registrar',
        ]);

        return redirect('/login')->with('success', 'Account created successfully. Please log in.');
    }

    private function pendingTwoFactorUser(Request $request): ?User
    {
        if (! $request->session()->has('login.id')) {
            return null;
        }

        return User::find($request->session()->get('login.id'));
    }

    private function redirectToDashboard(Request $request, User $user)
    {
        $role = strtolower(trim($user->role));

        // 1. Admin redirect
        if ($role === 'admin') {
            return redirect()->intended('/adashboard');
        }

        // 2. Guard redirect (Explicitly clear old timestamp and set fresh login time to reset live entry feed)
        if ($role === 'security') {
            $request->session()->forget('guard_logged_in_at');
            $request->session()->put('guard_logged_in_at', Carbon::now());

            return redirect()->intended('/Security/dashboard');
        }

        // 3. Registrar (default fallback)
        return redirect()->intended('/rdashboard');
    }
}
