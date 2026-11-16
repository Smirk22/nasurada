<?php

namespace App\Console\Commands;

use App\Services\BackupService;
use Illuminate\Console\Command;
use Throwable;

class BackupRun extends Command
{
    protected $signature = 'backup:run
                            {--keep= : Number of backup archives to keep (defaults to BACKUP_KEEP)}';

    protected $description = 'Create a backup of the database, student photos, and .env file';

    public function handle(BackupService $backups): int
    {
        $keep = $this->option('keep');

        try {
            $backup = $backups->create($keep !== null ? (int) $keep : null);
            $deleted = $backups->prune($keep !== null ? (int) $keep : null);
            $backups->pruneSafetySnapshots();
        } catch (Throwable $e) {
            $this->error('Backup failed: '.$e->getMessage());

            return self::FAILURE;
        }

        $this->info("Backup created: {$backup['name']} ({$backup['size_human']})");
        $this->line("Location: {$backup['path']}");

        foreach ($deleted as $name) {
            $this->line("Pruned old backup: {$name}");
        }

        return self::SUCCESS;
    }
}
