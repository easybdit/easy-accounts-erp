<?php

use App\Http\Controllers\Contacts\CustomerController;
use App\Http\Controllers\Contacts\VendorController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    // Route order matters: "create" (a static path) must be registered
    // before "{customer}" (a wildcard show route), or Laravel would try to
    // resolve "create" as a customer ID.
    Route::middleware('permission:customers.view')->group(function () {
        Route::get('customers', [CustomerController::class, 'index'])->name('customers.index');
    });
    Route::middleware('permission:customers.manage')->group(function () {
        Route::get('customers/create', [CustomerController::class, 'create'])->name('customers.create');
        Route::post('customers', [CustomerController::class, 'store'])->name('customers.store');
        Route::get('customers/{customer}/edit', [CustomerController::class, 'edit'])->name('customers.edit');
        Route::put('customers/{customer}', [CustomerController::class, 'update'])->name('customers.update');
        Route::delete('customers/{customer}', [CustomerController::class, 'destroy'])->name('customers.destroy');
    });
    Route::middleware('permission:customers.view')->group(function () {
        Route::get('customers/{customer}', [CustomerController::class, 'show'])->name('customers.show');
    });

    Route::middleware('permission:vendors.view')->group(function () {
        Route::get('vendors', [VendorController::class, 'index'])->name('vendors.index');
    });
    Route::middleware('permission:vendors.manage')->group(function () {
        Route::get('vendors/create', [VendorController::class, 'create'])->name('vendors.create');
        Route::post('vendors', [VendorController::class, 'store'])->name('vendors.store');
        Route::get('vendors/{vendor}/edit', [VendorController::class, 'edit'])->name('vendors.edit');
        Route::put('vendors/{vendor}', [VendorController::class, 'update'])->name('vendors.update');
        Route::delete('vendors/{vendor}', [VendorController::class, 'destroy'])->name('vendors.destroy');
    });
    Route::middleware('permission:vendors.view')->group(function () {
        Route::get('vendors/{vendor}', [VendorController::class, 'show'])->name('vendors.show');
    });
});
