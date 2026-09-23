<?php

use App\Http\Controllers\Banking\BankAccountController;
use App\Http\Controllers\Banking\BankDepositController;
use App\Http\Controllers\Banking\ReconciliationController;
use App\Http\Controllers\Banking\TransferController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->prefix('banking')->name('banking.')->group(function () {
    Route::middleware('permission:banking.view')->group(function () {
        Route::get('accounts', [BankAccountController::class, 'index'])->name('accounts.index');
        Route::get('accounts/{account}/reconcile', [ReconciliationController::class, 'index'])->name('reconciliation.index');
    });
    Route::middleware('permission:banking.manage')->group(function () {
        Route::post('accounts/{account}/reconcile', [ReconciliationController::class, 'store'])->name('reconciliation.store');
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

    // Bank Deposits batch undeposited customer Payments into one journal
    // entry against a real bank account — also posted immediately, no
    // edit/destroy, same as Transfers above.
    Route::middleware('permission:banking.view')->group(function () {
        Route::get('deposits', [BankDepositController::class, 'index'])->name('deposits.index');
    });
    Route::middleware('permission:banking.manage')->group(function () {
        Route::post('deposits', [BankDepositController::class, 'store'])->name('deposits.store');
    });
    Route::middleware('permission:banking.view')->group(function () {
        Route::get('deposits/{deposit}', [BankDepositController::class, 'show'])->name('deposits.show');
    });
});
