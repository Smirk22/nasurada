<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Services\BackupService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Throwable;

class BackupController extends Controller
{
    public function __construct(private readonly BackupService $backups) {}

    /**
     * Create a backup on demand from the Super Admin dashboard.
     */
    public function store(Request $request): RedirectResponse
    {
        set_time_limit(300);

        try {
            $backup = $this->backups->create();
            $deleted = $this->backups->prune();
            $this->backups->pruneSafetySnapshots();
        } catch (Throwable $e) {
            report($e);
            $this->logBackupFailure('Create backup failed: '.$e->getMessage());

            return $this->backToBackups()->withErrors([
                'backup' => 'Backup failed: '.$e->getMessage(),
            ]);
        }

        ActivityLog::log(
            ActivityLog::ACTION_CREATED_BACKUP,
            "Created backup '{$backup['name']}' ({$backup['size_human']})"
        );

        $message = "Backup created: {$backup['name']} ({$backup['size_human']}).";

        if ($deleted !== []) {
            $message .= ' Pruned '.count($deleted).' older backup(s) past retention.';
        }

        return $this->backToBackups()->with('success', $message);
    }

    /**
     * Download a backup archive (Super Admin only).
     */
    public function download(string $file): BinaryFileResponse
    {
        try {
            $path = $this->backups->path($file);
        } catch (Throwable) {
            abort(404);
        }

        ActivityLog::log(
            ActivityLog::ACTION_DOWNLOADED_BACKUP,
            "Downloaded backup '{$file}'"
        );

        return response()->download($path, $file, [
            'Content-Type' => 'application/zip',
        ]);
    }

    /**
     * Restore the live system from a backup archive.
     */
    public function restore(Request $request, string $file): RedirectResponse
    {
        $request->validate([
            'confirmation' => ['required', 'in:RESTORE'],
        ]);

        set_time_limit(300);

        Artisan::call('down');

        try {
            $result = $this->backups->restore($file);
            $this->backups->pruneSafetySnapshots();
            Artisan::call('optimize:clear');
        } catch (Throwable $e) {
            report($e);
            $this->logBackupFailure("Restore backup '{$file}' failed: ".$e->getMessage());

            return $this->backToBackups()->withErrors([
                'backup' => 'Restore failed: '.$e->getMessage(),
            ]);
        } finally {
            Artisan::call('up');
        }

        try {
            ActivityLog::log(
                ActivityLog::ACTION_RESTORED_BACKUP,
                "Restored backup '{$result['name']}' (safety snapshot: {$result['safety_snapshot']})"
            );
        } catch (Throwable) {
            // Restored databases may not match the previous auth/activity schema.
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        $message = "System restored from {$result['name']}. Sign in again to continue.";

        if ($result['photos_restored']) {
            $message .= ' Student photos were restored as well.';
        }

        return redirect()->route('login')->with('success', $message);
    }

    /**
     * Delete a backup archive.
     */
    public function destroy(string $file): RedirectResponse
    {
        try {
            $this->backups->delete($file);
        } catch (Throwable $e) {
            $this->logBackupFailure("Delete backup '{$file}' failed: ".$e->getMessage());

            return $this->backToBackups()->withErrors([
                'backup' => 'Unable to delete backup: '.$e->getMessage(),
            ]);
        }

        ActivityLog::log(
            ActivityLog::ACTION_DELETED_BACKUP,
            "Deleted backup '{$file}'"
        );

        return $this->backToBackups()->with('success', "Backup {$file} was deleted.");
    }

    private function logBackupFailure(string $details): void
    {
        try {
            ActivityLog::log(ActivityLog::ACTION_FAILED_BACKUP, $details);
        } catch (Throwable) {
            // A failed restore may leave the audit schema temporarily unavailable.
        }
    }

    private function backToBackups(): RedirectResponse
    {
        return redirect()->to(route('superadmin.dashboard').'#backups');
    }
}
