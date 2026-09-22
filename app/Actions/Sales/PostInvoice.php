<?php

namespace App\Actions\Sales;

use App\Actions\Accounting\PostJournal;
use App\Models\Sales\Invoice;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * The Sales-side half of Section 15's posting diagram:
 *   Invoice -> Invoice Items -> Accounting Posting -> Journal
 *
 * Recomputes the total fresh from the invoice's current items (never trusts
 * a cached total) and posts a single balanced journal: debit the invoice's
 * receivable account (tagged to the customer) for the total, credit each
 * item's income account for its line total (Section 80: invoice total must
 * always equal the accounting posting).
 */
class PostInvoice
{
    public function __construct(private PostJournal $postJournal) {}

    public function handle(Invoice $invoice): Invoice
    {
        if (! $invoice->isDraft()) {
            throw new RuntimeException('Only a draft invoice can be posted.');
        }

        $invoice->loadMissing('items');

        if ($invoice->items->isEmpty()) {
            throw new RuntimeException('An invoice must have at least one item before it can be posted.');
        }

        return DB::transaction(function () use ($invoice) {
            $subtotal = '0.0000';
            $discountTotal = '0.0000';

            foreach ($invoice->items as $item) {
                $subtotal = bcadd($subtotal, bcmul((string) $item->quantity, (string) $item->unit_price, 4), 4);
                $discountTotal = bcadd($discountTotal, (string) $item->discount, 4);
            }

            $total = $invoice->items->reduce(
                fn (string $carry, $item) => bcadd($carry, (string) $item->line_total, 4),
                '0.0000'
            );

            // Self-healing: whatever gets posted to accounting also becomes the
            // invoice's authoritative stored total (Section 80 — the two must
            // never diverge), regardless of what was cached before posting.
            $invoice->update(['subtotal' => $subtotal, 'discount_total' => $discountTotal, 'total' => $total]);

            if (bccomp($total, '0', 4) <= 0) {
                throw new RuntimeException('An invoice with a zero or negative total cannot be posted.');
            }

            $lines = [
                [
                    'account_id' => $invoice->receivable_account_id,
                    'customer_id' => $invoice->customer_id,
                    'debit' => $total,
                    'credit' => 0,
                    'description' => "Invoice {$invoice->invoice_number}",
                ],
            ];

            foreach ($invoice->items as $item) {
                $lines[] = [
                    'account_id' => $item->account_id,
                    'debit' => 0,
                    'credit' => (string) $item->line_total,
                    'description' => $item->description,
                ];
            }

            $this->postJournal->handle([
                'date' => $invoice->invoice_date->toDateString(),
                'reference' => $invoice->invoice_number,
                'description' => "Invoice {$invoice->invoice_number}",
                'created_by' => $invoice->created_by,
                'source_type' => Invoice::class,
                'source_id' => $invoice->id,
                'lines' => $lines,
            ]);

            $invoice->update(['status' => 'posted', 'posted_at' => now()]);

            return $invoice->fresh(['items', 'journal']);
        });
    }
}
