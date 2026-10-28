<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\RegistrarController;
use App\Http\Controllers\EntryLogController;
use App\Http\Controllers\GuardController;
use Illuminate\Support\Facades\Route;

// Send the home page to the login page
Route::redirect('/', '/login')->name('home');

// Login / Logout (no guest middleware, so /login always shows the form)
Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.store');

Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');

// Admin Routes (admin only)
Route::middleware(['auth', 'role:Admin'])->group(function () {
    Route::get('/adashboard', [AdminController::class, 'dashboard'])->name('adashboard');
    Route::post('/admin/users', [AdminController::class, 'storeUser'])->name('admin.users.store');
    Route::put('/admin/users/{user}', [AdminController::class, 'updateUser'])->name('admin.users.update');
    Route::delete('/admin/users/{user}', [AdminController::class, 'destroyUser'])->name('admin.users.destroy');
});

// Registrar Routes (registrar only)
Route::middleware(['auth', 'role:Registrar'])->group(function () {
    Route::get('/rdashboard', [RegistrarController::class, 'dashboard'])->name('rdashboard');
    Route::get('/registrar/students', [RegistrarController::class, 'students'])->name('registrar.students');
    Route::post('/registrar/students', [RegistrarController::class, 'store'])->name('registrar.students.store');
    Route::put('/registrar/students/{student}', [RegistrarController::class, 'update'])->name('registrar.students.update');
    Route::delete('/registrar/students/{student}', [RegistrarController::class, 'destroy'])->name('registrar.students.destroy');
    Route::post('/registrar/students/unenroll-all', [RegistrarController::class, 'unenrollAll'])->name('registrar.students.unenrollAll');
});

// Entry Logs Routes (any staff role)
Route::middleware(['auth', 'role:Admin,Registrar,Security'])->group(function () {
    Route::get('/registrar/entry-logs', [EntryLogController::class, 'index'])->name('entry-logs.index');
    Route::get('/registrar/entry-logs/section/{section}', [EntryLogController::class, 'getBySection'])->name('entry-logs.getBySection');
    Route::post('/registrar/entry-logs', [EntryLogController::class, 'store'])->name('entry-logs.store');
});

// Guard Routes (security only)
Route::middleware(['auth', 'role:Security'])->prefix('Security')->group(function () {
    Route::get('/dashboard', [GuardController::class, 'dashboard'])->name('guard.dashboard');
    Route::post('/visitor', [GuardController::class, 'visitorStore'])->name('guard.visitor.store');
    Route::get('/kiosk', [GuardController::class, 'kiosk'])->name('guard.kiosk');
    Route::get('/kiosk/latest', [GuardController::class, 'latestScan'])->name('guard.kiosk.latest');
});

require __DIR__.'/settings.php';