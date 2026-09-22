<?php

use App\Http\Controllers\Contacts\CustomerController;
use App\Http\Controllers\Contacts\VendorController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('customers', CustomerController::class);
    Route::resource('vendors', VendorController::class);
});
