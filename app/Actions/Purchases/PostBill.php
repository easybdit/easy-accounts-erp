<?php

namespace App\Actions\Purchases;

use App\Actions\Accounting\PostJournal;
use App\Models\Purchases\Bill;
use App\Models\Purchases\BillItem;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * The Purchases-side mirror of App\Actions\Sales\PostInvoice:
 *   Bill -> Bill Items -> Accounting Posting -> Journal
 *
 * Debit each item's expense account, credit the bill's payable account
 * (tagged to the vendor) for the total. Recomputes and persists the total
 * fresh from the current items at posting time (self-healing, Section 80).
 *
 * Inventory integration (Section 32): a line tagged to an inventory-tracked
 * product AND debiting that product's inventory account (rather than a
 * plain expense account) records a StockMovement (quantity in). No
 * companion journal is needed here — the bill's own journal above already
 * debits the inventory asset account for that line.
 */
class PostBill
{
    public function __construct(private PostJournal $postJournal) {}

    public function handle(Bill $bill): Bill
    {
        if (! $bill->isDraft()) {
            throw new RuntimeException('Only a draft bill can be posted.');
        }

        $bill->loadMissing('items.taxRate', 'items.product');

        if ($bill->items->isEmpty()) {
            throw new RuntimeException('A bill must have at least one item before it can be posted.');
        }

        return DB::transaction(function () use ($bill) {
            $subtotal = '0.0000';
            $discountTotal = '0.0000';
            $taxTotal = '0.0000';
            $taxByAccount = [];

            foreach ($bill->items as $item) {
                $subtotal = bcadd($subtotal, bcmul((string) $item->quantity, (string) $item->unit_price, 4), 4);
                $discountTotal = bcadd($discountTotal, (string) $item->discount, 4);

                $taxAmount = $item->taxRate ? $item->taxRate->calculate((string) $item->line_total) : '0.0000';
                $item->update(['tax_amount' => $taxAmount]);
                $taxTotal = bcadd($taxTotal, $taxAmount, 4);

                if ($item->taxRate && bccomp($taxAmount, '0', 4) > 0) {
                    $accountId = $item->taxRate->tax_account_id;
                    $taxByAccount[$accountId] = bcadd($taxByAccount[$accountId] ?? '0.0000', $taxAmount, 4);
                }
            }

            $total = bcadd(
                $bill->items->reduce(fn (string $carry, $item) => bcadd($carry, (string) $item->line_total, 4), '0.0000'),
                $taxTotal,
                4
            );

            $bill->update([
                'subtotal' => $subtotal,
                'discount_total' => $discountTotal,
                'tax_total' => $taxTotal,
                'total' => $total,
            ]);

            if (bccomp($total, '0', 4) <= 0) {
                throw new RuntimeException('A bill with a zero or negative total cannot be posted.');
            }

            $lines = [
                [
                    'account_id' => $bill->payable_account_id,
                    'vendor_id' => $bill->vendor_id,
                    'debit' => 0,
                    'credit' => $total,
                    'description' => "Bill {$bill->bill_number}",
                ],
            ];

            foreach ($bill->items as $item) {
                $lines[] = [
                    'account_id' => $item->account_id,
                    'debit' => (string) $item->line_total,
                    'credit' => 0,
                    'description' => $item->description,
                ];
            }

            // Debited (not credited): input tax paid on a purchase offsets
            // output tax collected on sales in the same tax_account_id,
            // rather than assuming separate input/output accounts — a
            // deliberate, jurisdiction-neutral simplification (Section 33).
            foreach ($taxByAccount as $accountId => $amount) {
                $lines[] = [
                    'account_id' => $accountId,
                    'debit' => $amount,
                    'credit' => 0,
                    'description' => "Tax paid — Bill {$bill->bill_number}",
                ];
            }

            $this->postJournal->handle([
                'date' => $bill->bill_date->toDateString(),
                'reference' => $bill->bill_number,
                'description' => "Bill {$bill->bill_number}",
                'created_by' => $bill->created_by,
                'source_type' => Bill::class,
                'source_id' => $bill->id,
                'lines' => $lines,
            ]);

            $bill->update(['status' => 'posted', 'posted_at' => now()]);

            foreach ($bill->items as $item) {
                if ($item->product && $item->product->isInventoryTracked() && $item->account_id === $item->product->inventory_account_id) {
                    $this->recordPurchaseStockMovement($bill, $item);
                }
            }

            return $bill->fresh(['items', 'journal']);
        });
    }

    private function recordPurchaseStockMovement(Bill $bill, BillItem $item): void
    {
        $item->product->stockMovements()->create([
            'date' => $bill->bill_date->toDateString(),
            'quantity' => $item->quantity,
            'reason' => 'purchase',
            'reference' => $bill->bill_number,
            'created_by' => $bill->created_by,
        ]);
    }
}
