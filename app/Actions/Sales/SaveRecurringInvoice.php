<?php

namespace App\Actions\Sales;

use App\Models\Sales\RecurringInvoice;
use Illuminate\Support\Facades\DB;

/**
 * Creates or updates a recurring invoice template and its line items. No
 * totals are computed or stored here — a template is just a reusable set of
 * line items; SaveInvoiceDraft recomputes real totals at generation time,
 * the same as any other invoice (Section 45).
 */
class SaveRecurringInvoice
{
    public function handle(array $data, ?RecurringInvoice $template = null): RecurringInvoice
    {
        return DB::transaction(function () use ($data, $template) {
            $template = $template
                ? tap($template)->update([
                    'name' => $data['name'],
                    'customer_id' => $data['customer_id'],
                    'receivable_account_id' => $data['receivable_account_id'],
                    'notes' => $data['notes'] ?? null,
                    'is_active' => $data['is_active'] ?? true,
                    'next_generation_date' => $data['next_generation_date'] ?? null,
                ])
                : RecurringInvoice::create([
                    'name' => $data['name'],
                    'customer_id' => $data['customer_id'],
                    'receivable_account_id' => $data['receivable_account_id'],
                    'notes' => $data['notes'] ?? null,
                    'is_active' => $data['is_active'] ?? true,
                    'next_generation_date' => $data['next_generation_date'] ?? null,
                    'created_by' => $data['created_by'] ?? null,
                ]);

            $template->items()->delete();

            foreach ($data['items'] as $item) {
                $template->items()->create([
                    'account_id' => $item['account_id'],
                    'tax_rate_id' => $item['tax_rate_id'] ?? null,
                    'description' => $item['description'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'discount' => $item['discount'] ?? 0,
                ]);
            }

            return $template->load('items');
        });
    }
}
