<?php

use App\Http\Controllers\Tax\TaxRateController;
use App\Http\Controllers\Tax\TaxReportController;
use App\Http\Controllers\Tax\WithholdingTaxRateController;
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

    // TDS/VDS withholding rates — reuses tax.* rather than a separate
    // permission pair, same reasoning as every other sub-feature this
    // session. Route order matters: "create" before wildcard
    // "{withholding_tax_rate}".
    Route::middleware('permission:tax.view')->group(function () {
        Route::get('withholding-rates', [WithholdingTaxRateController::class, 'index'])->name('withholding-rates.index');
    });
    Route::middleware('permission:tax.manage')->group(function () {
        Route::get('withholding-rates/create', [WithholdingTaxRateController::class, 'create'])->name('withholding-rates.create');
        Route::post('withholding-rates', [WithholdingTaxRateController::class, 'store'])->name('withholding-rates.store');
        Route::get('withholding-rates/{withholding_tax_rate}/edit', [WithholdingTaxRateController::class, 'edit'])->name('withholding-rates.edit');
        Route::put('withholding-rates/{withholding_tax_rate}', [WithholdingTaxRateController::class, 'update'])->name('withholding-rates.update');
        Route::delete('withholding-rates/{withholding_tax_rate}', [WithholdingTaxRateController::class, 'destroy'])->name('withholding-rates.destroy');
    });
});
