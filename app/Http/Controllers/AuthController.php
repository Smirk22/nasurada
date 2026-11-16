<?php

namespace App\Http\Controllers;

use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;
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

        [$a, $b] = $this->issueLoginCaptcha($request);
        $lockout = $this->resolveLoginLockout($request);

        return response()
            ->view('login', [
                'a' => $a,
                'b' => $b,
                'lockoutUntil' => $lockout['until'],
                'lockoutAdmin' => $lockout['admin'],
                'lockoutMessage' => $lockout['message'],
                'lockoutEmail' => $lockout['email'],
            ])
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
            'captcha' => ['required'],
        ]);

        $expected = $request->session()->pull('login.captcha');
        $given = trim((string) $credentials['captcha']);

        if ($expected === null || ! preg_match('/^\d+$/', $given) || (int) $given !== (int) $expected) {
            return back()->withErrors([
                'captcha' => 'The verification answer is incorrect.',
            ])->onlyInput('email');
        }

        $remember = $request->boolean('remember');
        $user = User::where('email', $credentials['email'])->first();

        if ($user) {
            if ($user->must_reset_password) {
                return $this->lockoutRedirect(
                    $request,
                    $user,
                    'This account is locked. Please contact the system administrator to reset your password.'
                );
            }

            if ($user->locked_until && $user->locked_until->isFuture()) {
                return $this->lockoutRedirect(
                    $request,
                    $user,
                    $this->lockoutMessage($user->locked_until)
                );
            }
        }

        if (! $user || ! Auth::validate([
            'email' => $credentials['email'],
            'password' => $credentials['password'],
        ])) {
            $message = 'The provided credentials do not match our records.';

            if ($user) {
                $this->registerFailedLoginAttempt($user);
                $user->refresh();

                if ($user->must_reset_password) {
                    $message = 'Too many failed attempts. This account is locked. Please contact the system administrator to reset your password.';

                    return $this->lockoutRedirect($request, $user, $message);
                }

                if ($user->locked_until && $user->locked_until->isFuture()) {
                    return $this->lockoutRedirect(
                        $request,
                        $user,
                        $this->lockoutMessage($user->locked_until)
                    );
                }
            }

            return back()->withErrors([
                'email' => $message,
            ])->onlyInput('email');
        }

        $mustChangeTemporaryPassword = filled($user->temporary_password);

        $user->forceFill([
            'failed_login_attempts' => 0,
            'locked_until' => null,
        ])->save();

        $request->session()->forget('login.lockout_email');

        Auth::login($user, $remember);
        $request->session()->regenerate();

        if ($mustChangeTemporaryPassword) {
            return redirect()
                ->route('password.force-change')
                ->with('success', 'Temporary password accepted. Please set a new permanent password to continue.');
        }

        return $this->redirectToDashboard($request, $user);
    }

    public function showTwoFactorChallenge(Request $request)
    {
        $user = $this->pendingTwoFactorUser($request);

        if (! $user) {
            return redirect()->route('login');
        }

        return response()
            ->view('two-factor.challenge', ['email' => $user->email])
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
            'password' => ['required', 'string', Password::default()],
        ]);

        User::create([
            'username' => $validated['username'],
            'email' => $validated['email'],
            'password' => $validated['password'], // Automatically hashed by User model casting
            'role' => 'registrar',
        ]);

        return redirect('/login')->with('success', 'Account created successfully. Please log in.');
    }

    /**
     * @return array{0: int, 1: int}
     */
    private function issueLoginCaptcha(Request $request): array
    {
        $a = random_int(1, 9);
        $b = random_int(1, 9);
        $request->session()->put('login.captcha', $a + $b);

        return [$a, $b];
    }

    private function registerFailedLoginAttempt(User $user): void
    {
        $attempts = (int) $user->failed_login_attempts + 1;
        $updates = ['failed_login_attempts' => $attempts];

        if ($attempts === 3) {
            $updates['locked_until'] = now()->addSeconds(30);
        } elseif ($attempts === 8) {
            $updates['locked_until'] = now()->addMinute();
        } elseif ($attempts >= 10) {
            $updates['locked_until'] = now()->addHour();
            $updates['must_reset_password'] = true;
            $updates['temporary_password'] = null;
        }

        $user->forceFill($updates)->save();
    }

    private function lockoutMessage(CarbonInterface $lockedUntil): string
    {
        $seconds = max(1, $lockedUntil->getTimestamp() - time());

        if ($seconds >= 3600) {
            $hours = (int) ceil($seconds / 3600);

            return "Too many failed attempts. Try again in {$hours} hour".($hours === 1 ? '' : 's').'.';
        }

        if ($seconds >= 60) {
            $minutes = (int) ceil($seconds / 60);

            return "Too many failed attempts. Try again in {$minutes} minute".($minutes === 1 ? '' : 's').'.';
        }

        return "Too many failed attempts. Try again in {$seconds} second".($seconds === 1 ? '' : 's').'.';
    }

    /**
     * @return array{until: int|null, admin: bool, message: string|null, email: string|null}
     */
    private function resolveLoginLockout(Request $request): array
    {
        // Only surface lockout UI for the account that just failed — other accounts can still sign in.
        $email = old('email');

        if (! is_string($email) || $email === '') {
            return ['until' => null, 'admin' => false, 'message' => null, 'email' => null];
        }

        $user = User::where('email', $email)->first();

        if (! $user) {
            $request->session()->forget('login.lockout_email');

            return ['until' => null, 'admin' => false, 'message' => null, 'email' => null];
        }

        if ($user->must_reset_password) {
            return [
                'until' => null,
                'admin' => true,
                'message' => 'This account is locked. Please contact the system administrator to reset your password.',
                'email' => $user->email,
            ];
        }

        if ($user->locked_until && $user->locked_until->isFuture()) {
            return [
                'until' => $user->locked_until->getTimestamp(),
                'admin' => false,
                'message' => $this->lockoutMessage($user->locked_until),
                'email' => $user->email,
            ];
        }

        $request->session()->forget('login.lockout_email');

        return ['until' => null, 'admin' => false, 'message' => null, 'email' => null];
    }

    private function lockoutRedirect(Request $request, User $user, string $message)
    {
        $request->session()->put('login.lockout_email', $user->email);

        return back()->withErrors([
            'email' => $message,
        ])->onlyInput('email');
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
        if (filled($user->temporary_password)) {
            return redirect()->route('password.force-change');
        }

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
