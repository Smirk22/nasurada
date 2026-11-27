<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\EntryLog;
use App\Models\Student;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminController extends Controller
{
    /** Roles the Admin dashboard is allowed to see and manage. */
    private const MANAGEABLE_ROLES = ['Registrar', 'Security'];

    public function dashboard(Request $request)
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

        $users = User::query()
            ->whereRaw("LOWER(COALESCE(role, '')) <> ?", ['superadmin'])
            ->orderBy('role')
            ->orderBy('name')
            ->get();
        // The admin monitor covers staff activity only; Super Admin actions
        // stay in the Super Admin audit log.
        $activityQuery = ActivityLog::withoutAccessActions()
            ->whereRaw("LOWER(COALESCE(role, '')) <> ?", ['superadmin']);

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

        // Keep the tab anchor and active filters when moving between pages.
        $activities = $activityQuery->latest()->paginate(10)->withQueryString()->fragment('activity');

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
            'role' => ['required', Rule::in(self::MANAGEABLE_ROLES)],
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
            'role' => ['required', Rule::in(self::MANAGEABLE_ROLES)],
        ]);

        $this->guardManagedAccount($user);

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
        $this->guardManagedAccount($user);

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
        $this->guardManagedAccount($user);

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
        $validated = $request->validate([
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:from'],
        ]);

        $from = $validated['from'] ?? null;
        $to = $validated['to'] ?? null;

        $query = EntryLog::query()
            ->with(['student', 'visitor'])
            ->when($from, fn ($query) => $query->whereDate('date', '>=', $from))
            ->when($to, fn ($query) => $query->whereDate('date', '<=', $to))
            ->orderByDesc('date')
            ->orderByDesc('time_in')
            ->orderByDesc('id');

        $count = (clone $query)->count();
        $range = $from || $to
            ? ' ('.($from ?? 'earliest').' to '.($to ?? 'latest').')'
            : '';

        ActivityLog::log(
            'Exported Entry Logs',
            "Downloaded {$count} entry log(s) as a CSV file{$range}."
        );

        $filename = 'entry_logs_'.now()->format('Ymd_His').'.csv';

        return response()->streamDownload(function () use ($query) {
            $handle = fopen('php://output', 'w');

            if ($handle === false) {
                throw new RuntimeException('Unable to open the CSV output stream.');
            }

            fwrite($handle, "\xEF\xBB\xBF");
            fputcsv($handle, [
                'Student No',
                'Name',
                'Year',
                'Section',
                'Time In',
                'Time Out',
                'Status',
                'Date',
                'Reason for access denied',
            ]);

            $query->chunk(500, function ($logs) use ($handle) {
                foreach ($logs as $log) {
                    $student = $log->student;
                    $visitor = $log->visitor;

                    $name = $student
                        ? trim($student->first_name.' '.$student->last_name)
                        : (string) ($visitor->visitor_name ?? '');

                    fputcsv($handle, array_map(
                        fn ($value) => $this->csvValue($value),
                        [
                            $student?->student_number,
                            $name,
                            $student?->year_level,
                            $student?->section,
                            $log->time_in,
                            $log->time_out,
                            $log->status,
                            $log->date,
                            $log->reason ?: 'N/A',
                        ]
                    ));
                }
            });
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Cache-Control' => 'no-store, no-cache, must-revalidate',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function exportSummaryReport(): StreamedResponse
    {
        $generatedAt = now();
        $totalStudents = Student::count();
        $studentsWithRfid = Student::whereNotNull('rfid_tag')
            ->where('rfid_tag', '!=', '')
            ->count();
        $todayStatusCounts = [
            'granted' => EntryLog::whereDate('date', today())->where('status', 'granted')->count(),
            'denied' => EntryLog::whereDate('date', today())->where('status', 'denied')->count(),
            'unknown' => EntryLog::whereDate('date', today())->where('status', 'unknown')->count(),
            'expired' => EntryLog::whereDate('date', today())->where('status', 'expired')->count(),
        ];

        // Each section gets its own title and column headings so the file is
        // readable when opened directly in Excel.
        $rows = [
            ['Generated At', $generatedAt->format('Y-m-d H:i:s')],
            [],
            ['Account Summary'],
            ['Account Type', 'Count'],
            ['Total Users', User::count()],
            ['Admin Accounts', User::where('role', 'Admin')->count()],
            ['Registrar Accounts', User::where('role', 'Registrar')->count()],
            ['Security Accounts', User::where('role', 'Security')->count()],
            [],
            ['Student RFID Registration'],
            ['Metric', 'Students'],
            ['Total Students', $totalStudents],
            ['With Registered RFID', $studentsWithRfid],
            ['Without Registered RFID', $totalStudents - $studentsWithRfid],
            [],
            ['Scan Summary (All Time)'],
            ['Metric', 'Scans'],
            ['Valid Scans (Granted)', EntryLog::where('status', 'granted')->count()],
            ['Invalid Scans (Denied, Unknown or Expired)', EntryLog::whereIn('status', ['denied', 'unknown', 'expired'])->count()],
            ['Note', 'Valid scans are Granted; invalid scans are Denied, Unknown or Expired.'],
            [],
        ];

        $firstDay = $generatedAt->copy()->subDays(6);
        $lastDay = $generatedAt->copy();
        $rows[] = ['Granted Scans - Last 7 Days ('.$firstDay->format('M d').' - '.$lastDay->format('M d, Y').')'];
        $rows[] = ['Date', 'Granted Scans'];

        for ($i = 6; $i >= 0; $i--) {
            $date = $generatedAt->copy()->subDays($i);
            $rows[] = [
                $date->format('M d'),
                EntryLog::whereDate('date', $date->toDateString())->where('status', 'granted')->count(),
            ];
        }

        $rows[] = [];
        $rows[] = ["Today's Scan Status (".$generatedAt->format('M d, Y').')'];
        $rows[] = ['Status', 'Scans'];

        foreach ($todayStatusCounts as $status => $count) {
            $rows[] = [ucfirst($status), $count];
        }

        $rows[] = ['Total Scans', array_sum($todayStatusCounts)];
        $rows[] = ['Invalid Scans (Denied, Unknown or Expired)', array_sum($todayStatusCounts) - $todayStatusCounts['granted']];

        return $this->streamCsv(
            'summary_report_'.$generatedAt->format('Ymd_His').'.csv',
            ['IETI SEVS - SUMMARY REPORT'],
            $rows,
            'Exported Summary Report',
            'Downloaded the admin summary report as a CSV file.'
        );
    }

    /**
     * @param  array<int, string>  $headers
     * @param  iterable<array<int, mixed>>  $rows
     */
    private function streamCsv(string $filename, array $headers, iterable $rows, string $action, string $details): StreamedResponse
    {
        ActivityLog::log($action, $details);

        return response()->streamDownload(function () use ($headers, $rows) {
            $handle = fopen('php://output', 'w');

            if ($handle === false) {
                throw new RuntimeException('Unable to open the CSV output stream.');
            }

            fwrite($handle, "\xEF\xBB\xBF");
            fputcsv($handle, $headers);

            foreach ($rows as $row) {
                fputcsv($handle, array_map(fn ($value) => $this->csvValue($value), $row));
            }
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Cache-Control' => 'no-store, no-cache, must-revalidate',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    private function csvValue(mixed $value): string
    {
        $text = (string) ($value ?? '');

        return preg_match('/^\s*[=+\-@]/', $text) ? "'".$text : $text;
    }

    /**
     * Admin and Super Admin accounts are managed exclusively from the
     * Super Admin control center. This dashboard may only touch the roles
     * listed in MANAGEABLE_ROLES.
     */
    private function guardManagedAccount(User $user): void
    {
        if (! in_array($user->role, self::MANAGEABLE_ROLES, true)) {
            abort(403, 'This account can only be managed from the Super Admin dashboard.');
        }
    }
}
