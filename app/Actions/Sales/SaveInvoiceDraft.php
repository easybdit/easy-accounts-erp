<?php

namespace App\Actions\Sales;

use App\Models\Sales\Invoice;
use App\Models\Tax\TaxRate;
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
        $taxInclusive = $data['tax_inclusive'] ?? false;

        $subtotal = '0.0000';
        $discountTotal = '0.0000';
        $taxTotal = '0.0000';
        $lineTotals = [];
        $taxAmounts = [];
        $taxAmounts2 = [];

        $taxRateIds = array_filter(array_merge(array_column($items, 'tax_rate_id'), array_column($items, 'tax_rate_2_id')));
        $taxRates = TaxRate::whereIn('id', $taxRateIds)->get()->keyBy('id');

        foreach ($items as $item) {
            $quantity = (string) $item['quantity'];
            $unitPrice = (string) $item['unit_price'];
            $discount = (string) ($item['discount'] ?? 0);

            $gross = bcmul($quantity, $unitPrice, 4);
            $grossAfterDiscount = bcsub($gross, $discount, 4);
            $taxRate = $taxRates->get($item['tax_rate_id'] ?? null);
            $taxRate2 = $taxRates->get($item['tax_rate_2_id'] ?? null);

            // Tax Inclusive (Section 33): the entered unit price already
            // contains tax, so the net line_total is backed out of the
            // gross rather than the exclusive default (tax added on top).
            // Downstream (posting, totals) always treats line_total as a
            // plain net amount regardless of which branch produced it.
            //
            // With two independent (non-compounding) tax rates, the net is
            // backed out using their COMBINED rate — each tax is then just
            // its own share of that same net, so tax1 + tax2 still equals
            // gross - net exactly. This collapses to the single-tax formula
            // above whenever only one rate is set.
            if ($taxInclusive && ($taxRate || $taxRate2)) {
                $combinedRate = bcadd((string) ($taxRate->rate ?? '0'), (string) ($taxRate2->rate ?? '0'), 6);
                $divisor = bcadd('100', $combinedRate, 6);
                $lineTotal = bcdiv(bcmul($grossAfterDiscount, '100', 6), $divisor, 4);
                $taxAmount = $taxRate ? $taxRate->calculate($lineTotal) : '0.0000';
                $taxAmount2 = $taxRate2 ? $taxRate2->calculate($lineTotal) : '0.0000';
            } else {
                $lineTotal = $grossAfterDiscount;
                $taxAmount = $taxRate ? $taxRate->calculate($lineTotal) : '0.0000';
                $taxAmount2 = $taxRate2 ? $taxRate2->calculate($lineTotal) : '0.0000';
            }

            $subtotal = bcadd($subtotal, $gross, 4);
            $discountTotal = bcadd($discountTotal, $discount, 4);
            $taxTotal = bcadd($taxTotal, bcadd($taxAmount, $taxAmount2, 4), 4);
            $lineTotals[] = $lineTotal;
            $taxAmounts[] = $taxAmount;
            $taxAmounts2[] = $taxAmount2;
        }

        // sum(line_total) + taxTotal, not subtotal - discountTotal + taxTotal:
        // the two coincide in exclusive mode but only this form stays correct
        // in inclusive mode, where line_total already excludes tax.
        $total = bcadd(array_reduce($lineTotals, fn (string $carry, string $lineTotal) => bcadd($carry, $lineTotal, 4), '0.0000'), $taxTotal, 4);

        return DB::transaction(function () use ($data, $items, $lineTotals, $taxAmounts, $taxAmounts2, $subtotal, $discountTotal, $taxTotal, $total, $taxInclusive, $invoice) {
            $invoice = $invoice
                ? tap($invoice)->update([
                    'customer_id' => $data['customer_id'],
                    'receivable_account_id' => $data['receivable_account_id'],
                    'invoice_date' => $data['invoice_date'],
                    'due_date' => $data['due_date'] ?? null,
                    'tax_inclusive' => $taxInclusive,
                    'notes' => $data['notes'] ?? null,
                    'subtotal' => $subtotal,
                    'discount_total' => $discountTotal,
                    'tax_total' => $taxTotal,
                    'total' => $total,
                ])
                : Invoice::create([
                    'invoice_number' => $this->nextInvoiceNumber($data['invoice_date']),
                    'customer_id' => $data['customer_id'],
                    'recurring_invoice_id' => $data['recurring_invoice_id'] ?? null,
                    'receivable_account_id' => $data['receivable_account_id'],
                    'invoice_date' => $data['invoice_date'],
                    'due_date' => $data['due_date'] ?? null,
                    'tax_inclusive' => $taxInclusive,
                    'notes' => $data['notes'] ?? null,
                    'status' => 'draft',
                    'subtotal' => $subtotal,
                    'discount_total' => $discountTotal,
                    'tax_total' => $taxTotal,
                    'total' => $total,
                    'created_by' => $data['created_by'] ?? null,
                ]);

            $invoice->items()->delete();

            foreach ($items as $index => $item) {
                $invoice->items()->create([
                    'product_id' => $item['product_id'] ?? null,
                    'account_id' => $item['account_id'],
                    'tax_rate_id' => $item['tax_rate_id'] ?? null,
                    'tax_rate_2_id' => $item['tax_rate_2_id'] ?? null,
                    'description' => $item['description'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'discount' => $item['discount'] ?? 0,
                    'line_total' => $lineTotals[$index],
                    'tax_amount' => $taxAmounts[$index],
                    'tax_amount_2' => $taxAmounts2[$index],
                    'is_deferred' => $item['is_deferred'] ?? false,
                    'deferred_months' => $item['is_deferred'] ?? false ? $item['deferred_months'] : null,
                    'deferred_revenue_account_id' => $item['is_deferred'] ?? false ? $item['deferred_revenue_account_id'] : null,
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
