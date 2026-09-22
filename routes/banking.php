<?php

use App\Http\Controllers\Banking\BankAccountController;
use App\Http\Controllers\Banking\TransferController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->prefix('banking')->name('banking.')->group(function () {
    Route::get('accounts', [BankAccountController::class, 'index'])->name('accounts.index');

    // Transfers are posted immediately on creation (Section 20), same as
    // Journals, Payments, and Expenses: no edit/update/destroy routes.
    Route::resource('transfers', TransferController::class)->only(['index', 'create', 'store', 'show']);
});
