<?php

namespace App\Actions\Sales;

use App\Models\Sales\Invoice;
use App\Models\Sales\RecurringInvoice;
use App\Models\Sales\RecurringInvoiceItem;

/**
 * Turns a recurring invoice template into a new DRAFT invoice, dated today.
 * Reuses SaveInvoiceDraft so numbering and totals are computed exactly the
 * same way as a manually-created invoice — a generated invoice is not
 * special-cased or auto-posted, it still goes through the normal
 * draft -> review -> post lifecycle (Section 20).
 */
class GenerateInvoiceFromRecurring
{
    public function __construct(private SaveInvoiceDraft $saveInvoiceDraft) {}

    public function handle(RecurringInvoice $template, ?int $userId): Invoice
    {
        $template->loadMissing('items');

        return $this->saveInvoiceDraft->handle([
            'customer_id' => $template->customer_id,
            'receivable_account_id' => $template->receivable_account_id,
            'invoice_date' => now()->toDateString(),
            'due_date' => null,
            'notes' => $template->notes,
            'created_by' => $userId,
            'items' => $template->items->map(fn (RecurringInvoiceItem $item) => [
                'account_id' => $item->account_id,
                'tax_rate_id' => $item->tax_rate_id,
                'description' => $item->description,
                'quantity' => $item->quantity,
                'unit_price' => $item->unit_price,
                'discount' => $item->discount,
            ])->all(),
        ]);
    }
}
