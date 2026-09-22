<?php

use App\Http\Controllers\Sales\EstimateController;
use App\Http\Controllers\Sales\InvoiceController;
use App\Http\Controllers\Sales\PaymentController;
use App\Http\Controllers\Sales\RecurringInvoiceController;
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
        Route::get('invoices/{invoice}/pdf', [InvoiceController::class, 'pdf'])->name('invoices.pdf');
    });

    // Estimates are non-financial (never post to the Journal) until
    // converted to a real draft Invoice (Section 90 Phase 4 open item,
    // now resolved). Reuses the invoices.* permission — a separate
    // estimates.* permission would be more granularity than this app's
    // two-tier model needs (Section 36).
    Route::middleware('permission:invoices.view')->group(function () {
        Route::get('estimates', [EstimateController::class, 'index'])->name('estimates.index');
    });
    Route::middleware('permission:invoices.manage')->group(function () {
        Route::get('estimates/create', [EstimateController::class, 'create'])->name('estimates.create');
        Route::post('estimates', [EstimateController::class, 'store'])->name('estimates.store');
        Route::get('estimates/{estimate}/edit', [EstimateController::class, 'edit'])->name('estimates.edit');
        Route::put('estimates/{estimate}', [EstimateController::class, 'update'])->name('estimates.update');
        Route::delete('estimates/{estimate}', [EstimateController::class, 'destroy'])->name('estimates.destroy');
        Route::post('estimates/{estimate}/convert', [EstimateController::class, 'convert'])->name('estimates.convert');
    });
    Route::middleware('permission:invoices.view')->group(function () {
        Route::get('estimates/{estimate}', [EstimateController::class, 'show'])->name('estimates.show');
    });

    // Recurring invoices are templates only (Section 60: manual "Generate
    // Now" trigger, no scheduler-driven automation). No show route, so
    // "create" vs "{recurring_invoice}" ordering isn't a concern here.
    Route::middleware('permission:invoices.view')->group(function () {
        Route::get('recurring-invoices', [RecurringInvoiceController::class, 'index'])->name('recurring-invoices.index');
    });
    Route::middleware('permission:invoices.manage')->group(function () {
        Route::get('recurring-invoices/create', [RecurringInvoiceController::class, 'create'])->name('recurring-invoices.create');
        Route::post('recurring-invoices', [RecurringInvoiceController::class, 'store'])->name('recurring-invoices.store');
        Route::get('recurring-invoices/{recurring_invoice}/edit', [RecurringInvoiceController::class, 'edit'])->name('recurring-invoices.edit');
        Route::put('recurring-invoices/{recurring_invoice}', [RecurringInvoiceController::class, 'update'])->name('recurring-invoices.update');
        Route::delete('recurring-invoices/{recurring_invoice}', [RecurringInvoiceController::class, 'destroy'])->name('recurring-invoices.destroy');
        Route::post('recurring-invoices/{recurring_invoice}/generate', [RecurringInvoiceController::class, 'generate'])->name('recurring-invoices.generate');
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
