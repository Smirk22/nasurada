<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Laravel\Fortify\Contracts\TwoFactorAuthenticationProvider;
use Laravel\Fortify\RecoveryCode;

class TwoFactorSetupController extends Controller
{
    public function edit(Request $request)
    {
        $user = $request->user();

        return response()
            ->view('two-factor.setup', [
                'user' => $user,
                'enabled' => $user->hasEnabledTwoFactorAuthentication(),
                'secret' => $user->two_factor_secret ? decrypt($user->two_factor_secret) : null,
                'recoveryCodes' => $user->two_factor_recovery_codes ? $user->recoveryCodes() : [],
                'dashboardUrl' => $this->dashboardUrl($user),
            ])
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
    }

    public function enable(Request $request): RedirectResponse
    {
        $user = $request->user();

        if (is_null($user->two_factor_secret)) {
            $user->forceFill([
                'two_factor_secret' => encrypt(app(TwoFactorAuthenticationProvider::class)->generateSecretKey()),
                'two_factor_recovery_codes' => encrypt(json_encode($this->freshRecoveryCodes())),
                'two_factor_confirmed_at' => null,
            ])->save();
        }

        return redirect()->route('two-factor.setup')->with(
            'success',
            'Scan the QR code with your authenticator app, then confirm the 6-digit code below.'
        );
    }

    public function confirm(Request $request): RedirectResponse
    {
        $request->validate([
            'code' => ['required', 'string'],
        ]);

        $user = $request->user();

        if (! $user->two_factor_secret) {
            return redirect()->route('two-factor.setup')->withErrors([
                'code' => 'Please start the two-factor setup again.',
            ]);
        }

        $secret = decrypt($user->two_factor_secret);
        $code = preg_replace('/\s+/', '', (string) $request->input('code')) ?? '';

        if (! app(TwoFactorAuthenticationProvider::class)->verify($secret, $code)) {
            return back()->withErrors([
                'code' => 'The provided authentication code is invalid. Please try again.',
            ]);
        }

        $user->forceFill(['two_factor_confirmed_at' => now()])->save();

        return redirect()->route('two-factor.setup')->with(
            'success',
            'Two-factor authentication is now enabled for your account.'
        );
    }

    public function regenerateRecoveryCodes(Request $request): RedirectResponse
    {
        $request->validate([
            'password' => ['required', 'string'],
        ]);

        $user = $request->user();

        if (! Hash::check((string) $request->input('password'), $user->password)) {
            return back()->withErrors(['password' => 'The provided password is incorrect.']);
        }

        $user->forceFill([
            'two_factor_recovery_codes' => encrypt(json_encode($this->freshRecoveryCodes())),
        ])->save();

        return back()->with('success', 'Recovery codes regenerated. Store them somewhere safe.');
    }

    public function destroy(Request $request): RedirectResponse
    {
        $request->validate([
            'password' => ['required', 'string'],
        ]);

        $user = $request->user();

        if (! Hash::check((string) $request->input('password'), $user->password)) {
            return back()->withErrors(['password' => 'The provided password is incorrect.']);
        }

        $user->forceFill([
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
        ])->save();

        return redirect()->route('two-factor.setup')->with(
            'success',
            'Two-factor authentication has been disabled.'
        );
    }

    private function freshRecoveryCodes(): array
    {
        return collect(range(1, 8))
            ->map(fn () => RecoveryCode::generate())
            ->all();
    }

    private function dashboardUrl(User $user): string
    {
        return match (strtolower(trim($user->role))) {
            'admin' => '/adashboard',
            'security' => '/Security/dashboard',
            default => '/rdashboard',
        };
    }
}
