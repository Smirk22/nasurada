<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->route('login');
        }

        if (filled($user->temporary_password)) {
            return redirect()->route('password.force-change');
        }

        $role = strtolower(trim((string) $user->role));
        $allowedRoles = array_map(static fn (string $allowed): string => strtolower(trim($allowed)), $roles);

        // A Super Admin may use every area of the system.
        if ($role === 'superadmin') {
            return $next($request);
        }

        if (! in_array($role, $allowedRoles, true)) {
            return match ($role) {
                'admin' => redirect()->route('adashboard'),
                'registrar' => redirect()->route('rdashboard'),
                'security' => redirect()->route('guard.dashboard'),
                default => abort(403),
            };
        }

        return $next($request);
    }
}
