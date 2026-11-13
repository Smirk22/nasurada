<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\EntryLog;
use App\Models\Student;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

class AdminController extends Controller
{
    public function dashboard()
    {
        $totalUsers = User::count();
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
        $activities = ActivityLog::latest()->get();

        return view('adashboard', compact(
            'totalUsers',
            'totalStudents',
            'invalidRfidScans',
            'studentsWithRfid',
            'studentsWithoutRfid',
            'validScans',
            'invalidScans',
            'users',
            'activities'
        ));
    }

    public function storeUser(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'username' => 'required|string|unique:accounts,username',
            'email' => 'required|email|unique:accounts,email',
            'password' => ['required', 'string', Password::default()],
            'role' => 'required|string',
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
            'role' => 'required|string',
        ]);

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
}
