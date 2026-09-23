<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Log retention policy (Section 90 Phase 11): prune activity_log rows older
// than config('activitylog.clean_after_days') — the audit trail otherwise
// grows unbounded. --force skips the production confirmation prompt since
// this runs unattended.
Schedule::command('activitylog:clean --force')->daily();

// Fixed Asset depreciation: post one month of straight-line depreciation
// for every active asset, on the 1st of each month. Idempotent — see
// PostMonthlyDepreciation's docblock.
Schedule::command('assets:post-depreciation')->monthlyOn(1, '01:00');

// Scheduled Recurring Invoicing: daily check for templates whose
// next_generation_date is due. Daily (not monthly) since each template
// carries its own due date — see GenerateScheduledRecurringInvoices.
Schedule::command('invoices:generate-recurring')->dailyAt('02:00');

// Deferred Revenue Recognition: monthly on the 1st, same cadence as
// depreciation. Each schedule tracks its own next_period_date.
Schedule::command('revenue:recognize')->monthlyOn(1, '01:30');

// Scheduled Recurring Expenses: daily check for templates whose
// next_generation_date is due — mirrors invoices:generate-recurring.
Schedule::command('expenses:generate-recurring')->dailyAt('02:15');

// Scheduled Recurring Bills: daily check for templates whose
// next_generation_date is due — mirrors invoices:generate-recurring
// exactly (always a draft, never posted, so no period-lock interaction).
Schedule::command('bills:generate-recurring')->dailyAt('02:30');
