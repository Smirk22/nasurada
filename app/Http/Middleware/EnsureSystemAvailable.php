<?php

namespace App\Http\Middleware;

use App\Models\Setting;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Honours the "maintenance mode" toggle from the Super Admin settings panel.
 *
 * Super Admins and the sign-in flow always stay reachable so the toggle can
 * never lock everyone out of the system.
 */
class EnsureSystemAvailable
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! Setting::boolean('maintenance_mode', false)) {
            return $next($request);
        }

        if ($request->routeIs(
            'login',
            'login.store',
            'two-factor.*',
            'password.force-change',
            'password.force-change.update',
            'logout',
            'superadmin.*',
        )) {
            return $next($request);
        }

        if (auth()->check() && auth()->user()?->role === 'SuperAdmin') {
            return $next($request);
        }

        abort(503);
    }
}
