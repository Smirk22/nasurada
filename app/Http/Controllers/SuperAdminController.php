<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\EntryLog;
use App\Models\Setting;
use App\Models\Student;
use App\Models\User;
use App\Services\BackupService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class SuperAdminController extends Controller
{
    /** @var array<int, string> */
    private const ROLES = ['SuperAdmin', 'Admin', 'Registrar', 'Security'];

    public function __construct(private readonly BackupService $backups) {}

    public function dashboard(Request $request): View
    {
        $users = User::query()->orderBy('role')->orderBy('name')->get();

        $activityQuery = ActivityLog::query();

        if ($search = trim((string) $request->input('q', ''))) {
            $activityQuery->where(function ($query) use ($search) {
                $query->where('user_name', 'like', "%{$search}%")
                    ->orWhere('action', 'like', "%{$search}%")
                    ->orWhere('details', 'like', "%{$search}%");
            });
        }

        if ($role = (string) $request->input('role', '')) {
            $activityQuery->where('role', $role);
        }

        $activities = $activityQuery->latest()->paginate(15)->withQueryString();

        $stats = [
            'accounts' => User::count(),
            'superadmins' => User::where('role', 'SuperAdmin')->count(),
            'admins' => User::where('role', 'Admin')->count(),
            'registrars' => User::where('role', 'Registrar')->count(),
            'security' => User::where('role', 'Security')->count(),
            'students' => Student::count(),
            'entries_today' => EntryLog::whereDate('date', today())->count(),
            'alerts_today' => EntryLog::whereDate('date', today())
                ->whereIn('status', ['denied', 'unknown', 'expired'])
                ->count(),
            'locked_accounts' => User::where('must_reset_password', true)
                ->orWhere('locked_until', '>', now())
                ->count(),
        ];

        $analyticsLabels = [];
        $analyticsData = [];

        for ($i = 6; $i >= 0; $i--) {
            $day = now()->subDays($i)->toDateString();
            $analyticsLabels[] = now()->subDays($i)->format('M d');
            $analyticsData[] = EntryLog::whereDate('date', $day)->where('status', 'granted')->count();
        }

        $roleBreakdown = collect(self::ROLES)
            ->mapWithKeys(fn (string $role): array => [$role => User::where('role', $role)->count()])
            ->all();

        $backupFiles = $this->backups->all();
        $backupTotalSize = $this->backups->totalSizeHuman();
        $backupDirectory = $this->backups->directory();
        $lastBackup = $backupFiles[0] ?? null;
        $backupStatus = 'never';

        if ($lastBackup) {
            $backupStatus = $lastBackup['created_at']->diffInHours(now()) <= 36 ? 'fresh' : 'stale';
        }

        $settings = [
            'site_name' => (string) Setting::value('site_name', config('app.name')),
            'backup_keep' => (int) Setting::value('backup_keep', config('backup.keep', 14)),
            'backup_include_photos' => Setting::boolean('backup_include_photos', (bool) config('backup.include_photos', true)),
            'backup_include_env' => Setting::boolean('backup_include_env', (bool) config('backup.include_env', true)),
            'maintenance_mode' => Setting::boolean('maintenance_mode', false),
        ];

        return view('superadmin.dashboard', compact(
            'users',
            'activities',
            'stats',
            'analyticsLabels',
            'analyticsData',
            'roleBreakdown',
            'backupFiles',
            'backupTotalSize',
            'backupDirectory',
            'backupStatus',
            'settings',
        ));
    }

    public function storeUser(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:100', 'unique:accounts,username'],
            'email' => ['required', 'email', 'max:255', 'unique:accounts,email'],
            'password' => ['required', 'string', Password::default()],
            'role' => ['required', Rule::in(self::ROLES)],
        ]);

        User::create($validated);

        ActivityLog::log(
            'Created Account (Super Admin)',
            "Created account '{$validated['username']}' with role '{$validated['role']}'"
        );

        return $this->accountsRedirect('Staff account created.');
    }

    public function updateUser(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:100', 'unique:accounts,username,'.$user->id],
            'email' => ['required', 'email', 'max:255', 'unique:accounts,email,'.$user->id],
            'role' => ['required', Rule::in(self::ROLES)],
        ]);

        $this->guardAccount($user, $validated['role']);

        $user->update($validated);

        ActivityLog::log(
            'Updated Account (Super Admin)',
            "Updated '{$user->username}' — role is now '{$user->role}'"
        );

        return $this->accountsRedirect('Account updated.');
    }

    public function generateTemporaryPassword(User $user): RedirectResponse
    {
        $temporaryPassword = Str::password(16);

        $user->forceFill([
            'password' => $temporaryPassword,
            'temporary_password' => $temporaryPassword,
            'failed_login_attempts' => 0,
            'locked_until' => null,
            'must_reset_password' => false,
        ])->save();

        ActivityLog::log(
            'Generated Temporary Password (Super Admin)',
            "Generated a temporary password for '{$user->username}'"
        );

        return $this->accountsRedirect("Temporary password generated for {$user->email}. Copy it from the Temp Password column.");
    }

    public function destroyUser(User $user): RedirectResponse
    {
        $this->guardAccount($user);

        $username = $user->username;
        $user->delete();

        ActivityLog::log(
            'Deleted Account (Super Admin)',
            "Removed account '{$username}' from the system"
        );

        return $this->accountsRedirect('Account deleted.');
    }

    public function updateSettings(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'site_name' => ['required', 'string', 'max:100'],
            'backup_keep' => ['required', 'integer', 'min:1', 'max:90'],
            'backup_include_photos' => ['sometimes', 'boolean'],
            'backup_include_env' => ['sometimes', 'boolean'],
            'maintenance_mode' => ['sometimes', 'boolean'],
        ]);

        Setting::put('site_name', $validated['site_name']);
        Setting::put('backup_keep', $validated['backup_keep']);
        Setting::put('backup_include_photos', $request->boolean('backup_include_photos'));
        Setting::put('backup_include_env', $request->boolean('backup_include_env'));
        Setting::put('maintenance_mode', $request->boolean('maintenance_mode'));

        ActivityLog::log(
            'Updated System Settings',
            'Updated site name, backup options and maintenance mode'
        );

        return redirect()->to(route('superadmin.dashboard').'#settings')
            ->with('success', 'System settings saved.');
    }

    /**
     * Prevent an Admin-style account edit from removing the last Super Admin
     * or from changing/deleting the currently signed-in Super Admin.
     */
    private function guardAccount(User $user, ?string $newRole = null): void
    {
        $isSelf = $user->id === auth()->id();

        if ($isSelf && ($newRole === null || $newRole !== $user->role)) {
            abort(403, 'You cannot change or delete your own Super Admin account.');
        }

        $wasSuperAdmin = $user->role === 'SuperAdmin';
        $remainsSuperAdmin = $newRole === 'SuperAdmin';

        if ($wasSuperAdmin && ! $remainsSuperAdmin && User::where('role', 'SuperAdmin')->count() <= 1) {
            abort(403, 'At least one Super Admin account must remain in the system.');
        }
    }

    private function accountsRedirect(string $message): RedirectResponse
    {
        return redirect()->to(route('superadmin.dashboard').'#accounts')
            ->with('success', $message);
    }
}
