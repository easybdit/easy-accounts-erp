<?php

namespace App\Console\Commands;

use App\Actions\Sales\RecognizeRevenue;
use App\Models\Sales\RevenueRecognitionSchedule;
use Illuminate\Console\Command;

/**
 * Scheduled monthly in routes/console.php, same cadence as
 * assets:post-depreciation. Each schedule tracks its own next_period_date
 * and advances it after every recognized period, so re-running this for an
 * already-processed period is a no-op (nothing matches the whereDate
 * filter) rather than needing a separate idempotency guard.
 */
class RecognizeDeferredRevenue extends Command
{
    protected $signature = 'revenue:recognize';

    protected $description = 'Recognize one period of revenue for every active deferred revenue schedule that is due';

    public function handle(RecognizeRevenue $action): int
    {
        $schedules = RevenueRecognitionSchedule::query()
            ->where('status', 'active')
            ->whereNotNull('next_period_date')
            ->whereDate('next_period_date', '<=', now()->toDateString())
            ->get();

        foreach ($schedules as $schedule) {
            $action->handle($schedule);
        }

        $this->info("Done — {$schedules->count()} period(s) recognized.");

        return self::SUCCESS;
    }
}
