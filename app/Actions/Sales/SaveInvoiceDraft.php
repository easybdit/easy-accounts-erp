<?php

namespace App\Actions\Sales;

use App\Models\Sales\Invoice;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Creates or updates a DRAFT invoice and its items, always recomputing
 * subtotal/discount/total from the submitted items server-side (Section 45:
 * frontend totals are UI feedback only, never authoritative).
 */
class SaveInvoiceDraft
{
    public function handle(array $data, ?Invoice $invoice = null): Invoice
    {
        if ($invoice && ! $invoice->isDraft()) {
            throw new RuntimeException('A posted invoice cannot be edited.');
        }

        $items = $data['items'];

        $subtotal = '0.0000';
        $discountTotal = '0.0000';
        $lineTotals = [];

        foreach ($items as $item) {
            $quantity = (string) $item['quantity'];
            $unitPrice = (string) $item['unit_price'];
            $discount = (string) ($item['discount'] ?? 0);

            $gross = bcmul($quantity, $unitPrice, 4);
            $lineTotal = bcsub($gross, $discount, 4);

            $subtotal = bcadd($subtotal, $gross, 4);
            $discountTotal = bcadd($discountTotal, $discount, 4);
            $lineTotals[] = $lineTotal;
        }

        $total = bcsub($subtotal, $discountTotal, 4);

        return DB::transaction(function () use ($data, $items, $lineTotals, $subtotal, $discountTotal, $total, $invoice) {
            $invoice = $invoice
                ? tap($invoice)->update([
                    'customer_id' => $data['customer_id'],
                    'receivable_account_id' => $data['receivable_account_id'],
                    'invoice_date' => $data['invoice_date'],
                    'due_date' => $data['due_date'] ?? null,
                    'notes' => $data['notes'] ?? null,
                    'subtotal' => $subtotal,
                    'discount_total' => $discountTotal,
                    'total' => $total,
                ])
                : Invoice::create([
                    'invoice_number' => $this->nextInvoiceNumber($data['invoice_date']),
                    'customer_id' => $data['customer_id'],
                    'receivable_account_id' => $data['receivable_account_id'],
                    'invoice_date' => $data['invoice_date'],
                    'due_date' => $data['due_date'] ?? null,
                    'notes' => $data['notes'] ?? null,
                    'status' => 'draft',
                    'subtotal' => $subtotal,
                    'discount_total' => $discountTotal,
                    'total' => $total,
                    'created_by' => $data['created_by'] ?? null,
                ]);

            $invoice->items()->delete();

            foreach ($items as $index => $item) {
                $invoice->items()->create([
                    'account_id' => $item['account_id'],
                    'description' => $item['description'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'discount' => $item['discount'] ?? 0,
                    'line_total' => $lineTotals[$index],
                ]);
            }

            return $invoice->load('items');
        });
    }

    /**
     * Simple default numbering (Section 50): INV-{year}-{sequence}.
     * The unique constraint on invoice_number is the hard guarantee against
     * duplicates; a concurrency-safe sequence is a refinement for later,
     * once the exact numbering policy is confirmed.
     */
    private function nextInvoiceNumber(string $invoiceDate): string
    {
        $year = date('Y', strtotime($invoiceDate));
        $count = Invoice::where('invoice_number', 'like', "INV-{$year}-%")->count() + 1;

        return sprintf('INV-%s-%04d', $year, $count);
    }
}
