<?php

namespace App\Actions\Purchases;

use App\Models\Purchases\Bill;
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
        $lineTotals = [];

        foreach ($items as $item) {
            $quantity = (string) $item['quantity'];
            $unitPrice = (string) $item['unit_price'];
            $discount = (string) ($item['discount'] ?? 0);

            $gross = bcmul($quantity, $unitPrice, 4);
            $lineTotal = bcsub($gross, $discount, 4);

            $subtotal = bcadd($subtotal, $gross, 4);
            $discountTotal = bcadd($discountTotal, $discount, 4);
            $lineTotals[] = $lineTotal;
        }

        $total = bcsub($subtotal, $discountTotal, 4);

        return DB::transaction(function () use ($data, $items, $lineTotals, $subtotal, $discountTotal, $total, $bill) {
            $bill = $bill
                ? tap($bill)->update([
                    'vendor_id' => $data['vendor_id'],
                    'payable_account_id' => $data['payable_account_id'],
                    'bill_date' => $data['bill_date'],
                    'due_date' => $data['due_date'] ?? null,
                    'notes' => $data['notes'] ?? null,
                    'subtotal' => $subtotal,
                    'discount_total' => $discountTotal,
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
                    'total' => $total,
                    'created_by' => $data['created_by'] ?? null,
                ]);

            $bill->items()->delete();

            foreach ($items as $index => $item) {
                $bill->items()->create([
                    'account_id' => $item['account_id'],
                    'description' => $item['description'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'discount' => $item['discount'] ?? 0,
                    'line_total' => $lineTotals[$index],
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
