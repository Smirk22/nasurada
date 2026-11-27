<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BackupController;
use App\Http\Controllers\EntryLogController;
use App\Http\Controllers\ForcedPasswordChangeController;
use App\Http\Controllers\GuardController;
use App\Http\Controllers\RegistrarController;
use App\Http\Controllers\RfidController;
use App\Http\Controllers\SuperAdminController;
use App\Http\Controllers\TwoFactorSetupController;
use Illuminate\Support\Facades\Route;

// Send the home page to the login page
Route::redirect('/', '/login')->name('home');

// Login / Logout (signed-in users visiting /login are sent to their dashboard)
Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.store');

// Two-factor challenge (used after a correct username/password when 2FA is enabled)
Route::get('/two-factor-verify', [AuthController::class, 'showTwoFactorChallenge'])->name('two-factor.challenge');
Route::post('/two-factor-verify', [AuthController::class, 'verifyTwoFactorChallenge'])->name('two-factor.challenge.verify');
Route::post('/two-factor-verify/cancel', [AuthController::class, 'cancelTwoFactorChallenge'])->name('two-factor.challenge.cancel');

// Use Fortify's logout to avoid route conflicts; AuthController::logout still available if needed

// Forced password change after temporary password login (one-time use)
Route::middleware(['auth'])->group(function () {
    Route::get('/password/change-required', [ForcedPasswordChangeController::class, 'edit'])->name('password.force-change');
    Route::post('/password/change-required', [ForcedPasswordChangeController::class, 'update'])->name('password.force-change.update');
});

// Two-factor authentication management (any signed-in staff member)
Route::middleware(['auth'])->group(function () {
    Route::get('/settings/two-factor', [TwoFactorSetupController::class, 'edit'])->name('two-factor.setup');
    Route::post('/settings/two-factor/enable', [TwoFactorSetupController::class, 'enable'])->name('two-factor.setup.enable');
    Route::post('/settings/two-factor/confirm', [TwoFactorSetupController::class, 'confirm'])->name('two-factor.setup.confirm');
    Route::post('/settings/two-factor/recovery-codes', [TwoFactorSetupController::class, 'regenerateRecoveryCodes'])->name('two-factor.setup.recovery-codes');
    Route::delete('/settings/two-factor', [TwoFactorSetupController::class, 'destroy'])->name('two-factor.setup.disable');
});

// Admin Routes (admin only)
Route::middleware(['auth', 'role:Admin'])->group(function () {
    Route::get('/adashboard', [AdminController::class, 'dashboard'])->name('adashboard');
    Route::post('/admin/users', [AdminController::class, 'storeUser'])->name('admin.users.store');
    Route::put('/admin/users/{user}', [AdminController::class, 'updateUser'])->name('admin.users.update');
    Route::post('/admin/users/{user}/temporary-password', [AdminController::class, 'generateTemporaryPassword'])->name('admin.users.temporary-password');
    Route::delete('/admin/users/{user}', [AdminController::class, 'destroyUser'])->name('admin.users.destroy');

    // Reports / exports
    Route::get('/admin/reports/summary/export', [AdminController::class, 'exportSummaryReport'])->name('admin.reports.summary.export');
    Route::get('/admin/reports/entry-logs/export', [AdminController::class, 'exportEntryLogs'])->name('admin.reports.entry-logs.export');
});

// Super Admin Routes (highest privilege — full system control)
Route::middleware(['auth', 'role:SuperAdmin'])->prefix('superadmin')->name('superadmin.')->group(function () {
    Route::get('/', [SuperAdminController::class, 'dashboard'])->name('dashboard');

    Route::post('/users', [SuperAdminController::class, 'storeUser'])->name('users.store');
    Route::put('/users/{user}', [SuperAdminController::class, 'updateUser'])->name('users.update');
    Route::post('/users/{user}/temporary-password', [SuperAdminController::class, 'generateTemporaryPassword'])->name('users.temporary-password');
    Route::delete('/users/{user}', [SuperAdminController::class, 'destroyUser'])->name('users.destroy');

    // Database backups (Super Admin only)
    Route::post('/backups', [BackupController::class, 'store'])->name('backups.store');
    Route::get('/backups/{file}', [BackupController::class, 'download'])
        ->where('file', '[A-Za-z0-9._-]+')
        ->name('backups.download');
    Route::post('/backups/{file}/restore', [BackupController::class, 'restore'])
        ->where('file', '[A-Za-z0-9._-]+')
        ->name('backups.restore');
    Route::delete('/backups/{file}', [BackupController::class, 'destroy'])
        ->where('file', '[A-Za-z0-9._-]+')
        ->name('backups.destroy');

    Route::put('/settings', [SuperAdminController::class, 'updateSettings'])->name('settings.update');
});

