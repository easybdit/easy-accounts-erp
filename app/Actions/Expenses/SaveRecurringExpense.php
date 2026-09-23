<?php

namespace App\Actions\Expenses;

use App\Models\Expenses\RecurringExpense;

/**
 * Creates or updates a recurring expense template. Mirrors
 * App\Actions\Sales\SaveRecurringInvoice — just stores the template fields,
 * RecordExpense recomputes tax and posts fresh at generation time.
 */
class SaveRecurringExpense
{
    public function handle(array $data, ?RecurringExpense $template = null): RecurringExpense
    {
        $attributes = [
            'name' => $data['name'],
            'expense_category_id' => $data['expense_category_id'],
            'account_id' => $data['account_id'],
            'payment_account_id' => $data['payment_account_id'],
            'vendor_id' => $data['vendor_id'] ?? null,
            'payee' => $data['payee'],
            'amount' => $data['amount'],
            'tax_rate_id' => $data['tax_rate_id'] ?? null,
            'reference' => $data['reference'] ?? null,
            'notes' => $data['notes'] ?? null,
            'is_active' => $data['is_active'] ?? true,
            'next_generation_date' => $data['next_generation_date'] ?? null,
            'frequency' => $data['frequency'] ?? 'monthly',
        ];

        if ($template) {
            $template->update($attributes);

            return $template;
        }

        return RecurringExpense::create([
            ...$attributes,
            'created_by' => $data['created_by'] ?? null,
        ]);
    }
}
