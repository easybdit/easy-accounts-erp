<?php

use App\Http\Controllers\Expenses\ExpenseCategoryController;
use App\Http\Controllers\Expenses\ExpenseController;
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
    // matters: "create" must be registered before wildcard "{entry}".
    Route::middleware('permission:expenses.view')->group(function () {
        Route::get('entries', [ExpenseController::class, 'index'])->name('entries.index');
    });
    Route::middleware('permission:expenses.manage')->group(function () {
        Route::get('entries/create', [ExpenseController::class, 'create'])->name('entries.create');
        Route::post('entries', [ExpenseController::class, 'store'])->name('entries.store');
    });
    Route::middleware('permission:expenses.view')->group(function () {
        Route::get('entries/{entry}', [ExpenseController::class, 'show'])->name('entries.show');
    });
});
