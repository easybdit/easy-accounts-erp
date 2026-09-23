<?php

namespace App\Console\Commands;

use App\Actions\Sales\SendOverdueInvoiceReminder;
use App\Models\Sales\Invoice;
use Illuminate\Console\Command;
use RuntimeException;

/**
 * Scheduled daily in routes/console.php. Reminds once when an invoice
 * first goes overdue, then at most once every 7 days after that — not
 * every single day, which would just train customers to ignore the
 * reminders. Each invoice is handled independently (mirrors
 * assets:post-depreciation) so one failure (e.g. no email on file)
 * doesn't stop the rest from being reminded.
 */
class SendOverdueInvoiceReminders extends Command
{
    protected $signature = 'invoices:send-overdue-reminders';

    protected $description = 'Email a payment reminder for every posted invoice that is overdue and due for a reminder';

    private const REMINDER_INTERVAL_DAYS = 7;

    public function handle(SendOverdueInvoiceReminder $action): int
    {
        $invoices = Invoice::query()
            ->where('status', 'posted')
            ->whereNotNull('due_date')
            ->whereDate('due_date', '<', now()->toDateString())
            ->where(function ($query) {
                $query->whereNull('last_reminder_sent_at')
                    ->orWhereDate('last_reminder_sent_at', '<=', now()->subDays(self::REMINDER_INTERVAL_DAYS)->toDateString());
            })
            ->get()
            ->filter(fn (Invoice $invoice) => $invoice->isOverdue());

        $reminded = 0;
        $skipped = 0;

        foreach ($invoices as $invoice) {
            try {
                $action->handle($invoice);
                $reminded++;
            } catch (RuntimeException $e) {
                $skipped++;
            }
        }

        $this->info("Done — {$reminded} reminder(s) sent, {$skipped} skipped.");

        return self::SUCCESS;
    }
}
