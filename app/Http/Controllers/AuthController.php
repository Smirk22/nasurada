<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function showLoginForm()
    {
        return view('login'); // resources/views/login.blade.php
    }

    public function login(Request $request)
    {
    $credentials = $request->validate([
        'username' => ['required', 'string'],
        'password' => ['required'],
    ]);

    if (Auth::attempt($credentials)) {
        $request->session()->regenerate();
        return redirect()->intended('/rdashboard');
    }

    return back()->withErrors([
        'password' => 'wrong password',
    ]);
    }

    public function logout(Request $request)
    {
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
        'username' => ['required', 'string', 'max:100'],
        'email' => ['required', 'email', 'unique:accounts,email'],
        'password' => ['required', 'min:8'],
    ]);

    $account = User::create([
        'username' => $validated['username'],
        'email' => $validated['email'],
        'password' => Hash::make($validated['password']),
    ]);

    return redirect('/login');
    }
}
