<?php

namespace App\Actions\Sales;

use App\Actions\Accounting\PostJournal;
use App\Models\Sales\CreditNote;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * The exact reverse of PostInvoice: credit the receivable account (tagged
 * to the customer, reducing what they owe), debit each item's income
 * account (reversing the revenue), debit the tax account for any tax
 * being credited back (reversing output tax).
 */
class PostCreditNote
{
    public function __construct(private PostJournal $postJournal) {}

    public function handle(CreditNote $creditNote): CreditNote
    {
        if (! $creditNote->isDraft()) {
            throw new RuntimeException('Only a draft credit note can be posted.');
        }

        $creditNote->loadMissing('items.taxRate');

        if ($creditNote->items->isEmpty()) {
            throw new RuntimeException('A credit note must have at least one item before it can be posted.');
        }

        return DB::transaction(function () use ($creditNote) {
            $subtotal = '0.0000';
            $discountTotal = '0.0000';
            $taxTotal = '0.0000';
            $taxByAccount = [];

            foreach ($creditNote->items as $item) {
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
                $creditNote->items->reduce(fn (string $carry, $item) => bcadd($carry, (string) $item->line_total, 4), '0.0000'),
                $taxTotal,
                4
            );

            $creditNote->update([
                'subtotal' => $subtotal,
                'discount_total' => $discountTotal,
                'tax_total' => $taxTotal,
                'total' => $total,
            ]);

            if (bccomp($total, '0', 4) <= 0) {
                throw new RuntimeException('A credit note with a zero or negative total cannot be posted.');
            }

            $lines = [
                [
                    'account_id' => $creditNote->receivable_account_id,
                    'customer_id' => $creditNote->customer_id,
                    'debit' => 0,
                    'credit' => $total,
                    'description' => "Credit Note {$creditNote->credit_note_number}",
                ],
            ];

            foreach ($creditNote->items as $item) {
                $lines[] = [
                    'account_id' => $item->account_id,
                    'debit' => (string) $item->line_total,
                    'credit' => 0,
                    'description' => $item->description,
                ];
            }

            foreach ($taxByAccount as $accountId => $amount) {
                $lines[] = [
                    'account_id' => $accountId,
                    'debit' => $amount,
                    'credit' => 0,
                    'description' => "Tax reversed — Credit Note {$creditNote->credit_note_number}",
                ];
            }

            $this->postJournal->handle([
                'date' => $creditNote->credit_note_date->toDateString(),
                'reference' => $creditNote->credit_note_number,
                'description' => "Credit Note {$creditNote->credit_note_number}",
                'created_by' => $creditNote->created_by,
                'source_type' => CreditNote::class,
                'source_id' => $creditNote->id,
                'lines' => $lines,
            ]);

            $creditNote->update(['status' => 'posted', 'posted_at' => now()]);

            return $creditNote->fresh(['items', 'journal']);
        });
    }
}
