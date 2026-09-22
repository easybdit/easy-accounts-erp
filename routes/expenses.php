<?php

use App\Http\Controllers\Expenses\ExpenseCategoryController;
use App\Http\Controllers\Expenses\ExpenseController;
use App\Http\Controllers\Expenses\RecurringExpenseController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->prefix('expenses')->name('expenses.')->group(function () {
    Route::middleware('permission:expenses.view')->group(function () {
        Route::get('categories', [ExpenseCategoryController::class, 'index'])->name('categories.index');
    });
    Route::middleware('permission:expenses.manage')->group(function () {
        Route::get('categories/create', [ExpenseCategoryController::class, 'create'])->name('categories.create');
        Route::post('categories', [ExpenseCategoryController::class, 'store'])->name('categories.store');
        Route::get('categories/{category}/edit', [ExpenseCategoryController::class, 'edit'])->name('categories.edit');
        Route::put('categories/{category}', [ExpenseCategoryController::class, 'update'])->name('categories.update');
        Route::delete('categories/{category}', [ExpenseCategoryController::class, 'destroy'])->name('categories.destroy');
    });

    // Expenses are posted immediately on creation (Section 20), same as
    // Journals and Payments: no edit/update/destroy routes. Route order
    // matters: "create" must be registered before wildcard "{expense}".
    Route::middleware('permission:expenses.view')->group(function () {
        Route::get('entries', [ExpenseController::class, 'index'])->name('entries.index');
    });
    Route::middleware('permission:expenses.manage')->group(function () {
        Route::get('entries/create', [ExpenseController::class, 'create'])->name('entries.create');
        Route::post('entries', [ExpenseController::class, 'store'])->name('entries.store');
    });
    Route::middleware('permission:expenses.view')->group(function () {
        Route::get('entries/{expense}', [ExpenseController::class, 'show'])->name('entries.show');
        Route::get('entries/{expense}/attachments/{attachment}', [ExpenseController::class, 'downloadAttachment'])->name('entries.attachments.download');
    });
    Route::middleware('permission:expenses.manage')->group(function () {
        Route::delete('entries/{expense}/attachments/{attachment}', [ExpenseController::class, 'destroyAttachment'])->name('entries.attachments.destroy');
    });

    // Recurring Expenses are templates only (Section 60: no scheduler-driven
    // automation), same "Generate Now" pattern as Recurring Invoices. Since
    // an Expense has no draft state, generating one posts it immediately —
    // identical to manually recording an expense, not a special case.
    Route::middleware('permission:expenses.view')->group(function () {
        Route::get('recurring', [RecurringExpenseController::class, 'index'])->name('recurring.index');
    });
    Route::middleware('permission:expenses.manage')->group(function () {
        Route::get('recurring/create', [RecurringExpenseController::class, 'create'])->name('recurring.create');
        Route::post('recurring', [RecurringExpenseController::class, 'store'])->name('recurring.store');
        Route::get('recurring/{recurring_expense}/edit', [RecurringExpenseController::class, 'edit'])->name('recurring.edit');
        Route::put('recurring/{recurring_expense}', [RecurringExpenseController::class, 'update'])->name('recurring.update');
        Route::delete('recurring/{recurring_expense}', [RecurringExpenseController::class, 'destroy'])->name('recurring.destroy');
        Route::post('recurring/{recurring_expense}/generate', [RecurringExpenseController::class, 'generate'])->name('recurring.generate');
    });
});
