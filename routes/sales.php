<?php

use App\Http\Controllers\Sales\InvoiceController;
use App\Http\Controllers\Sales\PaymentController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->prefix('sales')->name('sales.')->group(function () {
    // Route order matters: static "create" paths must be registered before
    // wildcard "{invoice}"/"{payment}" show routes.
    Route::middleware('permission:invoices.view')->group(function () {
        Route::get('invoices', [InvoiceController::class, 'index'])->name('invoices.index');
    });
    Route::middleware('permission:invoices.manage')->group(function () {
        Route::get('invoices/create', [InvoiceController::class, 'create'])->name('invoices.create');
        Route::post('invoices', [InvoiceController::class, 'store'])->name('invoices.store');
        Route::get('invoices/{invoice}/edit', [InvoiceController::class, 'edit'])->name('invoices.edit');
        Route::put('invoices/{invoice}', [InvoiceController::class, 'update'])->name('invoices.update');
        Route::delete('invoices/{invoice}', [InvoiceController::class, 'destroy'])->name('invoices.destroy');
        Route::post('invoices/{invoice}/post', [InvoiceController::class, 'post'])->name('invoices.post');
    });
    Route::middleware('permission:invoices.view')->group(function () {
        Route::get('invoices/{invoice}', [InvoiceController::class, 'show'])->name('invoices.show');
    });

    // Payments are posted immediately on creation (Section 20): no
    // edit/update/destroy routes, same as Journals.
    Route::middleware('permission:payments.view')->group(function () {
        Route::get('payments', [PaymentController::class, 'index'])->name('payments.index');
    });
    Route::middleware('permission:payments.manage')->group(function () {
        Route::get('payments/create', [PaymentController::class, 'create'])->name('payments.create');
        Route::post('payments', [PaymentController::class, 'store'])->name('payments.store');
    });
    Route::middleware('permission:payments.view')->group(function () {
        Route::get('payments/{payment}', [PaymentController::class, 'show'])->name('payments.show');
    });
});
