<?php

use App\Http\Controllers\Accounting\AccountController;
use App\Http\Controllers\Accounting\AccountingSettingsController;
use App\Http\Controllers\Accounting\BudgetController;
use App\Http\Controllers\Accounting\FixedAssetController;
use App\Http\Controllers\Accounting\GeneralLedgerController;
use App\Http\Controllers\Accounting\JournalController;
use App\Http\Controllers\Accounting\TrialBalanceController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->prefix('accounting')->name('accounting.')->group(function () {
    Route::middleware('permission:accounts.view')->group(function () {
        Route::get('accounts', [AccountController::class, 'index'])->name('accounts.index');
        Route::get('ledger', [GeneralLedgerController::class, 'index'])->name('ledger.index');
        Route::get('trial-balance', [TrialBalanceController::class, 'index'])->name('trial-balance.index');
    });
    Route::middleware('permission:accounts.manage')->group(function () {
        Route::get('accounts/create', [AccountController::class, 'create'])->name('accounts.create');
        Route::post('accounts', [AccountController::class, 'store'])->name('accounts.store');
        Route::get('accounts/{account}/edit', [AccountController::class, 'edit'])->name('accounts.edit');
        Route::put('accounts/{account}', [AccountController::class, 'update'])->name('accounts.update');
        Route::delete('accounts/{account}', [AccountController::class, 'destroy'])->name('accounts.destroy');
    });

    // Posted journals are financially immutable (Section 20): still no
    // edit/destroy routes — voiding posts an equal-and-opposite reversing
    // journal instead (VoidJournal action), never edits or deletes the
    // original.
    //
    // Route order matters here: the static "journals/create" route must be
    // registered before the wildcard "journals/{journal}" show route, or
    // Laravel would match "/journals/create" as {journal} = "create" first.
    Route::middleware('permission:journal.view')->group(function () {
        Route::get('journals', [JournalController::class, 'index'])->name('journals.index');
    });
    Route::middleware('permission:journal.manage')->group(function () {
        Route::get('journals/create', [JournalController::class, 'create'])->name('journals.create');
        Route::post('journals', [JournalController::class, 'store'])->name('journals.store');
        Route::post('journals/{journal}/void', [JournalController::class, 'void'])->name('journals.void');
    });
    Route::middleware('permission:journal.view')->group(function () {
        Route::get('journals/{journal}', [JournalController::class, 'show'])->name('journals.show');
    });

    // Fixed Assets reuse accounts.* rather than a separate permission pair
    // (Section 90) — same reasoning as every other sub-feature this session.
    Route::middleware('permission:accounts.view')->group(function () {
        Route::get('fixed-assets', [FixedAssetController::class, 'index'])->name('fixed-assets.index');
    });
    Route::middleware('permission:accounts.manage')->group(function () {
        Route::get('fixed-assets/create', [FixedAssetController::class, 'create'])->name('fixed-assets.create');
        Route::post('fixed-assets', [FixedAssetController::class, 'store'])->name('fixed-assets.store');
        Route::get('fixed-assets/{fixed_asset}/edit', [FixedAssetController::class, 'edit'])->name('fixed-assets.edit');
        Route::put('fixed-assets/{fixed_asset}', [FixedAssetController::class, 'update'])->name('fixed-assets.update');
        Route::delete('fixed-assets/{fixed_asset}', [FixedAssetController::class, 'destroy'])->name('fixed-assets.destroy');
        Route::post('fixed-assets/{fixed_asset}/post-depreciation', [FixedAssetController::class, 'postDepreciation'])->name('fixed-assets.post-depreciation');
        Route::post('fixed-assets/{fixed_asset}/dispose', [FixedAssetController::class, 'dispose'])->name('fixed-assets.dispose');
    });
    Route::middleware('permission:accounts.view')->group(function () {
        Route::get('fixed-assets/{fixed_asset}', [FixedAssetController::class, 'show'])->name('fixed-assets.show');
    });

    // Period Lock: a singleton settings row, gated by its own settings.*
    // permission rather than accounts.* — closing the books is a distinct
    // responsibility from day-to-day chart-of-accounts maintenance.
    Route::middleware('permission:settings.view')->group(function () {
        Route::get('settings', [AccountingSettingsController::class, 'edit'])->name('settings.edit');
    });
    Route::middleware('permission:settings.manage')->group(function () {
        Route::put('settings', [AccountingSettingsController::class, 'update'])->name('settings.update');
        Route::post('settings/logo', [AccountingSettingsController::class, 'updateLogo'])->name('settings.logo.update');
        Route::delete('settings/logo', [AccountingSettingsController::class, 'destroyLogo'])->name('settings.logo.destroy');
        Route::put('settings/mail', [AccountingSettingsController::class, 'updateMail'])->name('settings.mail.update');
        Route::post('settings/mail/test', [AccountingSettingsController::class, 'sendTestMail'])->name('settings.mail.test');
        Route::put('settings/payment-gateway', [AccountingSettingsController::class, 'updatePaymentGateway'])->name('settings.payment-gateway.update');
        Route::put('settings/login-security', [AccountingSettingsController::class, 'updateLoginSecurity'])->name('settings.login-security.update');
        Route::put('settings/ip-whitelist-enabled', [AccountingSettingsController::class, 'updateIpWhitelistEnabled'])->name('settings.ip-whitelist-enabled.update');
    });

    // Budgets reuse accounts.* rather than a separate permission pair —
    // same reasoning as Fixed Assets above. Route order matters: "create"
    // must be registered before wildcard "{budget}".
    Route::middleware('permission:accounts.view')->group(function () {
        Route::get('budgets', [BudgetController::class, 'index'])->name('budgets.index');
    });
    Route::middleware('permission:accounts.manage')->group(function () {
        Route::get('budgets/create', [BudgetController::class, 'create'])->name('budgets.create');
        Route::post('budgets', [BudgetController::class, 'store'])->name('budgets.store');
        Route::get('budgets/{budget}/edit', [BudgetController::class, 'edit'])->name('budgets.edit');
        Route::put('budgets/{budget}', [BudgetController::class, 'update'])->name('budgets.update');
        Route::delete('budgets/{budget}', [BudgetController::class, 'destroy'])->name('budgets.destroy');
    });
});
