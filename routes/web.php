<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route(auth()->check() ? 'dashboard' : 'login');
});

Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified', 'permission:dashboard.view'])
    ->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Content is filtered per-category inside the controller based on the
    // viewer's own permissions, so no single permission gates this route.
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
});

require __DIR__.'/accounting.php';
require __DIR__.'/contacts.php';
require __DIR__.'/sales.php';
require __DIR__.'/purchases.php';
require __DIR__.'/expenses.php';
require __DIR__.'/banking.php';
require __DIR__.'/inventory.php';
require __DIR__.'/tax.php';
require __DIR__.'/reports.php';
require __DIR__.'/security.php';
require __DIR__.'/auth.php';
