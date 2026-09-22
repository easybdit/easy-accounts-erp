<?php

namespace App\Actions\Sales;

use App\Models\Sales\Estimate;
use App\Models\Sales\EstimateItem;
use App\Models\Sales\Invoice;
use RuntimeException;

/**
 * Turns an Estimate into a real draft Invoice via SaveInvoiceDraft — the
 * only point an Estimate has any financial effect, and even then only once
 * that invoice is itself posted (Section 20). The Estimate is then locked
 * (status -> converted) and linked to the invoice it produced.
 */
class ConvertEstimateToInvoice
{
    public function __construct(private SaveInvoiceDraft $saveInvoiceDraft) {}

    public function handle(Estimate $estimate, ?int $userId): Invoice
    {
        if ($estimate->isConverted()) {
            throw new RuntimeException('This estimate has already been converted to an invoice.');
        }

        $estimate->loadMissing('items');

        $invoice = $this->saveInvoiceDraft->handle([
            'customer_id' => $estimate->customer_id,
            'receivable_account_id' => $estimate->receivable_account_id,
            'invoice_date' => now()->toDateString(),
            'due_date' => null,
            'notes' => $estimate->notes,
            'created_by' => $userId,
            'items' => $estimate->items->map(fn (EstimateItem $item) => [
                'account_id' => $item->account_id,
                'tax_rate_id' => $item->tax_rate_id,
                'description' => $item->description,
                'quantity' => $item->quantity,
                'unit_price' => $item->unit_price,
                'discount' => $item->discount,
            ])->all(),
        ]);

        $estimate->update(['status' => 'converted', 'converted_invoice_id' => $invoice->id]);

        return $invoice;
    }
}
