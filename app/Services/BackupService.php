<?php

namespace App\Services;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use PDO;
use RuntimeException;
use Throwable;
use ZipArchive;

/**
 * Creates, lists, prunes, and restores local backups of the SEVS database.
 *
 * Each archive contains a transaction-safe SQLite snapshot (via VACUUM INTO),
 * the .env file, and (optionally) student photos. Archives are stored outside
 * the public web root in storage/app/backups.
 */
class BackupService
{
    public const PREFIX = 'sevs-backup-';

    public const SAFETY_PREFIX = 'pre-restore-';

    /**
     * Directory where backup archives are written.
     */
    public function directory(): string
    {
        return storage_path('app/backups');
    }

    /**
     * Absolute path to the live SQLite database file.
     */
    public function databaseFile(): string
    {
        $path = config('database.connections.'.config('database.default').'.database');

        if (! is_string($path) || $path === '' || $path === ':memory:') {
            throw new RuntimeException('The configured database is not a file-backed SQLite database.');
        }

        return str_starts_with($path, DIRECTORY_SEPARATOR) || preg_match('/^[A-Za-z]:[\\\\\\/]/', $path)
            ? $path
            : database_path($path);
    }

    /**
     * Create a new backup archive and return its metadata.
     *
     * @return array{name: string, path: string, size: int, size_human: string, created_at: CarbonImmutable}
     */
    public function create(?int $keep = null): array
    {
        $this->assertZipAvailable();
        File::ensureDirectoryExists($this->directory());

        $lock = fopen($this->directory().DIRECTORY_SEPARATOR.'.lock', 'c');

        if ($lock === false) {
            throw new RuntimeException('Unable to open the backup lock file.');
        }

        try {
            if (! flock($lock, LOCK_EX | LOCK_NB)) {
                throw new RuntimeException('Another backup is already running. Try again in a moment.');
            }

            return $this->buildArchive();
        } finally {
            flock($lock, LOCK_UN);
            fclose($lock);
        }
    }

    /**
     * @return array{name: string, path: string, size: int, size_human: string, created_at: CarbonImmutable}
     */
    protected function buildArchive(): array
    {
        $tempDir = $this->directory().DIRECTORY_SEPARATOR.'.tmp-'.Str::lower(Str::random(12));
        File::ensureDirectoryExists($tempDir);

        try {
            $snapshot = $tempDir.DIRECTORY_SEPARATOR.'database.sqlite';
            $this->snapshot($snapshot);

            $name = $this->newArchiveName();
            $partial = $this->directory().DIRECTORY_SEPARATOR.$name.'.part';
            $photos = 0;

            $zip = new ZipArchive;

            if ($zip->open($partial, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
                throw new RuntimeException('Unable to create the backup archive.');
            }

            try {
                $zip->addFile($snapshot, 'database.sqlite');

                $includesEnv = (bool) config('backup.include_env', true) && is_file(base_path('.env'));

                if ($includesEnv) {
                    $zip->addFile(base_path('.env'), 'env/.env');
                }

                $includesPhotos = (bool) config('backup.include_photos', true);

                if ($includesPhotos) {
                    $photos = $this->addDirectoryToZip($zip, storage_path('app/public'), 'storage/app/public');
                }

                $manifest = [
                    'application' => config('app.name'),
                    'format_version' => 1,
                    'created_at' => now()->toIso8601String(),
                    'php_version' => PHP_VERSION,
                    'laravel_version' => app()->version(),
                    'database_connection' => config('database.default'),
                    'database_size' => File::size($snapshot),
                    'includes_env' => $includesEnv,
                    'includes_photos' => $includesPhotos,
                    'photo_files' => $photos,
                ];

                $zip->addFromString(
                    'manifest.json',
                    json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) ?: '{}'
                );
            } finally {
                $zip->close();
            }

            $final = $this->directory().DIRECTORY_SEPARATOR.$name;

            if (! @rename($partial, $final)) {
                @unlink($partial);

                throw new RuntimeException('Unable to finalize the backup archive.');
            }

            return $this->describe($final);
        } finally {
            File::deleteDirectory($tempDir);
        }
    }

    /**
     * Write a transaction-safe snapshot of the live SQLite database to $destination.
     */
    public function snapshot(string $destination): void
    {
        if (file_exists($destination)) {
            @unlink($destination);
        }

        $connection = DB::connection();

        if ($connection->getDriverName() !== 'sqlite') {
            throw new RuntimeException('Automatic backups currently support SQLite databases only.');
        }

        $quoted = $connection->getPdo()->quote($destination);
        $connection->statement("VACUUM INTO {$quoted}");
    }

