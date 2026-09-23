<?php

use App\Http\Controllers\Payments\PublicPaymentController;
use Illuminate\Support\Facades\Route;

// Deliberately outside the 'auth' middleware group — reached only via a
// token-bearing link a staff member generated and sent to the customer
// directly (Section 90; this app has no customer login/portal), plus
// SSLCommerz's own success/fail/cancel/ipn callbacks. The token itself is
// the only credential; see InvoicePaymentLink's docblock.
Route::prefix('pay')->name('pay.')->middleware('throttle:30,1')->group(function () {
    Route::get('{token}', [PublicPaymentController::class, 'show'])->name('show');
    Route::post('{token}/initiate', [PublicPaymentController::class, 'initiate'])->name('initiate');

    // SSLCommerz POSTs to these without a Laravel CSRF token — exempted in
    // bootstrap/app.php.
    Route::post('callback/success', [PublicPaymentController::class, 'success'])->name('callback.success');
    Route::post('callback/fail', [PublicPaymentController::class, 'fail'])->name('callback.fail');
    Route::post('callback/cancel', [PublicPaymentController::class, 'cancel'])->name('callback.cancel');
    Route::post('callback/ipn', [PublicPaymentController::class, 'ipn'])->name('callback.ipn');
});
