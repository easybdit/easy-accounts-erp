<?php

namespace App\Actions\Purchases;

use App\Models\Purchases\Bill;
use App\Models\Purchases\RecurringBill;
use App\Models\Purchases\RecurringBillItem;

/**
 * Turns a recurring bill template into a new DRAFT bill, dated today.
 * Reuses SaveBillDraft so numbering and totals are computed exactly the
 * same way as a manually-created bill — a generated bill is not
 * special-cased or auto-posted, it still goes through the normal
 * draft -> review -> post lifecycle. Mirrors
 * App\Actions\Sales\GenerateInvoiceFromRecurring.
 */
class GenerateBillFromRecurring
{
    public function __construct(private SaveBillDraft $saveBillDraft) {}

    public function handle(RecurringBill $template, ?int $userId): Bill
    {
        $template->loadMissing('items');

        return $this->saveBillDraft->handle([
            'vendor_id' => $template->vendor_id,
            'recurring_bill_id' => $template->id,
            'payable_account_id' => $template->payable_account_id,
            'bill_date' => now()->toDateString(),
            'due_date' => null,
            'notes' => $template->notes,
            'created_by' => $userId,
            'items' => $template->items->map(fn (RecurringBillItem $item) => [
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
