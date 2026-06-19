<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LoginLogController;
use App\Http\Controllers\MahasiswaBookingController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PenginstalanController;
use App\Http\Controllers\PerbaikanController;
use App\Http\Controllers\RekapController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\SoftwareController;
use App\Http\Controllers\TicketController;
use App\Http\Controllers\TrustedWebsiteController;
use App\Http\Controllers\UserActivityController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('auth.login');
})->name('login');

Route::post('/login', [AuthController::class, 'login'])->name('login.process');

Route::post('/logout', [AuthController::class, 'logout'])
    ->middleware('auth')
    ->name('logout');


Route::get('/forgot-password/reset', [AuthController::class, 'resetForgotPasswordFlow'])
    ->name('password.forgot.reset');
Route::get('/forgot-password', [AuthController::class, 'forgotPassword'])->name('password.forgot');
Route::post('/forgot-password/send', [AuthController::class, 'sendResetCode'])->name('password.reset.send');
Route::post('/forgot-password/resend', [AuthController::class, 'resendResetCode'])->name('password.reset.resend');
Route::post('/forgot-password/otp/verify', [AuthController::class, 'verifyResetOtp'])->name('password.reset.otp.verify');
Route::post('/forgot-password/security/verify', [AuthController::class, 'verifyResetSecurity'])->name('password.reset.security.verify');
Route::get('/forgot-password/new-password', [AuthController::class, 'showResetPasswordForm'])->name('password.reset.form');
Route::post('/forgot-password/new-password', [AuthController::class, 'updateResetPassword'])->name('password.reset.update');

Route::get('/ticket/qr/{token}', [TicketController::class, 'showByQr'])
    ->name('ticket.qr.show');

Route::middleware(['auth'])->group(function () {

    Route::get('/profile', function () {
        return view('auth.profile');
    })->name('profile.show');

    Route::get('/settings', function () {
        return view('auth.settings');
    })->name('settings.show');

    Route::get('/settings/profile', function () {
        return view('auth.settings_profile');
    })->name('settings.profile');

    Route::get('/settings/password', function () {
        return view('auth.settings_password');
    })->name('settings.password');

    Route::get('/settings/security', function () {
        return view('auth.settings_security');
    })->name('settings.security');

    Route::put('/settings/profile', [UserController::class, 'updateProfile'])
        ->name('settings.profile.update');

    Route::put('/settings/password', [UserController::class, 'updatePassword'])
        ->name('settings.password.update');

    Route::put('/settings/security', [UserController::class, 'updateSecurityQuestion'])
        ->name('settings.security.update');

    Route::get('/settings/delete', function () {
        return view('auth.settings_delete_account');
    })->name('settings.delete');

    Route::delete('/settings/account', [UserController::class, 'destroyOwnAccount'])
        ->name('settings.destroy');
});

Route::middleware(['auth', 'role:Admin'])->group(function () {

    Route::resource('notifications', NotificationController::class)->only(['index', 'show']);
    Route::patch('/notifications/{notification}/read', [NotificationController::class, 'markRead'])->name('notifications.read');
    Route::patch('/notifications/read-all', [NotificationController::class, 'markAllRead'])->name('notifications.readAll');

    Route::resource('login-log', LoginLogController::class)->only(['index', 'show']);

    Route::get('/dashboardAdmin', [DashboardController::class, 'admin'])->name('dashboard.admin');

    Route::get('/software/trash', [SoftwareController::class, 'trash'])->name('software.trash');
    Route::put('/software/{id}/restore', [SoftwareController::class, 'restore'])->name('software.restore');
    Route::put('/software/restore-all', [SoftwareController::class, 'restoreAll'])->name('software.restoreAll');
    Route::delete('/software/destroy-all', [SoftwareController::class, 'destroyAll'])->name('software.destroyAll');
    Route::resource('software', SoftwareController::class);

    Route::get('/role/trash', [RoleController::class, 'trash'])->name('role.trash');
    Route::put('/role/{id}/restore', [RoleController::class, 'restore'])->name('role.restore');
    Route::put('/role/restore-all', [RoleController::class, 'restoreAll'])->name('role.restoreAll');
    Route::post('/role/{role}/toggle-status', [RoleController::class, 'toggleStatus'])->name('role.toggleStatus');
    Route::resource('role', RoleController::class);

    Route::get('/user/trash', [UserController::class, 'trash'])->name('user.trash');
    Route::put('/user/{id}/restore', [UserController::class, 'restore'])->name('user.restore');
    Route::put('/user/restore-all', [UserController::class, 'restoreAll'])->name('user.restoreAll');
    Route::delete('/user/destroy-all', [UserController::class, 'destroyAll'])->name('user.destroyAll');
    Route::resource('user', UserController::class);

    Route::resource('trusted', TrustedWebsiteController::class);

    Route::patch(
        '/penginstalan/{penginstalan}/complete',
        [PenginstalanController::class, 'forceComplete']
    )->name('penginstalan.complete');

    Route::patch(
        '/penginstalan/{penginstalan}/failed',
        [PenginstalanController::class, 'forceFailed']
    )->name('penginstalan.failed');
    Route::get('/penginstalan/trash', [PenginstalanController::class, 'trash'])->name('penginstalan.trash');
    Route::put('/penginstalan/{id}/restore', [PenginstalanController::class, 'restore'])->name('penginstalan.restore');
    Route::put('/penginstalan/restore-all', [PenginstalanController::class, 'restoreAll'])->name('penginstalan.restoreAll');
    Route::resource('penginstalan', PenginstalanController::class);

    Route::get('perbaikan/trash', [PerbaikanController::class, 'trash'])->name('perbaikan.trash');
    Route::put('perbaikan/{id}/restore', [PerbaikanController::class, 'restore'])->name('perbaikan.restore');
    Route::put('perbaikan/restore-all', [PerbaikanController::class, 'restoreAll'])->name('perbaikan.restoreAll');

    Route::patch('perbaikan/{perbaikan}/complete', [PerbaikanController::class, 'complete'])->name('perbaikan.complete');
    Route::patch('perbaikan/{perbaikan}/failed', [PerbaikanController::class, 'failed'])->name('perbaikan.failed');
    Route::resource('perbaikan', PerbaikanController::class);

    Route::get('/ticket/logs', [TicketController::class, 'logs'])->name('ticket.logs');
    Route::get('/ticket/{ticket}/logs', [TicketController::class, 'showLogs'])->name('ticket.logs.show');
    Route::patch('/ticket/{ticket}/status', [TicketController::class, 'updateStatus'])->name('ticket.updateStatus');
    Route::resource('ticket', TicketController::class);

    Route::resource('rekap', RekapController::class);

    Route::resource('user-activity', UserActivityController::class)->only(['index', 'show']);
});




Route::middleware(['auth', 'role:Mahasiswa'])->group(function () {
    Route::get(
        '/dashboard/booking/check-availability',
        [MahasiswaBookingController::class, 'checkAvailability']
    )->name('mahasiswa.booking.check');

    Route::resource('/dashboard', MahasiswaBookingController::class)
        ->names('mahasiswa.booking')
        ->parameters(['dashboard' => 'ticket']);
});





Route::middleware(['auth', 'role:Dosen'])->group(function () {
    Route::get('/dashboard/dosen', [DashboardController::class, 'dosen'])->name('dashboard.dosen');

    // route dosen lain di sini
});
