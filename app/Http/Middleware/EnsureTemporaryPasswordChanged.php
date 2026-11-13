<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTemporaryPasswordChanged
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && filled($user->temporary_password)) {
            if (! $request->routeIs(
                'password.force-change',
                'password.force-change.update',
                'logout',
                'login',
                'login.store',
            )) {
                return redirect()->route('password.force-change');
            }
        }

        return $next($request);
    }
}
