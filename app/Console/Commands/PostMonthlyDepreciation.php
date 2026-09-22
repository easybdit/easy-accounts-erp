<?php

namespace App\Console\Commands;

use App\Actions\Accounting\PostDepreciation;
use App\Models\Accounting\FixedAsset;
use Illuminate\Console\Command;
use RuntimeException;

/**
 * Scheduled monthly in routes/console.php. Idempotent: PostDepreciation
 * refuses a period that's already been posted for a given asset, so a
 * re-run (or catch-up for a month the schedule missed) is safe.
 */
class PostMonthlyDepreciation extends Command
{
    protected $signature = 'assets:post-depreciation {--period=}';

    protected $description = 'Post one month of straight-line depreciation for every active fixed asset';

    public function handle(PostDepreciation $action): int
    {
        $period = $this->option('period') ?? now()->startOfMonth()->toDateString();

        $assets = FixedAsset::where('status', 'active')->get();
        $posted = 0;
        $skipped = 0;

        foreach ($assets as $asset) {
            try {
                $action->handle($asset, $period);
                $posted++;
            } catch (RuntimeException $e) {
                $skipped++;
            }
        }

        $this->info("Depreciation posted for {$posted} asset(s), skipped {$skipped} for period {$period}.");

        return self::SUCCESS;
    }
}
