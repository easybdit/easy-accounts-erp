<?php

use App\Http\Controllers\Inventory\ProductCategoryController;
use App\Http\Controllers\Inventory\ProductController;
use App\Http\Controllers\Inventory\StockMovementController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->prefix('inventory')->name('inventory.')->group(function () {
    Route::resource('categories', ProductCategoryController::class)->except(['show']);
    Route::resource('products', ProductController::class);

    // Stock movements are posted immediately on creation (Section 20), same
    // as Journals/Payments/Expenses/Transfers: no edit/update/destroy routes.
    Route::resource('stock-movements', StockMovementController::class)->only(['index', 'create', 'store', 'show']);
});
