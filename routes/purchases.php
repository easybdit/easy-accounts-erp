<?php

use App\Http\Controllers\Purchases\BillController;
use App\Http\Controllers\Purchases\VendorPaymentController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->prefix('purchases')->name('purchases.')->group(function () {
    Route::resource('bills', BillController::class);
    Route::post('bills/{bill}/post', [BillController::class, 'post'])->name('bills.post');

    // Vendor payments are posted immediately on creation (Section 20): no
    // edit/update/destroy routes, same as Journals and customer Payments.
    Route::resource('vendor-payments', VendorPaymentController::class)->only(['index', 'create', 'store', 'show']);
});
