<?php

namespace App\Console\Commands;

use App\Services\BackupService;
use Illuminate\Console\Command;

class BackupList extends Command
{
    protected $signature = 'backup:list';

    protected $description = 'List saved database backups';

    public function handle(BackupService $backups): int
    {
        $all = $backups->all();

        if ($all === []) {
            $this->info('No backups have been created yet.');

            return self::SUCCESS;
        }

        $this->table(
            ['Created', 'File', 'Size'],
            array_map(fn (array $backup): array => [
                $backup['created_at']->format('Y-m-d g:i A'),
                $backup['name'],
                $backup['size_human'],
            ], $all)
        );

        $this->line('Total size: '.$backups->totalSizeHuman());

        return self::SUCCESS;
    }
}
