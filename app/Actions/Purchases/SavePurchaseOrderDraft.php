<?php

namespace App\Actions\Purchases;

use App\Models\Purchases\PurchaseOrder;
use App\Models\Tax\TaxRate;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Creates or updates a Purchase Order and its items, mirroring
 * App\Actions\Sales\SaveEstimateDraft — never posts to the Journal.
 */
class SavePurchaseOrderDraft
{
    public function handle(array $data, ?PurchaseOrder $purchaseOrder = null): PurchaseOrder
    {
        if ($purchaseOrder && ! $purchaseOrder->isEditable()) {
            throw new RuntimeException('A converted purchase order cannot be edited.');
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

        return DB::transaction(function () use ($data, $items, $lineTotals, $taxAmounts, $subtotal, $discountTotal, $taxTotal, $total, $purchaseOrder) {
            $purchaseOrder = $purchaseOrder
                ? tap($purchaseOrder)->update([
                    'vendor_id' => $data['vendor_id'],
                    'payable_account_id' => $data['payable_account_id'],
                    'order_date' => $data['order_date'],
                    'expected_date' => $data['expected_date'] ?? null,
                    'status' => $data['status'] ?? $purchaseOrder->status,
                    'notes' => $data['notes'] ?? null,
                    'subtotal' => $subtotal,
                    'discount_total' => $discountTotal,
                    'tax_total' => $taxTotal,
                    'total' => $total,
                ])
                : PurchaseOrder::create([
                    'po_number' => $this->nextPoNumber($data['order_date']),
                    'vendor_id' => $data['vendor_id'],
                    'payable_account_id' => $data['payable_account_id'],
                    'order_date' => $data['order_date'],
                    'expected_date' => $data['expected_date'] ?? null,
                    'status' => 'draft',
                    'notes' => $data['notes'] ?? null,
                    'subtotal' => $subtotal,
                    'discount_total' => $discountTotal,
                    'tax_total' => $taxTotal,
                    'total' => $total,
                    'created_by' => $data['created_by'] ?? null,
                ]);

            $purchaseOrder->items()->delete();

            foreach ($items as $index => $item) {
                $purchaseOrder->items()->create([
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

            return $purchaseOrder->load('items');
        });
    }

    private function nextPoNumber(string $orderDate): string
    {
        $year = date('Y', strtotime($orderDate));
        $count = PurchaseOrder::where('po_number', 'like', "PO-{$year}-%")->count() + 1;

        return sprintf('PO-%s-%04d', $year, $count);
    }
}
