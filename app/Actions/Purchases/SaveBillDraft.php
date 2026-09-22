<?php

namespace App\Actions\Purchases;

use App\Models\Purchases\Bill;
use App\Models\Tax\TaxRate;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Creates or updates a DRAFT bill and its items, always recomputing
 * subtotal/discount/total from the submitted items server-side (Section 45).
 * Mirrors App\Actions\Sales\SaveInvoiceDraft.
 */
class SaveBillDraft
{
    public function handle(array $data, ?Bill $bill = null): Bill
    {
        if ($bill && ! $bill->isDraft()) {
            throw new RuntimeException('A posted bill cannot be edited.');
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

        return DB::transaction(function () use ($data, $items, $lineTotals, $taxAmounts, $subtotal, $discountTotal, $taxTotal, $total, $bill) {
            $bill = $bill
                ? tap($bill)->update([
                    'vendor_id' => $data['vendor_id'],
                    'payable_account_id' => $data['payable_account_id'],
                    'bill_date' => $data['bill_date'],
                    'due_date' => $data['due_date'] ?? null,
                    'notes' => $data['notes'] ?? null,
                    'subtotal' => $subtotal,
                    'discount_total' => $discountTotal,
                    'tax_total' => $taxTotal,
                    'total' => $total,
                ])
                : Bill::create([
                    'bill_number' => $this->nextBillNumber($data['bill_date']),
                    'vendor_id' => $data['vendor_id'],
                    'payable_account_id' => $data['payable_account_id'],
                    'bill_date' => $data['bill_date'],
                    'due_date' => $data['due_date'] ?? null,
                    'notes' => $data['notes'] ?? null,
                    'status' => 'draft',
                    'subtotal' => $subtotal,
                    'discount_total' => $discountTotal,
                    'tax_total' => $taxTotal,
                    'total' => $total,
                    'created_by' => $data['created_by'] ?? null,
                ]);

            $bill->items()->delete();

            foreach ($items as $index => $item) {
                $bill->items()->create([
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

            return $bill->load('items');
        });
    }

    private function nextBillNumber(string $billDate): string
    {
        $year = date('Y', strtotime($billDate));
        $count = Bill::where('bill_number', 'like', "BILL-{$year}-%")->count() + 1;

        return sprintf('BILL-%s-%04d', $year, $count);
    }
}
