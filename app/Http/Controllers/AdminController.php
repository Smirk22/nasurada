<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Incident;
use App\Models\Student;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AdminController extends Controller
{
    public function dashboard()
    {
        $totalUsers = User::count();
        $totalStudents = Student::count();
        $invalidRfidScans = Incident::count();
        $users = User::all();
        $activities = ActivityLog::latest()->get();

        return view('adashboard', compact('totalUsers', 'totalStudents', 'invalidRfidScans', 'users', 'activities'));
    }

    public function storeUser(Request $request)
    {
        $validated = $request->validate([
            'name'     => 'required|string|max:255',
            'username' => 'required|string|unique:accounts,username',
            'email'    => 'required|email|unique:accounts,email',
            'password' => 'required|min:6',
            'role'     => 'required|string',
        ]);

        $validated['password'] = Hash::make($validated['password']);

        User::create($validated);

        ActivityLog::create([
            'user_name' => auth()->user()->username ?? 'Admin',
            'role'      => 'Admin',
            'action'    => 'Created User Account',
            'details'   => "Added user '{$validated['username']}' with role '{$validated['role']}'",
        ]);

        return redirect()->back()->with('success', 'New staff user account created!');
    }

    public function updateUser(Request $request, User $user)
    {
        $validated = $request->validate([
            'name'     => 'required|string|max:255',
            'username' => 'required|string|unique:accounts,username,' . $user->id,
            'email'    => 'required|email|unique:accounts,email,' . $user->id,
            'role'     => 'required|string',
        ]);

        if ($request->filled('password')) {
            $validated['password'] = Hash::make($request->password);
        }

        $user->update($validated);

        ActivityLog::create([
            'user_name' => auth()->user()->username ?? 'Admin',
            'role'      => 'Admin',
            'action'    => 'Updated User Account',
            'details'   => "Updated account settings for '{$user->username}'",
        ]);

        return redirect()->back()->with('success', 'User account updated!');
    }

    public function destroyUser(User $user)
    {
        $username = $user->username;
        $user->delete();

        ActivityLog::create([
            'user_name' => auth()->user()->username ?? 'Admin',
            'role'      => 'Admin',
            'action'    => 'Deleted User Account',
            'details'   => "Removed user '{$username}' from system",
        ]);

        return redirect()->back()->with('success', 'User account deleted.');
    }
}