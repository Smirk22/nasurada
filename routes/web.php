<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\RegistrarController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\EntryLogController;

Route::inertia('/', 'welcome')->name('home');

Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.store');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

Route::get('/register', [AuthController::class, 'showRegisterForm'])->name('register');
Route::post('/register', [AuthController::class, 'register'])->name('register.store');

// Admin Routes
Route::get('/adashboard', [AdminController::class, 'dashboard'])->name('adashboard');
Route::post('/admin/users', [AdminController::class, 'storeUser'])->name('admin.users.store');
Route::put('/admin/users/{user}', [AdminController::class, 'updateUser'])->name('admin.users.update');
Route::delete('/admin/users/{user}', [AdminController::class, 'destroyUser'])->name('admin.users.destroy');

// Registrar Routes
Route::get('/rdashboard', [RegistrarController::class, 'dashboard'])->name('rdashboard');
Route::get('/registrar/students', [RegistrarController::class, 'students'])->name('registrar.students');
Route::post('/registrar/students', [RegistrarController::class, 'store'])->name('registrar.students.store');
Route::put('/registrar/students/{student}', [RegistrarController::class, 'update'])->name('registrar.students.update');
Route::delete('/registrar/students/{student}', [RegistrarController::class, 'destroy'])->name('registrar.students.destroy');
Route::post('/registrar/students/unenroll-all', [RegistrarController::class, 'unenrollAll'])->name('registrar.students.unenrollAll');


// Entry Logs Routes
Route::middleware('auth')->group(function () {
    Route::get('/registrar/entry-logs', [EntryLogController::class, 'index'])->name('entry-logs.index');
    Route::get('/registrar/entry-logs/section/{section}', [EntryLogController::class, 'getBySection'])->name('entry-logs.getBySection');
    Route::post('/registrar/entry-logs', [EntryLogController::class, 'store'])->name('entry-logs.store');
});

require __DIR__.'/settings.php';
