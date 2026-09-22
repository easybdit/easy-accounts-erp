<?php

namespace App\Actions\Sales;

use App\Models\Sales\Estimate;
use App\Models\Tax\TaxRate;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Creates or updates an Estimate and its items, recomputing totals from the
 * submitted items server-side (Section 45), exactly like SaveInvoiceDraft —
 * an Estimate just never posts to the Journal.
 */
class SaveEstimateDraft
{
    public function handle(array $data, ?Estimate $estimate = null): Estimate
    {
        if ($estimate && ! $estimate->isEditable()) {
            throw new RuntimeException('A converted estimate cannot be edited.');
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

        return DB::transaction(function () use ($data, $items, $lineTotals, $taxAmounts, $subtotal, $discountTotal, $taxTotal, $total, $estimate) {
            $estimate = $estimate
                ? tap($estimate)->update([
                    'customer_id' => $data['customer_id'],
                    'receivable_account_id' => $data['receivable_account_id'],
                    'estimate_date' => $data['estimate_date'],
                    'expiry_date' => $data['expiry_date'] ?? null,
                    'status' => $data['status'] ?? $estimate->status,
                    'notes' => $data['notes'] ?? null,
                    'subtotal' => $subtotal,
                    'discount_total' => $discountTotal,
                    'tax_total' => $taxTotal,
                    'total' => $total,
                ])
                : Estimate::create([
                    'estimate_number' => $this->nextEstimateNumber($data['estimate_date']),
                    'customer_id' => $data['customer_id'],
                    'receivable_account_id' => $data['receivable_account_id'],
                    'estimate_date' => $data['estimate_date'],
                    'expiry_date' => $data['expiry_date'] ?? null,
                    'status' => 'draft',
                    'notes' => $data['notes'] ?? null,
                    'subtotal' => $subtotal,
                    'discount_total' => $discountTotal,
                    'tax_total' => $taxTotal,
                    'total' => $total,
                    'created_by' => $data['created_by'] ?? null,
                ]);

            $estimate->items()->delete();

            foreach ($items as $index => $item) {
                $estimate->items()->create([
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

            return $estimate->load('items');
        });
    }

    private function nextEstimateNumber(string $estimateDate): string
    {
        $year = date('Y', strtotime($estimateDate));
        $count = Estimate::where('estimate_number', 'like', "EST-{$year}-%")->count() + 1;

        return sprintf('EST-%s-%04d', $year, $count);
    }
}
