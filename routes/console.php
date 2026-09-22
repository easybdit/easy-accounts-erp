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
