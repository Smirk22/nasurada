<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Nightly database backup (database, student photos, and .env).
// Requires the scheduler to be running: php artisan schedule:work
// or a Windows Task Scheduler entry that runs "php artisan schedule:run" every minute.
Schedule::command('backup:run')
    ->dailyAt('23:00')
    ->withoutOverlapping();
