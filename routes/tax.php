<?php

use App\Http\Controllers\Tax\TaxRateController;
use App\Http\Controllers\Tax\TaxReportController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->prefix('tax')->name('tax.')->group(function () {
    Route::resource('rates', TaxRateController::class)->except(['show']);
    Route::get('report', [TaxReportController::class, 'index'])->name('report');
});
