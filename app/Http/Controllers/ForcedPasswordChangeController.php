<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

class ForcedPasswordChangeController extends Controller
{
    public function edit(Request $request)
    {
        $user = $request->user();

        if (! $user || ! filled($user->temporary_password)) {
            return redirect()->intended($this->dashboardPath($user?->role));
        }

        return response()
            ->view('auth.force-change-password')
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
    }

    public function update(Request $request)
    {
        $user = $request->user();

        if (! $user || ! filled($user->temporary_password)) {
            return redirect()->intended($this->dashboardPath($user?->role));
        }

        $validated = $request->validate([
            'password' => ['required', 'string', 'confirmed', Password::default()],
        ]);

        if (hash_equals((string) $user->temporary_password, $validated['password'])
            || Hash::check($validated['password'], $user->password)) {
            throw ValidationException::withMessages([
                'password' => 'Choose a new password. You cannot reuse the temporary password.',
            ]);
        }

        $user->forceFill([
            'password' => Hash::make($validated['password']),
            'temporary_password' => null,
            'failed_login_attempts' => 0,
            'locked_until' => null,
            'must_reset_password' => false,
        ])->save();

        $request->session()->regenerate();

        return redirect()
            ->intended($this->dashboardPath($user->role))
            ->with('success', 'Password updated. You can now use the system.');
    }

    private function dashboardPath(?string $role): string
    {
        return match (strtolower(trim((string) $role))) {
            'admin' => '/adashboard',
            'security' => '/Security/dashboard',
            default => '/rdashboard',
        };
    }
}
