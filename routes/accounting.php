<?php

use App\Http\Controllers\Accounting\AccountController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->prefix('accounting')->name('accounting.')->group(function () {
    Route::resource('accounts', AccountController::class)->except(['show']);
});
