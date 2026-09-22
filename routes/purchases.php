<?php

use App\Http\Controllers\Purchases\BillController;
use App\Http\Controllers\Purchases\VendorPaymentController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->prefix('purchases')->name('purchases.')->group(function () {
    // Route order matters: static "create" paths must be registered before
    // wildcard "{bill}"/"{vendor_payment}" show routes.
    Route::middleware('permission:bills.view')->group(function () {
        Route::get('bills', [BillController::class, 'index'])->name('bills.index');
    });
    Route::middleware('permission:bills.manage')->group(function () {
        Route::get('bills/create', [BillController::class, 'create'])->name('bills.create');
        Route::post('bills', [BillController::class, 'store'])->name('bills.store');
        Route::get('bills/{bill}/edit', [BillController::class, 'edit'])->name('bills.edit');
        Route::put('bills/{bill}', [BillController::class, 'update'])->name('bills.update');
        Route::delete('bills/{bill}', [BillController::class, 'destroy'])->name('bills.destroy');
        Route::post('bills/{bill}/post', [BillController::class, 'post'])->name('bills.post');
    });
    Route::middleware('permission:bills.view')->group(function () {
        Route::get('bills/{bill}', [BillController::class, 'show'])->name('bills.show');
    });

    // Vendor payments are posted immediately on creation (Section 20): no
    // edit/update/destroy routes, same as Journals and customer Payments.
    Route::middleware('permission:payments.view')->group(function () {
        Route::get('vendor-payments', [VendorPaymentController::class, 'index'])->name('vendor-payments.index');
    });
    Route::middleware('permission:payments.manage')->group(function () {
        Route::get('vendor-payments/create', [VendorPaymentController::class, 'create'])->name('vendor-payments.create');
        Route::post('vendor-payments', [VendorPaymentController::class, 'store'])->name('vendor-payments.store');
    });
    Route::middleware('permission:payments.view')->group(function () {
        Route::get('vendor-payments/{vendor_payment}', [VendorPaymentController::class, 'show'])->name('vendor-payments.show');
    });
});
