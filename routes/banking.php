<?php

use App\Http\Controllers\Banking\BankAccountController;
use App\Http\Controllers\Banking\TransferController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->prefix('banking')->name('banking.')->group(function () {
    Route::middleware('permission:banking.view')->group(function () {
        Route::get('accounts', [BankAccountController::class, 'index'])->name('accounts.index');
    });

    // Transfers are posted immediately on creation (Section 20), same as
    // Journals, Payments, and Expenses: no edit/update/destroy routes. Route
    // order matters: "create" must be registered before wildcard "{transfer}".
    Route::middleware('permission:banking.view')->group(function () {
        Route::get('transfers', [TransferController::class, 'index'])->name('transfers.index');
    });
    Route::middleware('permission:banking.manage')->group(function () {
        Route::get('transfers/create', [TransferController::class, 'create'])->name('transfers.create');
        Route::post('transfers', [TransferController::class, 'store'])->name('transfers.store');
    });
    Route::middleware('permission:banking.view')->group(function () {
        Route::get('transfers/{transfer}', [TransferController::class, 'show'])->name('transfers.show');
    });
});
