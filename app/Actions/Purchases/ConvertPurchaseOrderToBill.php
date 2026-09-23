<?php

namespace App\Actions\Purchases;

use App\Models\Purchases\Bill;
use App\Models\Purchases\PurchaseOrder;
use App\Models\Purchases\PurchaseOrderItem;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Turns a Purchase Order into a real draft Bill via SaveBillDraft — mirrors
 * App\Actions\Sales\ConvertEstimateToInvoice, extended to support partial
 * quantities (e.g. 6 of 10 ordered routers arrived this week): a PO can be
 * converted more than once, each time for however much of each line is
 * being billed now, until every line's billed_quantity reaches its
 * ordered quantity. Omitting $quantities converts everything still
 * remaining in one shot — the original, still-most-common one-click case.
 */
class ConvertPurchaseOrderToBill
{
    public function __construct(private SaveBillDraft $saveBillDraft) {}

    /**
     * @param  array<int, numeric-string|float>|null  $quantities  PurchaseOrderItem id => quantity to bill now.
     */
    public function handle(PurchaseOrder $purchaseOrder, ?array $quantities, ?int $userId): Bill
    {
        return DB::transaction(function () use ($purchaseOrder, $quantities, $userId) {
            $purchaseOrder->loadMissing('items');

            if ($purchaseOrder->isFullyBilled()) {
                throw new RuntimeException('This purchase order has already been fully billed.');
            }

            $lines = [];

            foreach ($purchaseOrder->items as $item) {
                $remaining = $item->remainingQuantity();
                $requested = ($quantities !== null && array_key_exists($item->id, $quantities))
                    ? (string) $quantities[$item->id]
                    : $remaining;

                if (bccomp($requested, '0', 4) < 0) {
                    throw new RuntimeException('Quantity to bill cannot be negative.');
                }

                if (bccomp($requested, $remaining, 4) > 0) {
                    throw new RuntimeException("Quantity to bill for \"{$item->description}\" ({$requested}) exceeds the remaining {$remaining}.");
                }

                if (bccomp($requested, '0', 4) === 0) {
                    continue;
                }

                // The discount carries over proportionally to the share of
                // the line being billed now, so a partial conversion never
                // drops or double-applies it.
                $discountShare = bccomp((string) $item->quantity, '0', 4) > 0
                    ? bcmul((string) $item->discount, bcdiv($requested, (string) $item->quantity, 8), 4)
                    : '0.0000';

                $lines[] = ['item' => $item, 'quantity' => $requested, 'discount' => $discountShare];
            }

            if (empty($lines)) {
                throw new RuntimeException('Select at least one line with a quantity greater than zero to bill.');
            }

            $bill = $this->saveBillDraft->handle([
                'vendor_id' => $purchaseOrder->vendor_id,
                'purchase_order_id' => $purchaseOrder->id,
                'payable_account_id' => $purchaseOrder->payable_account_id,
                'bill_date' => now()->toDateString(),
                'due_date' => null,
                'notes' => $purchaseOrder->notes,
                'created_by' => $userId,
                'items' => collect($lines)->map(fn (array $line) => [
                    'account_id' => $line['item']->account_id,
                    'tax_rate_id' => $line['item']->tax_rate_id,
                    'description' => $line['item']->description,
                    'quantity' => $line['quantity'],
                    'unit_price' => $line['item']->unit_price,
                    'discount' => $line['discount'],
                ])->all(),
            ]);

            foreach ($lines as $line) {
                /** @var PurchaseOrderItem $item */
                $item = $line['item'];
                $item->update(['billed_quantity' => bcadd((string) $item->billed_quantity, $line['quantity'], 4)]);
            }

            $purchaseOrder->update(['converted_bill_id' => $bill->id]);

            if ($purchaseOrder->fresh()->isFullyBilled()) {
                $purchaseOrder->update(['status' => 'converted']);
            }

            return $bill;
        });
    }
}
