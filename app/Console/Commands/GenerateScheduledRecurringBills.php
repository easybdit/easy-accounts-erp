<?php

namespace App\Console\Commands;

use App\Actions\Purchases\GenerateBillFromRecurring;
use App\Models\Purchases\RecurringBill;
use Illuminate\Console\Command;

/**
 * Scheduled daily in routes/console.php. Only templates with
 * next_generation_date set (and due) are picked up — a template left with
 * next_generation_date null stays manual-only ("Generate Now"). Always
 * produces a DRAFT bill; see RecurringBill's docblock for why this never
 * auto-posts. Mirrors GenerateScheduledRecurringInvoices exactly.
 */
class GenerateScheduledRecurringBills extends Command
{
    protected $signature = 'bills:generate-recurring';

    protected $description = 'Generate a draft bill for every recurring bill template due for auto-generation';

    public function handle(GenerateBillFromRecurring $action): int
    {
        $templates = RecurringBill::query()
            ->where('is_active', true)
            ->whereNotNull('next_generation_date')
            ->whereDate('next_generation_date', '<=', now()->toDateString())
            ->get();

        foreach ($templates as $template) {
            $bill = $action->handle($template, $template->created_by);

            // Anchored to the previous due date (not "today"), so a delayed
            // run doesn't drift the billing day forward.
            $template->update(['next_generation_date' => $template->nextGenerationDateAfterAdvance()]);

            $this->info("Generated {$bill->bill_number} from \"{$template->name}\".");
        }

        $this->info("Done — {$templates->count()} bill(s) generated.");

        return self::SUCCESS;
    }
}
