<?php

use App\Http\Controllers\Purchases\BillController;
use App\Http\Controllers\Purchases\PurchaseOrderController;
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
        Route::get('bills/{bill}/pdf', [BillController::class, 'pdf'])->name('bills.pdf');
    });

    // Purchase Orders are non-financial (never post to the Journal) until
    // converted to a real draft Bill (Section 90 Phase 5 open item, now
    // resolved). Reuses bills.* rather than a separate permission pair.
    Route::middleware('permission:bills.view')->group(function () {
        Route::get('purchase-orders', [PurchaseOrderController::class, 'index'])->name('purchase-orders.index');
    });
    Route::middleware('permission:bills.manage')->group(function () {
        Route::get('purchase-orders/create', [PurchaseOrderController::class, 'create'])->name('purchase-orders.create');
        Route::post('purchase-orders', [PurchaseOrderController::class, 'store'])->name('purchase-orders.store');
        Route::get('purchase-orders/{purchase_order}/edit', [PurchaseOrderController::class, 'edit'])->name('purchase-orders.edit');
        Route::put('purchase-orders/{purchase_order}', [PurchaseOrderController::class, 'update'])->name('purchase-orders.update');
        Route::delete('purchase-orders/{purchase_order}', [PurchaseOrderController::class, 'destroy'])->name('purchase-orders.destroy');
        Route::post('purchase-orders/{purchase_order}/convert', [PurchaseOrderController::class, 'convert'])->name('purchase-orders.convert');
    });
    Route::middleware('permission:bills.view')->group(function () {
        Route::get('purchase-orders/{purchase_order}', [PurchaseOrderController::class, 'show'])->name('purchase-orders.show');
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
