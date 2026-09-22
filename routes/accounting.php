<?php

use App\Http\Controllers\Accounting\AccountController;
use App\Http\Controllers\Accounting\JournalController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->prefix('accounting')->name('accounting.')->group(function () {
    Route::resource('accounts', AccountController::class)->except(['show']);

    // Posted journals are financially immutable (Section 20): no edit/destroy
    // routes until an approved void/reversal workflow exists.
    Route::resource('journals', JournalController::class)->only(['index', 'create', 'store', 'show']);
});
