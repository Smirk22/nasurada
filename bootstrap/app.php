<?php

use App\Http\Middleware\EnsureTemporaryPasswordChanged;
use App\Http\Middleware\PreventBackHistory;
use App\Http\Middleware\HandleAppearance;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\RoleMiddleware;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->encryptCookies(except: ['appearance', 'sidebar_state']);

        $middleware->web(append: [
            HandleAppearance::class,
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
            PreventBackHistory::class,
            EnsureTemporaryPasswordChanged::class,
        ]);

        $middleware->alias([
            'role' => RoleMiddleware::class,
            'password.changed' => EnsureTemporaryPasswordChanged::class,
        ]);

        // Where logged-in users are sent when they open a guest-only page (like /login)
        $middleware->redirectUsersTo(function () {
            $user = auth()->user();

            if ($user && filled($user->temporary_password)) {
                return route('password.force-change');
            }

            return match ($user?->role) {
                'Admin'     => route('adashboard'),
                'Registrar' => route('rdashboard'),
                'Security'  => route('guard.dashboard'),
                default     => '/login',
            };
        });
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
