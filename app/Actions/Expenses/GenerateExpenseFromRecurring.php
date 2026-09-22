<?php

namespace App\Actions\Expenses;

use App\Models\Expenses\Expense;
use App\Models\Expenses\RecurringExpense;

/**
 * Turns a recurring expense template into a new Expense, dated today.
 * Reuses RecordExpense so numbering, tax and posting are computed exactly
 * the same way as a manually-recorded expense — a generated expense is not
 * special-cased, it posts immediately just like any other (Expense has no
 * draft state, unlike Invoice's recurring counterpart).
 */
class GenerateExpenseFromRecurring
{
    public function __construct(private RecordExpense $recordExpense) {}

    public function handle(RecurringExpense $template, ?int $userId): Expense
    {
        return $this->recordExpense->handle([
            'expense_category_id' => $template->expense_category_id,
            'account_id' => $template->account_id,
            'payment_account_id' => $template->payment_account_id,
            'vendor_id' => $template->vendor_id,
            'payee' => $template->payee,
            'expense_date' => now()->toDateString(),
            'amount' => $template->amount,
            'tax_rate_id' => $template->tax_rate_id,
            'reference' => $template->reference,
            'notes' => $template->notes,
            'created_by' => $userId,
        ]);
    }
}
