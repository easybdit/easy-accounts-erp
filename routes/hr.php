<?php

use App\Http\Controllers\HR\PayrollComponentController;
use App\Http\Controllers\HR\PayrollController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->prefix('hr')->name('hr.')->group(function () {
    Route::middleware('permission:payroll.view')->group(function () {
        Route::get('payroll', [PayrollController::class, 'index'])->name('payroll.index');
        Route::get('payroll-components', [PayrollComponentController::class, 'index'])->name('payroll-components.index');
    });
    Route::middleware('permission:payroll.manage')->group(function () {
        Route::post('payroll/generate', [PayrollController::class, 'generate'])->name('payroll.generate');
        Route::post('payroll/{salary_slip}/post', [PayrollController::class, 'post'])->name('payroll.post');

        Route::get('payroll-components/create', [PayrollComponentController::class, 'create'])->name('payroll-components.create');
        Route::post('payroll-components', [PayrollComponentController::class, 'store'])->name('payroll-components.store');
        Route::get('payroll-components/{payroll_component}/edit', [PayrollComponentController::class, 'edit'])->name('payroll-components.edit');
        Route::put('payroll-components/{payroll_component}', [PayrollComponentController::class, 'update'])->name('payroll-components.update');
        Route::delete('payroll-components/{payroll_component}', [PayrollComponentController::class, 'destroy'])->name('payroll-components.destroy');
    });
});
