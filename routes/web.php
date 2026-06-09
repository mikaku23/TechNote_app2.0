<?php

use App\Http\Controllers\RoleController;
use App\Http\Controllers\SoftwareController;
use App\Http\Controllers\TrustedWebsiteController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/software/trash', [SoftwareController::class, 'trash'])
    ->name('software.trash');

Route::put('/software/{id}/restore', [SoftwareController::class, 'restore'])
    ->name('software.restore');

Route::put('/software/restore-all', [SoftwareController::class, 'restoreAll'])
    ->name('software.restoreAll');

Route::delete(
    '/software/destroy-all',
    [SoftwareController::class, 'destroyAll']
)->name('software.destroyAll');

Route::resource('software', SoftwareController::class);
Route::resource('role', RoleController::class);

Route::get(
    '/user/trash',
    [UserController::class, 'trash']
)->name('user.trash');

Route::put(
    '/user/{id}/restore',
    [UserController::class, 'restore']
)->name('user.restore');

Route::put(
    '/user/restore-all',
    [UserController::class, 'restoreAll']
)->name('user.restoreAll');
Route::delete(
    '/user/destroy-all',
    [UserController::class, 'destroyAll']
)->name('user.destroyAll');
Route::resource('user', UserController::class);

Route::resource('trusted', TrustedWebsiteController::class);
