<?php

namespace App\Actions\Purchases;

use App\Models\Purchases\Bill;
use App\Models\Purchases\PurchaseOrder;
use App\Models\Purchases\PurchaseOrderItem;
use RuntimeException;

/**
 * Turns a Purchase Order into a real draft Bill via SaveBillDraft — mirrors
 * App\Actions\Sales\ConvertEstimateToInvoice exactly.
 */
class ConvertPurchaseOrderToBill
{
    public function __construct(private SaveBillDraft $saveBillDraft) {}

    public function handle(PurchaseOrder $purchaseOrder, ?int $userId): Bill
    {
        if ($purchaseOrder->isConverted()) {
            throw new RuntimeException('This purchase order has already been converted to a bill.');
        }

        $purchaseOrder->loadMissing('items');

        $bill = $this->saveBillDraft->handle([
            'vendor_id' => $purchaseOrder->vendor_id,
            'payable_account_id' => $purchaseOrder->payable_account_id,
            'bill_date' => now()->toDateString(),
            'due_date' => null,
            'notes' => $purchaseOrder->notes,
            'created_by' => $userId,
            'items' => $purchaseOrder->items->map(fn (PurchaseOrderItem $item) => [
                'account_id' => $item->account_id,
                'tax_rate_id' => $item->tax_rate_id,
                'description' => $item->description,
                'quantity' => $item->quantity,
                'unit_price' => $item->unit_price,
                'discount' => $item->discount,
            ])->all(),
        ]);

        $purchaseOrder->update(['status' => 'converted', 'converted_bill_id' => $bill->id]);

        return $bill;
    }
}
