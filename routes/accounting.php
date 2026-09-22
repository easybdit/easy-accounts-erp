<?php

use App\Http\Controllers\Accounting\AccountController;
use App\Http\Controllers\Accounting\GeneralLedgerController;
use App\Http\Controllers\Accounting\JournalController;
use App\Http\Controllers\Accounting\TrialBalanceController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->prefix('accounting')->name('accounting.')->group(function () {
    Route::middleware('permission:accounts.view')->group(function () {
        Route::get('accounts', [AccountController::class, 'index'])->name('accounts.index');
        Route::get('ledger', [GeneralLedgerController::class, 'index'])->name('ledger.index');
        Route::get('trial-balance', [TrialBalanceController::class, 'index'])->name('trial-balance.index');
    });
    Route::middleware('permission:accounts.manage')->group(function () {
        Route::get('accounts/create', [AccountController::class, 'create'])->name('accounts.create');
        Route::post('accounts', [AccountController::class, 'store'])->name('accounts.store');
        Route::get('accounts/{account}/edit', [AccountController::class, 'edit'])->name('accounts.edit');
        Route::put('accounts/{account}', [AccountController::class, 'update'])->name('accounts.update');
        Route::delete('accounts/{account}', [AccountController::class, 'destroy'])->name('accounts.destroy');
    });

    // Posted journals are financially immutable (Section 20): still no
    // edit/destroy routes — voiding posts an equal-and-opposite reversing
    // journal instead (VoidJournal action), never edits or deletes the
    // original.
    //
    // Route order matters here: the static "journals/create" route must be
    // registered before the wildcard "journals/{journal}" show route, or
    // Laravel would match "/journals/create" as {journal} = "create" first.
    Route::middleware('permission:journal.view')->group(function () {
        Route::get('journals', [JournalController::class, 'index'])->name('journals.index');
    });
    Route::middleware('permission:journal.manage')->group(function () {
        Route::get('journals/create', [JournalController::class, 'create'])->name('journals.create');
        Route::post('journals', [JournalController::class, 'store'])->name('journals.store');
        Route::post('journals/{journal}/void', [JournalController::class, 'void'])->name('journals.void');
    });
    Route::middleware('permission:journal.view')->group(function () {
        Route::get('journals/{journal}', [JournalController::class, 'show'])->name('journals.show');
    });
});
