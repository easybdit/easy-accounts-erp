<?php

namespace App\Actions\Sales;

use App\Models\Sales\CreditNote;
use App\Models\Tax\TaxRate;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Creates or updates a DRAFT credit note and its items, mirroring
 * SaveInvoiceDraft exactly (Section 45: recompute totals from submitted
 * items server-side, never trust the frontend).
 */
class SaveCreditNoteDraft
{
    public function handle(array $data, ?CreditNote $creditNote = null): CreditNote
    {
        if ($creditNote && ! $creditNote->isDraft()) {
            throw new RuntimeException('A posted credit note cannot be edited.');
        }

        $items = $data['items'];

        $subtotal = '0.0000';
        $discountTotal = '0.0000';
        $taxTotal = '0.0000';
        $lineTotals = [];
        $taxAmounts = [];

        $taxRates = TaxRate::whereIn('id', array_filter(array_column($items, 'tax_rate_id')))->get()->keyBy('id');

        foreach ($items as $item) {
            $quantity = (string) $item['quantity'];
            $unitPrice = (string) $item['unit_price'];
            $discount = (string) ($item['discount'] ?? 0);

            $gross = bcmul($quantity, $unitPrice, 4);
            $lineTotal = bcsub($gross, $discount, 4);

            $taxRate = $taxRates->get($item['tax_rate_id'] ?? null);
            $taxAmount = $taxRate ? $taxRate->calculate($lineTotal) : '0.0000';

            $subtotal = bcadd($subtotal, $gross, 4);
            $discountTotal = bcadd($discountTotal, $discount, 4);
            $taxTotal = bcadd($taxTotal, $taxAmount, 4);
            $lineTotals[] = $lineTotal;
            $taxAmounts[] = $taxAmount;
        }

        $total = bcadd(bcsub($subtotal, $discountTotal, 4), $taxTotal, 4);

        return DB::transaction(function () use ($data, $items, $lineTotals, $taxAmounts, $subtotal, $discountTotal, $taxTotal, $total, $creditNote) {
            $creditNote = $creditNote
                ? tap($creditNote)->update([
                    'customer_id' => $data['customer_id'],
                    'receivable_account_id' => $data['receivable_account_id'],
                    'invoice_id' => $data['invoice_id'] ?? null,
                    'credit_note_date' => $data['credit_note_date'],
                    'notes' => $data['notes'] ?? null,
                    'subtotal' => $subtotal,
                    'discount_total' => $discountTotal,
                    'tax_total' => $taxTotal,
                    'total' => $total,
                ])
                : CreditNote::create([
                    'credit_note_number' => $this->nextCreditNoteNumber($data['credit_note_date']),
                    'customer_id' => $data['customer_id'],
                    'receivable_account_id' => $data['receivable_account_id'],
                    'invoice_id' => $data['invoice_id'] ?? null,
                    'credit_note_date' => $data['credit_note_date'],
                    'status' => 'draft',
                    'notes' => $data['notes'] ?? null,
                    'subtotal' => $subtotal,
                    'discount_total' => $discountTotal,
                    'tax_total' => $taxTotal,
                    'total' => $total,
                    'created_by' => $data['created_by'] ?? null,
                ]);

            $creditNote->items()->delete();

            foreach ($items as $index => $item) {
                $creditNote->items()->create([
                    'account_id' => $item['account_id'],
                    'tax_rate_id' => $item['tax_rate_id'] ?? null,
                    'description' => $item['description'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'discount' => $item['discount'] ?? 0,
                    'line_total' => $lineTotals[$index],
                    'tax_amount' => $taxAmounts[$index],
                ]);
            }

            return $creditNote->load('items');
        });
    }

    private function nextCreditNoteNumber(string $date): string
    {
        $year = date('Y', strtotime($date));
        $count = CreditNote::where('credit_note_number', 'like', "CN-{$year}-%")->count() + 1;

        return sprintf('CN-%s-%04d', $year, $count);
    }
}
