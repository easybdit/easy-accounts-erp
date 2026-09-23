<?php

namespace App\Console\Commands;

use App\Actions\Sales\RecognizeRevenue;
use App\Models\Sales\RevenueRecognitionSchedule;
use Illuminate\Console\Command;
use RuntimeException;

/**
 * Scheduled monthly in routes/console.php, same cadence as
 * assets:post-depreciation. Each schedule tracks its own next_period_date
 * and advances it after every recognized period, so re-running this for an
 * already-processed period is a no-op (nothing matches the whereDate
 * filter) rather than needing a separate idempotency guard. Each schedule
 * is handled independently (mirrors assets:post-depreciation) so one
 * schedule failing — e.g. its period falling in a locked accounting
 * period — doesn't stop the rest from recognizing.
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

        $recognized = 0;
        $skipped = 0;

        foreach ($schedules as $schedule) {
            try {
                $action->handle($schedule);
                $recognized++;
            } catch (RuntimeException $e) {
                $skipped++;
            }
        }

        $this->info("Done — {$recognized} period(s) recognized, {$skipped} skipped.");

        return self::SUCCESS;
    }
}
