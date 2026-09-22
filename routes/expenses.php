<?php

use App\Http\Controllers\Expenses\ExpenseCategoryController;
use App\Http\Controllers\Expenses\ExpenseController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->prefix('expenses')->name('expenses.')->group(function () {
    Route::resource('categories', ExpenseCategoryController::class)->except(['show']);

    // Expenses are posted immediately on creation (Section 20), same as
    // Journals and Payments: no edit/update/destroy routes.
    Route::resource('entries', ExpenseController::class)->only(['index', 'create', 'store', 'show']);
});
