<?php

namespace App\Actions\Sales;

use App\Actions\Accounting\GenerateDocumentNumber;
use App\Actions\Accounting\PostJournal;
use App\Actions\Inventory\RecordInventorySaleMovement;
use App\Models\Inventory\Product;
use App\Models\Sales\SalesReceipt;
use App\Models\Tax\TaxRate;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Records an immediate cash sale and, in the same atomic step, posts it —
 * there is no draft state (Section 12/28: a Sales Receipt is settled in
 * full at the point of sale, unlike an Invoice's draft -> post -> AR ->
 * Payment lifecycle). Combines what SaveInvoiceDraft (line calculation) and
 * PostInvoice (posting, tax, COGS) do separately for Invoices, since there
 * is no draft/post split here — same "creating IS posting it" pattern as
 * ReceivePayment.
 *
 * Debits the deposit account (Cash/Bank/Undeposited Funds — the same field
 * Payment.deposit_account_id already uses) for the total, credits each
 * item's income account for its line total, credits each distinct tax
 * account for tax collected. Inventory-tracked items also get a stock-out
 * movement and COGS journal via the shared RecordInventorySaleMovement
 * action (Section 32) — same integration Invoice already has.
 */
class RecordSalesReceipt
{
    public function __construct(
        private PostJournal $postJournal,
        private GenerateDocumentNumber $generateDocumentNumber,
        private RecordInventorySaleMovement $recordInventorySaleMovement,
    ) {}

