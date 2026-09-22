<?php

namespace App\Actions\Purchases;

use App\Actions\Accounting\PostJournal;
use App\Models\Purchases\VendorCredit;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * The exact reverse of PostBill: debit the payable account (tagged to
 * the vendor, reducing what's owed to them), credit each item's expense
 * account (reversing the expense), credit the tax account for any input
 * tax being reversed.
 */
class PostVendorCredit
{
    public function __construct(private PostJournal $postJournal) {}

    public function handle(VendorCredit $vendorCredit): VendorCredit
    {
        if (! $vendorCredit->isDraft()) {
            throw new RuntimeException('Only a draft vendor credit can be posted.');
        }

        $vendorCredit->loadMissing('items.taxRate');

        if ($vendorCredit->items->isEmpty()) {
            throw new RuntimeException('A vendor credit must have at least one item before it can be posted.');
        }

        return DB::transaction(function () use ($vendorCredit) {
            $subtotal = '0.0000';
            $discountTotal = '0.0000';
            $taxTotal = '0.0000';
            $taxByAccount = [];

            foreach ($vendorCredit->items as $item) {
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
                $vendorCredit->items->reduce(fn (string $carry, $item) => bcadd($carry, (string) $item->line_total, 4), '0.0000'),
                $taxTotal,
                4
            );

            $vendorCredit->update([
                'subtotal' => $subtotal,
                'discount_total' => $discountTotal,
                'tax_total' => $taxTotal,
                'total' => $total,
            ]);

            if (bccomp($total, '0', 4) <= 0) {
                throw new RuntimeException('A vendor credit with a zero or negative total cannot be posted.');
            }

            $lines = [
                [
                    'account_id' => $vendorCredit->payable_account_id,
                    'vendor_id' => $vendorCredit->vendor_id,
                    'debit' => $total,
                    'credit' => 0,
                    'description' => "Vendor Credit {$vendorCredit->vendor_credit_number}",
                ],
            ];

            foreach ($vendorCredit->items as $item) {
                $lines[] = [
                    'account_id' => $item->account_id,
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
                    'description' => "Tax reversed — Vendor Credit {$vendorCredit->vendor_credit_number}",
                ];
            }

            $this->postJournal->handle([
                'date' => $vendorCredit->vendor_credit_date->toDateString(),
                'reference' => $vendorCredit->vendor_credit_number,
                'description' => "Vendor Credit {$vendorCredit->vendor_credit_number}",
                'created_by' => $vendorCredit->created_by,
                'source_type' => VendorCredit::class,
                'source_id' => $vendorCredit->id,
                'lines' => $lines,
            ]);

            $vendorCredit->update(['status' => 'posted', 'posted_at' => now()]);

            return $vendorCredit->fresh(['items', 'journal']);
        });
    }
}
