<?php

use App\Http\Controllers\Tax\TaxRateController;
use App\Http\Controllers\Tax\TaxReportController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->prefix('tax')->name('tax.')->group(function () {
    Route::middleware('permission:tax.view')->group(function () {
        Route::get('rates', [TaxRateController::class, 'index'])->name('rates.index');
        Route::get('report', [TaxReportController::class, 'index'])->name('report');
    });
    Route::middleware('permission:tax.manage')->group(function () {
        Route::get('rates/create', [TaxRateController::class, 'create'])->name('rates.create');
        Route::post('rates', [TaxRateController::class, 'store'])->name('rates.store');
        Route::get('rates/{rate}/edit', [TaxRateController::class, 'edit'])->name('rates.edit');
        Route::put('rates/{rate}', [TaxRateController::class, 'update'])->name('rates.update');
        Route::delete('rates/{rate}', [TaxRateController::class, 'destroy'])->name('rates.destroy');
    });
});
