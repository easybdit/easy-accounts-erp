<?php

namespace App\Console\Commands;

use App\Actions\Inventory\SendLowStockAlert;
use Illuminate\Console\Command;

/**
 * Scheduled daily in routes/console.php. Unlike overdue invoice reminders
 * (which must not spam a customer), this is an internal digest to staff —
 * sending it every day the situation persists is the expected behavior,
 * not spam, so there is no cooldown/dedup to track here.
 */
class SendLowStockAlerts extends Command
{
    protected $signature = 'inventory:send-low-stock-alerts';

    protected $description = 'Email every inventory manager a digest of products at or below their reorder level';

    public function handle(SendLowStockAlert $action): int
    {
        $count = $action->handle();

        $this->info($count > 0 ? "Alert sent for {$count} low-stock item(s)." : 'Nothing is low on stock.');

        return self::SUCCESS;
    }
}
