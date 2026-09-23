<?php

namespace App\Models\Concerns;

use Carbon\Carbon;
use InvalidArgumentException;

/**
 * Shared by RecurringInvoice, RecurringExpense, and RecurringBill: each
 * scheduled command previously hardcoded ->addMonthNoOverflow(), so every
 * template was implicitly monthly. This lets a template pick weekly,
 * monthly, quarterly, or yearly instead — all *NoOverflow so e.g. a
 * template due Jan 31 lands on Feb 28, not rolls into March (same
 * reasoning that was already applied to the monthly-only case).
 */
trait HasRecurrenceFrequency
{
    public const FREQUENCIES = ['weekly', 'monthly', 'quarterly', 'yearly'];

    public function nextGenerationDateAfterAdvance(): Carbon
    {
        return match ($this->frequency) {
            'weekly' => $this->next_generation_date->copy()->addWeek(),
            'monthly' => $this->next_generation_date->copy()->addMonthNoOverflow(),
            'quarterly' => $this->next_generation_date->copy()->addMonthsNoOverflow(3),
            'yearly' => $this->next_generation_date->copy()->addYearNoOverflow(),
            default => throw new InvalidArgumentException("Unknown recurrence frequency: {$this->frequency}"),
        };
    }
}
