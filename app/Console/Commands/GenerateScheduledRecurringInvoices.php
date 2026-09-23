<?php

namespace App\Console\Commands;

use App\Actions\Sales\GenerateInvoiceFromRecurring;
use App\Models\Sales\RecurringInvoice;
use Illuminate\Console\Command;

/**
 * Scheduled daily in routes/console.php. Only templates with
 * next_generation_date set (and due) are picked up — a template left with
 * next_generation_date null stays manual-only ("Generate Now"), unchanged
 * from before this command existed. Always produces a DRAFT invoice; see
 * RecurringInvoice's docblock for why this never auto-posts.
 */
class GenerateScheduledRecurringInvoices extends Command
{
    protected $signature = 'invoices:generate-recurring';

    protected $description = 'Generate a draft invoice for every recurring invoice template due for auto-generation';

    public function handle(GenerateInvoiceFromRecurring $action): int
    {
        $templates = RecurringInvoice::query()
            ->where('is_active', true)
            ->whereNotNull('next_generation_date')
            ->whereDate('next_generation_date', '<=', now()->toDateString())
            ->get();

        foreach ($templates as $template) {
            $invoice = $action->handle($template, $template->created_by);

            // Anchored to the previous due date (not "today"), so a delayed
            // run doesn't drift the billing day forward.
            $template->update(['next_generation_date' => $template->nextGenerationDateAfterAdvance()]);

            $this->info("Generated {$invoice->invoice_number} from \"{$template->name}\".");
        }

        $this->info("Done — {$templates->count()} invoice(s) generated.");

        return self::SUCCESS;
    }
}
