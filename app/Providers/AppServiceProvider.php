<?php

namespace App\Providers;

use App\Models\Setting;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
        $this->configureApplicationSettings();
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(function (): Password {
            $rule = Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols();

            return app()->isProduction()
                ? $rule->uncompromised()
                : $rule;
        });
    }

    /**
     * Apply settings saved from the Super Admin control center.
     *
     * The schema check keeps fresh installs (and migrations) working before
     * the settings table exists.
     */
    protected function configureApplicationSettings(): void
    {
        if (! Schema::hasTable('settings')) {
            View::share('siteName', (string) config('app.name'));
            View::share('maintenanceMode', false);

            return;
        }

        $settings = Setting::allSettings();

        config([
            'backup.keep' => max(1, (int) ($settings['backup_keep'] ?? config('backup.keep', 14))),
            'backup.include_photos' => filter_var(
                $settings['backup_include_photos'] ?? config('backup.include_photos', true),
                FILTER_VALIDATE_BOOLEAN
            ),
            'backup.include_env' => filter_var(
                $settings['backup_include_env'] ?? config('backup.include_env', true),
                FILTER_VALIDATE_BOOLEAN
            ),
        ]);

        View::share('siteName', (string) (($settings['site_name'] ?? null) ?: config('app.name')));
        View::share('maintenanceMode', filter_var($settings['maintenance_mode'] ?? false, FILTER_VALIDATE_BOOLEAN));
    }
}
