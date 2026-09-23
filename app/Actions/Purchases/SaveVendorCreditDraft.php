<?php

namespace App\Actions\Purchases;

use App\Actions\Accounting\GenerateDocumentNumber;
use App\Models\Purchases\VendorCredit;
use App\Models\Tax\TaxRate;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Creates or updates a DRAFT vendor credit and its items, mirroring
 * SaveCreditNoteDraft exactly (Section 45: recompute totals from
 * submitted items server-side, never trust the frontend).
 */
class SaveVendorCreditDraft
{
    public function __construct(private GenerateDocumentNumber $generateDocumentNumber) {}

    public function handle(array $data, ?VendorCredit $vendorCredit = null): VendorCredit
    {
        if ($vendorCredit && ! $vendorCredit->isDraft()) {
            throw new RuntimeException('A posted vendor credit cannot be edited.');
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

        return DB::transaction(function () use ($data, $items, $lineTotals, $taxAmounts, $subtotal, $discountTotal, $taxTotal, $total, $vendorCredit) {
            $vendorCredit = $vendorCredit
                ? tap($vendorCredit)->update([
                    'vendor_id' => $data['vendor_id'],
                    'payable_account_id' => $data['payable_account_id'],
                    'bill_id' => $data['bill_id'] ?? null,
                    'vendor_credit_date' => $data['vendor_credit_date'],
                    'notes' => $data['notes'] ?? null,
                    'subtotal' => $subtotal,
                    'discount_total' => $discountTotal,
                    'tax_total' => $taxTotal,
                    'total' => $total,
                ])
                : VendorCredit::create([
                    'vendor_credit_number' => $this->generateDocumentNumber->handle('vendor_credit', VendorCredit::class, 'vendor_credit_number', $data['vendor_credit_date']),
                    'vendor_id' => $data['vendor_id'],
                    'payable_account_id' => $data['payable_account_id'],
                    'bill_id' => $data['bill_id'] ?? null,
                    'vendor_credit_date' => $data['vendor_credit_date'],
                    'status' => 'draft',
                    'notes' => $data['notes'] ?? null,
                    'subtotal' => $subtotal,
                    'discount_total' => $discountTotal,
                    'tax_total' => $taxTotal,
                    'total' => $total,
                    'created_by' => $data['created_by'] ?? null,
                ]);

            $vendorCredit->items()->delete();

            foreach ($items as $index => $item) {
                $vendorCredit->items()->create([
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

            return $vendorCredit->load('items');
        });
    }
}
