<?php

namespace App\Actions\Inventory;

use App\Actions\Accounting\PostJournal;
use App\Models\Inventory\Product;
use App\Models\Inventory\StockMovement;

/**
 * Records a stock-out for one sold line (Invoice or Sales Receipt) and, if
 * that stock carries a non-zero cost, a companion COGS journal — debit
 * COGS, credit the product's inventory asset account — kept as its own
 * journal (its own source_type/source_id, tagged to the StockMovement, not
 * the sale) so both stay independently traceable (Section 32).
 *
 * Extracted from what was originally PostInvoice::recordSaleStockMovement()
 * so Sales Receipts can reuse the exact same behavior rather than
 * duplicating it.
 */
class RecordInventorySaleMovement
{
    public function __construct(private PostJournal $postJournal) {}

    public function handle(Product $product, string $date, string $quantity, string $reference, ?int $createdBy): void
    {
        $movement = $product->stockMovements()->create([
            'date' => $date,
            'quantity' => bcmul($quantity, '-1', 4),
            'reason' => 'sale',
            'reference' => $reference,
            'created_by' => $createdBy,
        ]);

        $cost = bcmul($quantity, (string) $product->purchase_price, 4);

        if (bccomp($cost, '0', 4) > 0) {
            $this->postJournal->handle([
                'date' => $date,
                'reference' => $reference,
                'description' => "COGS for {$reference} — {$product->sku}",
                'created_by' => $createdBy,
                'source_type' => StockMovement::class,
                'source_id' => $movement->id,
                'lines' => [
                    ['account_id' => $product->cogs_account_id, 'debit' => $cost, 'credit' => 0, 'description' => "COGS — {$product->name}"],
                    ['account_id' => $product->inventory_account_id, 'debit' => 0, 'credit' => $cost, 'description' => "COGS — {$product->name}"],
                ],
            ]);
        }
    }
}
