<?php

namespace App\Actions\Purchases;

use App\Actions\Accounting\PostJournal;
use App\Models\Purchases\Bill;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * The Purchases-side mirror of App\Actions\Sales\PostInvoice:
 *   Bill -> Bill Items -> Accounting Posting -> Journal
 *
 * Debit each item's expense account, credit the bill's payable account
 * (tagged to the vendor) for the total. Recomputes and persists the total
 * fresh from the current items at posting time (self-healing, Section 80).
 */
class PostBill
{
    public function __construct(private PostJournal $postJournal) {}

    public function handle(Bill $bill): Bill
    {
        if (! $bill->isDraft()) {
            throw new RuntimeException('Only a draft bill can be posted.');
        }

        $bill->loadMissing('items');

        if ($bill->items->isEmpty()) {
            throw new RuntimeException('A bill must have at least one item before it can be posted.');
        }

        return DB::transaction(function () use ($bill) {
            $subtotal = '0.0000';
            $discountTotal = '0.0000';

            foreach ($bill->items as $item) {
                $subtotal = bcadd($subtotal, bcmul((string) $item->quantity, (string) $item->unit_price, 4), 4);
                $discountTotal = bcadd($discountTotal, (string) $item->discount, 4);
            }

            $total = $bill->items->reduce(
                fn (string $carry, $item) => bcadd($carry, (string) $item->line_total, 4),
                '0.0000'
            );

            $bill->update(['subtotal' => $subtotal, 'discount_total' => $discountTotal, 'total' => $total]);

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

            return $bill->fresh(['items', 'journal']);
        });
    }
}
