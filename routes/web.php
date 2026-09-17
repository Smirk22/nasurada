<?php
use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'welcome')->name('home');

Route::post('/login', [AuthController::class, 'login'])->name('login.store');

Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

Route::get('/register', [AuthController::class, 'showRegisterForm'])->name('register');

Route::post('/register', [AuthController::class, 'register'])->name('register.store');

Route::middleware(['auth', 'verified'])->group(function () {
   Route::inertia('rdashboard', 'rdashboard')->name('rdashboard');php artisan route:clear
});

require __DIR__.'/settings.php';