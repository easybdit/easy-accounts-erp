<?php

use App\Http\Controllers\Security\AuditLogController;
use App\Http\Controllers\Security\IpWhitelistController;
use App\Http\Controllers\Security\LoginHistoryController;
use App\Http\Controllers\Security\RoleController;
use App\Http\Controllers\Security\UserController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->prefix('security')->name('security.')->group(function () {
    Route::middleware('permission:audit.view')->group(function () {
        Route::get('audit-log', [AuditLogController::class, 'index'])->name('audit-log.index');
        // Login history reuses audit.view — it's the same "who can review
        // the security trail" responsibility as the general audit log.
        Route::get('login-history', [LoginHistoryController::class, 'index'])->name('login-history.index');
    });

    Route::middleware('permission:settings.manage')->group(function () {
        Route::post('ip-whitelist', [IpWhitelistController::class, 'store'])->name('ip-whitelist.store');
        Route::delete('ip-whitelist/{ip_whitelist_entry}', [IpWhitelistController::class, 'destroy'])->name('ip-whitelist.destroy');
    });

    Route::middleware('permission:users.view')->group(function () {
        Route::get('users', [UserController::class, 'index'])->name('users.index');
    });
    Route::middleware('permission:users.manage')->group(function () {
        Route::get('users/create', [UserController::class, 'create'])->name('users.create');
        Route::post('users', [UserController::class, 'store'])->name('users.store');
        Route::get('users/{user}/edit', [UserController::class, 'edit'])->name('users.edit');
        Route::put('users/{user}', [UserController::class, 'update'])->name('users.update');
        Route::post('users/{user}/unlock', [UserController::class, 'unlock'])->name('users.unlock');
    });

    Route::middleware('permission:roles.view')->group(function () {
        Route::get('roles', [RoleController::class, 'index'])->name('roles.index');
    });
    Route::middleware('permission:roles.manage')->group(function () {
        Route::get('roles/create', [RoleController::class, 'create'])->name('roles.create');
        Route::post('roles', [RoleController::class, 'store'])->name('roles.store');
        Route::get('roles/{role}/edit', [RoleController::class, 'edit'])->name('roles.edit');
        Route::put('roles/{role}', [RoleController::class, 'update'])->name('roles.update');
        Route::delete('roles/{role}', [RoleController::class, 'destroy'])->name('roles.destroy');
    });
});
