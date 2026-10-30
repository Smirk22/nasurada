<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class AuthController extends Controller
{

    public function showLoginForm(Request $request)
    {
        if (Auth::check()) {
            $request->session()->forget('guard_logged_in_at');
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        return response()
            ->view('login')
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'username' => ['required', 'string'],
            'password' => ['required'],
        ]);

        if (Auth::attempt($credentials)) {
            $request->session()->regenerate();

            $user = Auth::user();
            $role = strtolower(trim($user->role));

            // 1. Admin redirect
            if ($role === 'admin') {
                return redirect()->intended('/adashboard');
            }

            // 2. Guard redirect (Explicitly clear old timestamp and set fresh login time to reset live entry feed)
            if ($role === 'security') {
                $request->session()->forget('guard_logged_in_at');
                $request->session()->put('guard_logged_in_at', Carbon::now());
                return redirect()->intended('/Security/dashboard');
            }

            // 3. Registrar (default fallback)
            return redirect()->intended('/rdashboard');
        }

        return back()->withErrors([
            'username' => 'The provided credentials do not match our records.',
        ])->onlyInput('username');
    }

    public function logout(Request $request)
    {
       $request->session()->forget('feed_reset_after_id');
       Auth::logout();
       $request->session()->invalidate();
       $request->session()->regenerateToken();

       return redirect('/login');
    }

    public function showRegisterForm()
    {
        return view('register');
    }

    public function register(Request $request)
    {
        $validated = $request->validate([
            'username' => ['required', 'string', 'max:100', 'unique:accounts,username'],
            'email' => ['required', 'email', 'unique:accounts,email'],
            'password' => ['required', 'min:8'],
        ]);

        User::create([
            'username' => $validated['username'],
            'email' => $validated['email'],
            'password' => $validated['password'], // Automatically hashed by User model casting
            'role' => 'registrar',
        ]);

        return redirect('/login')->with('success', 'Account created successfully. Please log in.');
    }
}