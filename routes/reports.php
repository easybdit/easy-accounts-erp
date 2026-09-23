<?php

use App\Http\Controllers\Reports\ReportController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'permission:reports.view'])->prefix('reports')->name('reports.')->group(function () {
    Route::get('/', [ReportController::class, 'index'])->name('index');
    Route::get('profit-and-loss', [ReportController::class, 'profitAndLoss'])->name('profit-and-loss');
    Route::get('balance-sheet', [ReportController::class, 'balanceSheet'])->name('balance-sheet');
    Route::get('ar-aging', [ReportController::class, 'arAging'])->name('ar-aging');
    Route::get('ap-aging', [ReportController::class, 'apAging'])->name('ap-aging');
    Route::get('cash-flow', [ReportController::class, 'cashFlow'])->name('cash-flow');
    Route::get('cash-flow-statement', [ReportController::class, 'cashFlowStatement'])->name('cash-flow-statement');
    Route::get('sales', [ReportController::class, 'sales'])->name('sales');
    Route::get('purchases', [ReportController::class, 'purchases'])->name('purchases');
    Route::get('expenses', [ReportController::class, 'expenses'])->name('expenses');
    Route::get('customer-balances', [ReportController::class, 'customerBalances'])->name('customer-balances');
    Route::get('customer-statement', [ReportController::class, 'customerStatement'])->name('customer-statement');
    Route::get('customer-statement/pdf', [ReportController::class, 'customerStatementPdf'])->name('customer-statement.pdf');
    Route::get('vendor-balances', [ReportController::class, 'vendorBalances'])->name('vendor-balances');
    Route::get('payments', [ReportController::class, 'payments'])->name('payments');
    Route::get('inventory', [ReportController::class, 'inventory'])->name('inventory');
});