    /**
     * All backup archives, newest first.
     *
     * @return array<int, array{name: string, path: string, size: int, size_human: string, created_at: CarbonImmutable}>
     */
    public function all(): array
    {
        $files = glob($this->directory().DIRECTORY_SEPARATOR.self::PREFIX.'*.zip') ?: [];
        $backups = [];

        foreach ($files as $path) {
            if (! $this->isValidName(basename($path))) {
                continue;
            }

            $backups[] = $this->describe($path);
        }

        usort($backups, fn (array $a, array $b): int => $b['created_at']->getTimestamp() <=> $a['created_at']->getTimestamp());

        return $backups;
    }

    /**
     * The newest backup archive, if one exists.
     *
     * @return array{name: string, path: string, size: int, size_human: string, created_at: CarbonImmutable}|null
     */
    public function latest(): ?array
    {
        return $this->all()[0] ?? null;
    }

    /**
     * Absolute path for a validated archive name.
     */
    public function path(string $name): string
    {
        if (! $this->isValidName($name)) {
            throw new RuntimeException('Invalid backup file name.');
        }

        $path = $this->directory().DIRECTORY_SEPARATOR.$name;

        if (! is_file($path)) {
            throw new RuntimeException('Backup file not found.');
        }

        return $path;
    }

    public function delete(string $name): void
    {
        $path = $this->path($name);

        if (! @unlink($path)) {
            throw new RuntimeException('Unable to delete the backup file.');
        }
    }

    /**
     * Delete archives beyond the retention limit and return their names.
     *
     * @return array<int, string>
     */
    public function prune(?int $keep = null): array
    {
        $keep = max(1, $keep ?? (int) config('backup.keep', 14));
        $deleted = [];

        foreach (array_slice($this->all(), $keep) as $backup) {
            if (@unlink($backup['path'])) {
                $deleted[] = $backup['name'];
            }
        }

        return $deleted;
    }

    /**
     * Restore the database (and photos) from a backup archive.
     *
     * The caller is responsible for putting the application into maintenance
     * mode and for clearing caches afterwards.
     *
     * @return array{name: string, safety_snapshot: string, photos_restored: bool}
     */
    public function restore(string $name): array
    {
        $this->assertZipAvailable();

        $archive = $this->path($name);
        $tempDir = $this->directory().DIRECTORY_SEPARATOR.'.restore-'.Str::lower(Str::random(12));
        File::ensureDirectoryExists($tempDir);

        $zip = new ZipArchive;

        if ($zip->open($archive) !== true) {
            throw new RuntimeException('Unable to open the backup archive.');
        }

        try {
            $this->guardAgainstUnsafeEntries($zip);

            if ($zip->locateName('database.sqlite') === false) {
                throw new RuntimeException('The backup archive does not contain a database snapshot.');
            }

            if (! $zip->extractTo($tempDir)) {
                throw new RuntimeException('Unable to extract the backup archive.');
            }
        } finally {
            $zip->close();
        }

        try {
            $restoredDatabase = $tempDir.DIRECTORY_SEPARATOR.'database.sqlite';
            $this->assertValidDatabase($restoredDatabase);

            $databaseFile = $this->databaseFile();
            $safetyName = self::SAFETY_PREFIX.now()->format('Y-m-d_His').'.sqlite';
            $safetyPath = $this->directory().DIRECTORY_SEPARATOR.$safetyName;

            // Snapshot the current database before touching anything.
            $this->snapshot($safetyPath);

            // Drop the connection so Windows releases its handle on the file.
            DB::purge();

            foreach (['-wal', '-shm'] as $suffix) {
                if (is_file($databaseFile.$suffix)) {
                    @unlink($databaseFile.$suffix);
                }
            }

            $replaceBackup = $databaseFile.'.before-restore';

            if (is_file($replaceBackup)) {
                @unlink($replaceBackup);
            }

            if (! @rename($databaseFile, $replaceBackup)) {
                throw new RuntimeException(
                    'Unable to replace the live database. Stop the SEVS server, then run the restore again.'
                );
            }

            try {
                if (! @copy($restoredDatabase, $databaseFile)) {
                    throw new RuntimeException('Unable to write the restored database file.');
                }

                @unlink($replaceBackup);
            } catch (Throwable $e) {
                @copy($replaceBackup, $databaseFile);
                @unlink($replaceBackup);

                throw $e;
            }

            $photosRestored = false;
            $restoredPhotos = $tempDir.DIRECTORY_SEPARATOR.'storage'.DIRECTORY_SEPARATOR.'app'.DIRECTORY_SEPARATOR.'public';

            if (is_dir($restoredPhotos)) {
                File::ensureDirectoryExists(storage_path('app/public'));
                File::copyDirectory($restoredPhotos, storage_path('app/public'));
                $photosRestored = true;
            }

            return [
                'name' => $name,
                'safety_snapshot' => $safetyName,
                'photos_restored' => $photosRestored,
            ];
        } finally {
            File::deleteDirectory($tempDir);
        }
    }

