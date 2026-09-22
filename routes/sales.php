<?php

use App\Http\Controllers\Sales\InvoiceController;
use App\Http\Controllers\Sales\PaymentController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->prefix('sales')->name('sales.')->group(function () {
    Route::resource('invoices', InvoiceController::class);
    Route::post('invoices/{invoice}/post', [InvoiceController::class, 'post'])->name('invoices.post');

    // Payments are posted immediately on creation (Section 20): no
    // edit/update/destroy routes, same as Journals.
    Route::resource('payments', PaymentController::class)->only(['index', 'create', 'store', 'show']);
});
