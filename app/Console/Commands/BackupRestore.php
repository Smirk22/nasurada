<?php

namespace App\Console\Commands;

use App\Services\BackupService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Throwable;

class BackupRestore extends Command
{
    protected $signature = 'backup:restore
                            {file? : Backup file name (defaults to the newest backup)}
                            {--force : Skip the confirmation prompt}';

    protected $description = 'Restore the database and photos from a backup archive';

    public function handle(BackupService $backups): int
    {
        $latest = $backups->latest();
        $file = $this->argument('file') ?: $latest['name'] ?? null;

        if ($file === null) {
            $this->error('No backup archives were found. Run "php artisan backup:run" first.');

            return self::FAILURE;
        }

        if (! $this->option('force') && ! $this->confirm("Restore from \"{$file}\"? The current database will be replaced.", false)) {
            $this->line('Restore cancelled.');

            return self::FAILURE;
        }

        // Keep web requests out while the database file is being swapped.
        Artisan::call('down');

        try {
            $result = $backups->restore($file);
            $backups->pruneSafetySnapshots();

            Artisan::call('optimize:clear');
        } catch (Throwable $e) {
            $this->error('Restore failed: '.$e->getMessage());
            $this->line('The previous database was kept as a pre-restore safety snapshot where possible.');

            return self::FAILURE;
        } finally {
            Artisan::call('up');
        }

        $this->info("Restored: {$result['name']}");
        $this->line('Safety snapshot of the previous database: '.$result['safety_snapshot']);

        if ($result['photos_restored']) {
            $this->line('Student photos were restored as well.');
        }

        $this->newLine();
        $this->comment('Sign in again. Sessions from the live database are no longer valid.');

        return self::SUCCESS;
    }
}