    /**
     * Human readable total size of all backups.
     */
    public function totalSizeHuman(): string
    {
        return $this->formatSize((int) array_sum(array_column($this->all(), 'size')));
    }

    /**
     * Remove old pre-restore safety snapshots, keeping the newest few.
     *
     * @return array<int, string>
     */
    public function pruneSafetySnapshots(int $keep = 5): array
    {
        $files = glob($this->directory().DIRECTORY_SEPARATOR.self::SAFETY_PREFIX.'*.sqlite') ?: [];

        usort($files, fn (string $a, string $b): int => filemtime($b) <=> filemtime($a));

        $deleted = [];

        foreach (array_slice($files, max(1, $keep)) as $path) {
            if (@unlink($path)) {
                $deleted[] = basename($path);
            }
        }

        return $deleted;
    }

    /**
     * @return array{name: string, path: string, size: int, size_human: string, created_at: CarbonImmutable}
     */
    protected function describe(string $path): array
    {
        $size = (int) (filesize($path) ?: 0);

        return [
            'name' => basename($path),
            'path' => $path,
            'size' => $size,
            'size_human' => $this->formatSize($size),
            'created_at' => CarbonImmutable::createFromTimestamp((int) filemtime($path), config('app.timezone')),
        ];
    }

    protected function formatSize(int $bytes): string
    {
        if ($bytes < 1024) {
            return $bytes.' B';
        }

        $units = ['KB', 'MB', 'GB', 'TB'];
        $value = $bytes / 1024;
        $unit = 0;

        while ($value >= 1024 && $unit < count($units) - 1) {
            $value /= 1024;
            $unit++;
        }

        return number_format($value, 1).' '.$units[$unit];
    }

    protected function newArchiveName(): string
    {
        $base = self::PREFIX.now()->format('Y-m-d_His');
        $name = $base.'.zip';
        $suffix = 1;

        while (file_exists($this->directory().DIRECTORY_SEPARATOR.$name)) {
            $name = $base.'-'.$suffix++.'.zip';
        }

        return $name;
    }

    protected function isValidName(string $name): bool
    {
        return (bool) preg_match('/^'.preg_quote(self::PREFIX, '/').'\d{4}-\d{2}-\d{2}_\d{6}(?:-\d+)?\.zip$/', $name);
    }

    protected function addDirectoryToZip(ZipArchive $zip, string $directory, string $prefix): int
    {
        if (! is_dir($directory)) {
            return 0;
        }

        $added = 0;

        foreach (File::allFiles($directory) as $file) {
            $entry = $prefix.'/'.str_replace('\\', '/', $file->getRelativePathname());

            if ($zip->addFile($file->getPathname(), $entry)) {
                $added++;
            }
        }

        return $added;
    }

    protected function assertZipAvailable(): void
    {
        if (! class_exists(ZipArchive::class)) {
            throw new RuntimeException('The PHP zip extension is required to create backups.');
        }
    }

    protected function assertValidDatabase(string $path): void
    {
        $result = null;

        try {
            $pdo = new PDO('sqlite:'.$path);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $statement = $pdo->query('PRAGMA integrity_check');

            if ($statement === false) {
                throw new RuntimeException('The integrity check could not be run.');
            }

            $result = $statement->fetchColumn();
            $pdo = null;
        } catch (Throwable $e) {
            throw new RuntimeException('The backup database snapshot could not be opened: '.$e->getMessage());
        }

        if (strtolower((string) $result) !== 'ok') {
            throw new RuntimeException('The backup database snapshot failed its integrity check: '.$result);
        }
    }

    protected function guardAgainstUnsafeEntries(ZipArchive $zip): void
    {
        for ($index = 0; $index < $zip->numFiles; $index++) {
            $entry = (string) $zip->getNameIndex($index);

            if (str_contains($entry, '..') || str_starts_with($entry, '/') || str_starts_with($entry, '\\') || str_contains($entry, "\0")) {
                throw new RuntimeException('The backup archive contains an unsafe file path.');
            }
        }
    }
}