// Registrar Routes (super admin, admin, and registrar)
Route::middleware(['auth', 'role:SuperAdmin,Admin,Registrar'])->group(function () {
    Route::get('/rdashboard', [RegistrarController::class, 'dashboard'])->name('rdashboard');
    Route::get('/registrar/students', [RegistrarController::class, 'students'])->name('registrar.students');
    Route::get('/registrar/students/export', [RegistrarController::class, 'exportStudents'])->name('registrar.students.export');
    Route::get('/registrar/reports/export', [RegistrarController::class, 'exportReports'])->name('registrar.reports.export');
    Route::post('/registrar/students', [RegistrarController::class, 'store'])->name('registrar.students.store');
    Route::put('/registrar/students/{student}', [RegistrarController::class, 'update'])->name('registrar.students.update');
    Route::post('/registrar/students/{student}/photo', [RegistrarController::class, 'updatePhoto'])->name('registrar.students.photo');
    Route::delete('/registrar/students/{student}', [RegistrarController::class, 'destroy'])->name('registrar.students.destroy');
    Route::post('/registrar/students/unenroll-all', [RegistrarController::class, 'unenrollAll'])->name('registrar.students.unenrollAll');

    // Registration scan mode: taps are captured for enrollment, not logged as gate entries.
    Route::post('/registrar/rfid/scan-mode', [RfidController::class, 'beginRegistration'])->name('registrar.rfid.scan-mode');
    Route::post('/registrar/rfid/scan-mode/cancel', [RfidController::class, 'endRegistration'])->name('registrar.rfid.scan-mode.cancel');
    Route::get('/registrar/rfid/scan-latest', [RfidController::class, 'latestRegistrationScan'])->name('registrar.rfid.scan-latest');
});

// Entry Logs Routes (any staff role)
Route::middleware(['auth', 'role:Admin,Registrar,Security'])->group(function () {
    Route::get('/registrar/entry-logs', [EntryLogController::class, 'index'])->name('entry-logs.index');
    Route::get('/registrar/entry-logs/summary', [EntryLogController::class, 'summary'])->name('entry-logs.summary');
    Route::get('/registrar/entry-logs/student', [EntryLogController::class, 'getByStudentNumber'])->name('entry-logs.getByStudentNumber');
    Route::get('/registrar/entry-logs/section/{section}', [EntryLogController::class, 'getBySection'])->name('entry-logs.getBySection');
    Route::post('/registrar/entry-logs', [EntryLogController::class, 'store'])->name('entry-logs.store');
});

// Guard Routes (security and admin)
Route::middleware(['auth', 'role:Admin,Security'])->prefix('Security')->group(function () {
    Route::get('/dashboard', [GuardController::class, 'dashboard'])->name('guard.dashboard');
    Route::post('/visitor', [GuardController::class, 'visitorStore'])->name('guard.visitor.store');
    Route::post('/temporary-pass', [GuardController::class, 'visitorStore'])->name('guard.temporary-pass.store');
    Route::get('/visitors', [GuardController::class, 'visitors'])->name('guard.visitors.index');
    Route::get('/visitor-logs', [GuardController::class, 'visitorLogs'])->name('guard.visitor-logs.index');
    Route::delete('/visitors/{visitor}', [GuardController::class, 'visitorDestroy'])->name('guard.visitors.destroy');
    Route::get('/kiosk', [GuardController::class, 'kiosk'])->name('guard.kiosk');
    Route::get('/kiosk/latest', [GuardController::class, 'latestScan'])->name('guard.kiosk.latest');

    // Visitor registration scan mode: taps are captured for the visitor form,
    // not logged as gate entries in the live feed.
    Route::post('/rfid/scan-mode', [RfidController::class, 'beginVisitorRegistration'])->name('guard.rfid.scan-mode');
    Route::post('/rfid/scan-mode/cancel', [RfidController::class, 'endVisitorRegistration'])->name('guard.rfid.scan-mode.cancel');
    Route::get('/rfid/scan-latest', [RfidController::class, 'latestVisitorRegistrationScan'])->name('guard.rfid.scan-latest');
});

require __DIR__.'/settings.php';
