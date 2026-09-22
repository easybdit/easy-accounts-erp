<?php

use App\Http\Controllers\Sales\InvoiceController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->prefix('sales')->name('sales.')->group(function () {
    Route::resource('invoices', InvoiceController::class);
    Route::post('invoices/{invoice}/post', [InvoiceController::class, 'post'])->name('invoices.post');
});