    public function handle(array $data): SalesReceipt
    {
        $items = $data['items'];
        $taxInclusive = $data['tax_inclusive'] ?? false;

        $subtotal = '0.0000';
        $discountTotal = '0.0000';
        $taxTotal = '0.0000';
        $lineTotals = [];
        $taxAmounts = [];
        $taxAmounts2 = [];

        $taxRateIds = array_filter(array_merge(array_column($items, 'tax_rate_id'), array_column($items, 'tax_rate_2_id')));
        $taxRates = TaxRate::whereIn('id', $taxRateIds)->get()->keyBy('id');

        foreach ($items as $item) {
            $quantity = (string) $item['quantity'];
            $unitPrice = (string) $item['unit_price'];
            $discount = (string) ($item['discount'] ?? 0);

            $gross = bcmul($quantity, $unitPrice, 4);
            $grossAfterDiscount = bcsub($gross, $discount, 4);
            $taxRate = $taxRates->get($item['tax_rate_id'] ?? null);
            $taxRate2 = $taxRates->get($item['tax_rate_2_id'] ?? null);

            // Tax Inclusive (Section 33): mirrors SaveInvoiceDraft/PostInvoice
            // — with two independent (non-compounding) rates, the net is
            // backed out using their combined rate, then each tax is its
            // own share of that net.
            if ($taxInclusive && ($taxRate || $taxRate2)) {
                $combinedRate = bcadd((string) ($taxRate->rate ?? '0'), (string) ($taxRate2->rate ?? '0'), 6);
                $divisor = bcadd('100', $combinedRate, 6);
                $lineTotal = bcdiv(bcmul($grossAfterDiscount, '100', 6), $divisor, 4);
            } else {
                $lineTotal = $grossAfterDiscount;
            }

            $taxAmount = $taxRate ? $taxRate->calculate($lineTotal) : '0.0000';
            $taxAmount2 = $taxRate2 ? $taxRate2->calculate($lineTotal) : '0.0000';

            $subtotal = bcadd($subtotal, $gross, 4);
            $discountTotal = bcadd($discountTotal, $discount, 4);
            $taxTotal = bcadd($taxTotal, bcadd($taxAmount, $taxAmount2, 4), 4);
            $lineTotals[] = $lineTotal;
            $taxAmounts[] = $taxAmount;
            $taxAmounts2[] = $taxAmount2;
        }

        $total = bcadd(array_reduce($lineTotals, fn (string $carry, string $lineTotal) => bcadd($carry, $lineTotal, 4), '0.0000'), $taxTotal, 4);

        if (bccomp($total, '0', 4) <= 0) {
            throw new RuntimeException('A sales receipt with a zero or negative total cannot be recorded.');
        }

        return DB::transaction(function () use ($data, $items, $lineTotals, $taxAmounts, $taxAmounts2, $taxRates, $subtotal, $discountTotal, $taxTotal, $total, $taxInclusive) {
            $receipt = SalesReceipt::create([
                'receipt_number' => $this->generateDocumentNumber->handle('sales_receipt', SalesReceipt::class, 'receipt_number', $data['receipt_date']),
                'customer_id' => $data['customer_id'],
                'deposit_account_id' => $data['deposit_account_id'],
                'receipt_date' => $data['receipt_date'],
                'tax_inclusive' => $taxInclusive,
                'notes' => $data['notes'] ?? null,
                'subtotal' => $subtotal,
                'discount_total' => $discountTotal,
                'tax_total' => $taxTotal,
                'total' => $total,
                'created_by' => $data['created_by'] ?? null,
            ]);

            $taxByAccount = [];
            $createdItems = [];

            foreach ($items as $index => $item) {
                $createdItems[] = $receipt->items()->create([
                    'product_id' => $item['product_id'] ?? null,
                    'account_id' => $item['account_id'],
                    'tax_rate_id' => $item['tax_rate_id'] ?? null,
                    'tax_rate_2_id' => $item['tax_rate_2_id'] ?? null,
                    'description' => $item['description'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'discount' => $item['discount'] ?? 0,
                    'line_total' => $lineTotals[$index],
                    'tax_amount' => $taxAmounts[$index],
                    'tax_amount_2' => $taxAmounts2[$index],
                ]);

                $taxRate = $taxRates->get($item['tax_rate_id'] ?? null);
                $taxRate2 = $taxRates->get($item['tax_rate_2_id'] ?? null);

                if ($taxRate && bccomp($taxAmounts[$index], '0', 4) > 0) {
                    $accountId = $taxRate->tax_account_id;
                    $taxByAccount[$accountId] = bcadd($taxByAccount[$accountId] ?? '0.0000', $taxAmounts[$index], 4);
                }

                if ($taxRate2 && bccomp($taxAmounts2[$index], '0', 4) > 0) {
                    $accountId = $taxRate2->tax_account_id;
                    $taxByAccount[$accountId] = bcadd($taxByAccount[$accountId] ?? '0.0000', $taxAmounts2[$index], 4);
                }
            }

            $lines = [
                [
                    'account_id' => $data['deposit_account_id'],
                    'debit' => $total,
                    'credit' => 0,
                    'description' => "Sales Receipt {$receipt->receipt_number}",
                ],
            ];

            foreach ($createdItems as $item) {
                $lines[] = [
                    'account_id' => $item->account_id,
                    'customer_id' => $data['customer_id'],
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
                    'description' => "Tax collected — Sales Receipt {$receipt->receipt_number}",
                ];
            }

            $this->postJournal->handle([
                'date' => $data['receipt_date'],
                'reference' => $receipt->receipt_number,
                'description' => "Sales Receipt {$receipt->receipt_number}",
                'created_by' => $data['created_by'] ?? null,
                'source_type' => SalesReceipt::class,
                'source_id' => $receipt->id,
                'lines' => $lines,
            ]);

            foreach ($createdItems as $item) {
                if ($item->product_id) {
                    $product = Product::find($item->product_id);

                    if ($product && $product->isInventoryTracked()) {
                        $this->recordInventorySaleMovement->handle(
                            $product,
                            $data['receipt_date'],
                            (string) $item->quantity,
                            $receipt->receipt_number,
                            $data['created_by'] ?? null
                        );
                    }
                }
            }

            return $receipt->fresh(['items', 'journal']);
        });
    }
}
