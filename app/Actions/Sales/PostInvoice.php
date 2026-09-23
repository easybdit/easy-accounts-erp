<?php

namespace App\Actions\Sales;

use App\Actions\Accounting\PostJournal;
use App\Models\Inventory\StockMovement;
use App\Models\Sales\Invoice;
use App\Models\Sales\InvoiceItem;
use App\Models\Sales\RevenueRecognitionSchedule;
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
 *
 * Inventory integration (Section 32): a line tagged to an inventory-tracked
 * product also records a StockMovement (quantity out) and, if that stock
 * carries a non-zero cost, a companion COGS journal — debit COGS, credit the
 * product's inventory asset account — kept separate from the revenue journal
 * above so both stay independently traceable via their own source_type.
 *
 * Deferred Revenue (Section 90): a line marked is_deferred credits its
 * deferred_revenue_account_id (a liability) instead of its income account —
 * the revenue simply isn't recognized yet — and gets a
 * RevenueRecognitionSchedule that RecognizeRevenue walks monthly,
 * eventually crediting the income account the line "would have" used.
 * Tax is still credited to the tax account immediately regardless of
 * deferral — VAT is typically due on the invoice date, not the revenue
 * recognition date.
 */
class PostInvoice
{
    public function __construct(private PostJournal $postJournal) {}

    public function handle(Invoice $invoice): Invoice
    {
        if (! $invoice->isDraft()) {
            throw new RuntimeException('Only a draft invoice can be posted.');
        }

        $invoice->loadMissing('items.taxRate', 'items.taxRate2', 'items.product');

        if ($invoice->items->isEmpty()) {
            throw new RuntimeException('An invoice must have at least one item before it can be posted.');
        }

        return DB::transaction(function () use ($invoice) {
            $subtotal = '0.0000';
            $discountTotal = '0.0000';
            $taxTotal = '0.0000';
            $taxByAccount = [];

            foreach ($invoice->items as $item) {
                $subtotal = bcadd($subtotal, bcmul((string) $item->quantity, (string) $item->unit_price, 4), 4);
                $discountTotal = bcadd($discountTotal, (string) $item->discount, 4);

                // Self-healing, same as subtotal/discount/total below: never
                // trust a cached tax_amount, recompute fresh from the tax
                // rate(s) at posting time. The optional second tax rate is
                // computed independently on the same net line_total, not
                // compounded on top of the first (Section 33).
                $taxAmount = $item->taxRate ? $item->taxRate->calculate((string) $item->line_total) : '0.0000';
                $taxAmount2 = $item->taxRate2 ? $item->taxRate2->calculate((string) $item->line_total) : '0.0000';
                $item->update(['tax_amount' => $taxAmount, 'tax_amount_2' => $taxAmount2]);
                $taxTotal = bcadd($taxTotal, bcadd($taxAmount, $taxAmount2, 4), 4);

                if ($item->taxRate && bccomp($taxAmount, '0', 4) > 0) {
                    $accountId = $item->taxRate->tax_account_id;
                    $taxByAccount[$accountId] = bcadd($taxByAccount[$accountId] ?? '0.0000', $taxAmount, 4);
                }

                if ($item->taxRate2 && bccomp($taxAmount2, '0', 4) > 0) {
                    $accountId = $item->taxRate2->tax_account_id;
                    $taxByAccount[$accountId] = bcadd($taxByAccount[$accountId] ?? '0.0000', $taxAmount2, 4);
                }
            }

            $total = bcadd(
                $invoice->items->reduce(fn (string $carry, $item) => bcadd($carry, (string) $item->line_total, 4), '0.0000'),
                $taxTotal,
                4
            );

            // Self-healing: whatever gets posted to accounting also becomes the
            // invoice's authoritative stored total (Section 80 — the two must
            // never diverge), regardless of what was cached before posting.
            $invoice->update([
                'subtotal' => $subtotal,
                'discount_total' => $discountTotal,
                'tax_total' => $taxTotal,
                'total' => $total,
            ]);

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
                    'account_id' => $item->is_deferred ? $item->deferred_revenue_account_id : $item->account_id,
                    'debit' => 0,
                    'credit' => (string) $item->line_total,
                    'description' => $item->description,
                ];
            }

            foreach ($taxByAccount as $accountId => $amount) {
                $lines[] = [
                    'account_id' => $accountId,
                    'debit' => 0,
                    'credit' => $amount,
                    'description' => "Tax collected — Invoice {$invoice->invoice_number}",
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

            foreach ($invoice->items as $item) {
                if ($item->product && $item->product->isInventoryTracked()) {
                    $this->recordSaleStockMovement($invoice, $item);
                }

                if ($item->is_deferred) {
                    $this->createRecognitionSchedule($invoice, $item);
                }
            }

            return $invoice->fresh(['items', 'journal']);
        });
    }

    private function createRecognitionSchedule(Invoice $invoice, InvoiceItem $item): void
    {
        RevenueRecognitionSchedule::create([
            'invoice_item_id' => $item->id,
            'deferred_revenue_account_id' => $item->deferred_revenue_account_id,
            'income_account_id' => $item->account_id,
            'total_amount' => $item->line_total,
            'months_total' => $item->deferred_months,
            'months_recognized' => 0,
            'next_period_date' => $invoice->invoice_date->copy()->startOfMonth(),
            'status' => 'active',
            'created_by' => $invoice->created_by,
        ]);
    }

    private function recordSaleStockMovement(Invoice $invoice, InvoiceItem $item): void
    {
        $product = $item->product;

        $movement = $product->stockMovements()->create([
            'date' => $invoice->invoice_date->toDateString(),
            'quantity' => bcmul((string) $item->quantity, '-1', 4),
            'reason' => 'sale',
            'reference' => $invoice->invoice_number,
            'created_by' => $invoice->created_by,
        ]);

        $cost = bcmul((string) $item->quantity, (string) $product->purchase_price, 4);

        if (bccomp($cost, '0', 4) > 0) {
            $this->postJournal->handle([
                'date' => $invoice->invoice_date->toDateString(),
                'reference' => $invoice->invoice_number,
                'description' => "COGS for Invoice {$invoice->invoice_number} — {$product->sku}",
                'created_by' => $invoice->created_by,
                'source_type' => StockMovement::class,
                'source_id' => $movement->id,
                'lines' => [
                    ['account_id' => $product->cogs_account_id, 'debit' => $cost, 'credit' => 0, 'description' => "COGS — {$product->name}"],
                    ['account_id' => $product->inventory_account_id, 'debit' => 0, 'credit' => $cost, 'description' => "COGS — {$product->name}"],
                ],
            ]);
        }
    }
}
