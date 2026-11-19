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

        // A Super Admin may use every area of the system.
        if ($user->role === 'SuperAdmin') {
            return $next($request);
        }

        if (! in_array($user->role, $roles)) {
            return match ($user->role) {
                'Admin' => redirect()->route('adashboard'),
                'Registrar' => redirect()->route('rdashboard'),
                'Security' => redirect()->route('guard.dashboard'),
                default => abort(403),
            };
        }

        return $next($request);
    }
}
