<?php

namespace App\Console\Commands;

use App\Actions\Expenses\GenerateExpenseFromRecurring;
use App\Models\Expenses\RecurringExpense;
use Illuminate\Console\Command;
use RuntimeException;

/**
 * Scheduled daily in routes/console.php. Only templates with
 * next_generation_date set (and due) are picked up — a template left with
 * next_generation_date null stays manual-only ("Generate Now"), unchanged
 * from before this command existed. Mirrors
 * GenerateScheduledRecurringInvoices; unlike that command, the generated
 * Expense posts immediately (Expense has no draft state), so — like
 * assets:post-depreciation — each template is handled independently and a
 * period lock blocking one template (e.g. its due date has drifted into a
 * newly-locked period) just skips that one rather than failing the run.
 */
class GenerateScheduledRecurringExpenses extends Command
{
    protected $signature = 'expenses:generate-recurring';

    protected $description = 'Record an expense for every recurring expense template due for auto-generation';

    public function handle(GenerateExpenseFromRecurring $action): int
    {
        $templates = RecurringExpense::query()
            ->where('is_active', true)
            ->whereNotNull('next_generation_date')
            ->whereDate('next_generation_date', '<=', now()->toDateString())
            ->get();

        $recorded = 0;
        $skipped = 0;

        foreach ($templates as $template) {
            try {
                $expense = $action->handle($template, $template->created_by);
            } catch (RuntimeException $e) {
                $skipped++;

                continue;
            }

            // Anchored to the previous due date (not "today"), so a delayed
            // run doesn't drift the billing day forward.
            $template->update(['next_generation_date' => $template->next_generation_date->addMonthNoOverflow()]);

            $this->info("Recorded {$expense->expense_number} from \"{$template->name}\".");
            $recorded++;
        }

        $this->info("Done — {$recorded} expense(s) recorded, {$skipped} skipped.");

        return self::SUCCESS;
    }
}
