<?php

use App\Http\Controllers\Inventory\LowStockAlertController;
use App\Http\Controllers\Inventory\ProductCategoryController;
use App\Http\Controllers\Inventory\ProductController;
use App\Http\Controllers\Inventory\StockMovementController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->prefix('inventory')->name('inventory.')->group(function () {
    Route::middleware('permission:inventory.view')->group(function () {
        Route::get('categories', [ProductCategoryController::class, 'index'])->name('categories.index');
    });
    Route::middleware('permission:inventory.manage')->group(function () {
        Route::get('categories/create', [ProductCategoryController::class, 'create'])->name('categories.create');
        Route::post('categories', [ProductCategoryController::class, 'store'])->name('categories.store');
        Route::get('categories/{category}/edit', [ProductCategoryController::class, 'edit'])->name('categories.edit');
        Route::put('categories/{category}', [ProductCategoryController::class, 'update'])->name('categories.update');
        Route::delete('categories/{category}', [ProductCategoryController::class, 'destroy'])->name('categories.destroy');
    });

    // Route order matters: static "create" paths must be registered before
    // wildcard "{product}"/"{stock_movement}" show routes.
    Route::middleware('permission:inventory.view')->group(function () {
        Route::get('products', [ProductController::class, 'index'])->name('products.index');
    });
    Route::middleware('permission:inventory.manage')->group(function () {
        Route::get('products/create', [ProductController::class, 'create'])->name('products.create');
        Route::post('products', [ProductController::class, 'store'])->name('products.store');
        Route::get('products/{product}/edit', [ProductController::class, 'edit'])->name('products.edit');
        Route::put('products/{product}', [ProductController::class, 'update'])->name('products.update');
        Route::delete('products/{product}', [ProductController::class, 'destroy'])->name('products.destroy');
    });
    Route::middleware('permission:inventory.view')->group(function () {
        Route::get('products/{product}', [ProductController::class, 'show'])->name('products.show');
    });

    // Stock movements are posted immediately on creation (Section 20), same
    // as Journals/Payments/Expenses/Transfers: no edit/update/destroy routes.
    Route::middleware('permission:inventory.view')->group(function () {
        Route::get('stock-movements', [StockMovementController::class, 'index'])->name('stock-movements.index');
    });
    Route::middleware('permission:inventory.manage')->group(function () {
        Route::get('stock-movements/create', [StockMovementController::class, 'create'])->name('stock-movements.create');
        Route::post('stock-movements', [StockMovementController::class, 'store'])->name('stock-movements.store');
    });
    Route::middleware('permission:inventory.view')->group(function () {
        Route::get('stock-movements/{stock_movement}', [StockMovementController::class, 'show'])->name('stock-movements.show');
    });

    Route::middleware('permission:inventory.manage')->group(function () {
        Route::post('low-stock-alert', [LowStockAlertController::class, 'store'])->name('low-stock-alert.store');
    });
});
