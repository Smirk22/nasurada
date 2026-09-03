<?php

use Illuminate\Support\Facades\Route;

Route::inertia('/', 'welcome')->name('home');

Route::get('/login', function () {
    return view('login');
})->name('login');

Route::get('/rdashboard', function () {
    return view('rdashboard');
})->name('rdashboard');

// Route::middleware(['auth', 'verified'])->group(function () {
   // Route::inertia('rdashboard', 'rdashboard')->name('rdashboard');
// });

require __DIR__.'/settings.php';