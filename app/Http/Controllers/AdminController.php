<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\EntryLog;
use App\Models\Student;
use App\Models\User;
use App\Services\BackupService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminController extends Controller
{
    public function dashboard(BackupService $backups)
    {
        $totalUsers = User::count();
        $adminCount = User::where('role', 'Admin')->count();
        $registrarCount = User::where('role', 'Registrar')->count();
        $guardCount = User::where('role', 'Security')->count();
        $totalStudents = Student::count();
        $invalidRfidScans = EntryLog::whereDate('date', today())
            ->whereIn('status', ['denied', 'unknown', 'expired'])
            ->count();

        $studentsWithRfid = Student::whereNotNull('rfid_tag')
            ->where('rfid_tag', '!=', '')
            ->count();
        $studentsWithoutRfid = $totalStudents - $studentsWithRfid;

        $validScans = EntryLog::where('status', 'granted')->count();
        $invalidScans = EntryLog::whereIn('status', ['denied', 'unknown', 'expired'])->count();

        $users = User::all();
        // The admin monitor covers staff activity only; Super Admin actions
        // stay in the Super Admin audit log.
        $activities = ActivityLog::query()
            ->whereRaw('LOWER(role) <> ?', ['superadmin'])
            ->latest()
            ->get();

        // Analytics (last 7 days granted entries per day)
        $analyticsLabels = [];
        $analyticsData = [];
        for ($i = 6; $i >= 0; $i--) {
            $d = now()->subDays($i)->toDateString();
            $analyticsLabels[] = now()->subDays($i)->format('M d');
            $analyticsData[] = EntryLog::whereDate('date', $d)->where('status', 'granted')->count();
        }
        $todayStatusCounts = [
            'granted' => EntryLog::whereDate('date', today())->where('status', 'granted')->count(),
            'denied' => EntryLog::whereDate('date', today())->where('status', 'denied')->count(),
            'unknown' => EntryLog::whereDate('date', today())->where('status', 'unknown')->count(),
            'expired' => EntryLog::whereDate('date', today())->where('status', 'expired')->count(),
        ];

        $backupFiles = $backups->all();
        $backupTotalSize = $backups->totalSizeHuman();
        $backupDirectory = $backups->directory();
        $backupKeep = (int) config('backup.keep', 14);
        $backupIncludesPhotos = (bool) config('backup.include_photos', true);
        $backupIncludesEnv = (bool) config('backup.include_env', true);
        $backupDriver = (string) config('database.default');
        $lastBackup = $backupFiles[0] ?? null;
        $backupStatus = 'never';

        if ($lastBackup) {
            $hours = $lastBackup['created_at']->diffInHours(now());
            $backupStatus = $hours <= 36 ? 'fresh' : 'stale';
        }

        return view('adashboard', compact(
            'totalUsers',
            'adminCount',
            'registrarCount',
            'guardCount',
            'totalStudents',
            'invalidRfidScans',
            'studentsWithRfid',
            'studentsWithoutRfid',
            'validScans',
            'invalidScans',
            'users',
            'activities',
            'backupFiles',
            'backupTotalSize',
            'backupDirectory',
            'backupKeep',
            'backupIncludesPhotos',
            'backupIncludesEnv',
            'backupDriver',
            'backupStatus',
            'analyticsLabels',
            'analyticsData',
            'todayStatusCounts'
        ));
    }

    public function storeUser(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'username' => 'required|string|unique:accounts,username',
            'email' => 'required|email|unique:accounts,email',
            'password' => ['required', 'string', Password::default()],
            'role' => ['required', Rule::in(['Admin', 'Registrar', 'Security'])],
        ]);

        $validated['password'] = Hash::make($validated['password']);

        User::create($validated);

        ActivityLog::create([
            'user_name' => auth()->user()->username ?? 'Admin',
            'role' => 'Admin',
            'action' => 'Created User Account',
            'details' => "Added user '{$validated['username']}' with role '{$validated['role']}'",
        ]);

        return redirect()->back()->with('success', 'New staff user account created!');
    }

    public function updateUser(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'username' => 'required|string|unique:accounts,username,'.$user->id,
            'email' => 'required|email|unique:accounts,email,'.$user->id,
            'role' => ['required', Rule::in(['Admin', 'Registrar', 'Security'])],
        ]);

        $this->guardSuperAdminAccount($user);

        $user->update($validated);

        ActivityLog::create([
            'user_name' => auth()->user()->username ?? 'Admin',
            'role' => 'Admin',
            'action' => 'Updated User Account',
            'details' => "Updated account settings for '{$user->username}'",
        ]);

        return redirect()->back()->with('success', 'User account updated!');
    }

    public function generateTemporaryPassword(User $user)
    {
        $this->guardSuperAdminAccount($user);

        $temporaryPassword = Str::password(16);

        $user->forceFill([
            'password' => Hash::make($temporaryPassword),
            'temporary_password' => $temporaryPassword,
            'failed_login_attempts' => 0,
            'locked_until' => null,
            'must_reset_password' => false,
        ])->save();

        ActivityLog::create([
            'user_name' => auth()->user()->username ?? 'Admin',
            'role' => 'Admin',
            'action' => 'Generated Temporary Password',
            'details' => "Generated temporary password for '{$user->username}'",
        ]);

        return redirect()->back()->with('success', "Temporary password generated for {$user->email}. It is shown in the Temp Password column.");
    }

    public function destroyUser(User $user)
    {
        $this->guardSuperAdminAccount($user);

        $username = $user->username;
        $user->delete();

        ActivityLog::create([
            'user_name' => auth()->user()->username ?? 'Admin',
            'role' => 'Admin',
            'action' => 'Deleted User Account',
            'details' => "Removed user '{$username}' from system",
        ]);

        return redirect()->back()->with('success', 'User account deleted.');
    }

    public function exportEntryLogs(Request $request): StreamedResponse
    {
        $from = $request->input('from');
        $to = $request->input('to');

        $query = EntryLog::query()->with('student')->orderByDesc('date')->orderByDesc('time_in');

        if ($from) {
            $query->whereDate('date', '>=', $from);
        }
        if ($to) {
            $query->whereDate('date', '<=', $to);
        }

        $filename = 'entry_logs_'.now()->format('Ymd_His').'.xls';

        return response()->streamDownload(function () use ($query) {
            echo "\xEF\xBB\xBF"; // UTF-8 BOM for Excel
            echo '<table border="1"><thead><tr>';
            foreach (['Date', 'Time In', 'Time Out', 'Status', 'Student No', 'Name', 'Year', 'Section', 'Reason'] as $h) {
                echo '<th>'.htmlspecialchars($h, ENT_QUOTES, 'UTF-8').'</th>';
            }
            echo '</tr></thead><tbody>';
            $query->chunk(500, function ($logs) {
                foreach ($logs as $log) {
                    $s = $log->student;
                    $name = $s ? trim($s->first_name.' '.($s->middle_name ? $s->middle_name.' ' : '').$s->last_name) : '';
                    echo '<tr>';
                    echo '<td>'.htmlspecialchars((string) $log->date, ENT_QUOTES, 'UTF-8').'</td>';
                    echo '<td>'.htmlspecialchars((string) ($log->time_in ?? ''), ENT_QUOTES, 'UTF-8').'</td>';
                    echo '<td>'.htmlspecialchars((string) ($log->time_out ?? ''), ENT_QUOTES, 'UTF-8').'</td>';
                    echo '<td>'.htmlspecialchars((string) $log->status, ENT_QUOTES, 'UTF-8').'</td>';
                    echo '<td>'.htmlspecialchars((string) ($s?->student_number ?? ''), ENT_QUOTES, 'UTF-8').'</td>';
                    echo '<td>'.htmlspecialchars($name, ENT_QUOTES, 'UTF-8').'</td>';
                    echo '<td>'.htmlspecialchars((string) ($s?->year_level ?? ''), ENT_QUOTES, 'UTF-8').'</td>';
                    echo '<td>'.htmlspecialchars((string) ($s?->section ?? ''), ENT_QUOTES, 'UTF-8').'</td>';
                    echo '<td>'.htmlspecialchars((string) ($log->reason ?? ''), ENT_QUOTES, 'UTF-8').'</td>';
                    echo '</tr>';
                }
            });
            echo '</tbody></table>';
        }, $filename, ['Content-Type' => 'application/vnd.ms-excel; charset=UTF-8']);
    }

    /**
     * Super Admin accounts are managed only from the Super Admin control center.
     */
    private function guardSuperAdminAccount(User $user): void
    {
        if ($user->role === 'SuperAdmin') {
            abort(403, 'Super Admin accounts can only be managed from the Super Admin control center.');
        }
    }
}
