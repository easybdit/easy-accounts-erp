<?php

namespace App\Actions\Inventory;

use App\Actions\Accounting\PostJournal;
use App\Models\Inventory\Product;
use App\Models\Inventory\StockMovement;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Corrects a product's stock to a physically-counted quantity. The user
 * enters what they counted, not a delta — the system computes the
 * difference, records it as a single traceable StockMovement (Section 32),
 * and — if the difference has a non-zero monetary value at the product's
 * current purchase price — posts a balanced journal: an increase debits
 * the inventory asset account and credits the COGS account (a "found"
 * reduction in cost); a decrease is the reverse (shrinkage becomes a cost).
 */
class AdjustStock
{
    public function __construct(private PostJournal $postJournal) {}

    /**
     * @param  array{product_id:int, counted_quantity:numeric-string|float, date:string, reference:?string, notes:?string, created_by:?int}  $data
     */
    public function handle(array $data): StockMovement
    {
        $product = Product::findOrFail($data['product_id']);

        if (! $product->isInventoryTracked()) {
            throw new RuntimeException('Only inventory-tracked products can have their stock adjusted.');
        }

        $currentQuantity = $product->currentStock();
        $delta = bcsub((string) $data['counted_quantity'], $currentQuantity, 4);

        if (bccomp($delta, '0', 4) === 0) {
            throw new RuntimeException('The counted quantity matches the current stock — nothing to adjust.');
        }

        return DB::transaction(function () use ($data, $product, $delta) {
            $movement = $product->stockMovements()->create([
                'date' => $data['date'],
                'quantity' => $delta,
                'reason' => 'adjustment',
                'reference' => $data['reference'] ?? null,
                'notes' => $data['notes'] ?? null,
                'created_by' => $data['created_by'] ?? null,
            ]);

            $value = bcmul(ltrim($delta, '-'), (string) $product->purchase_price, 4);

            if (bccomp($value, '0', 4) > 0) {
                $increasing = bccomp($delta, '0', 4) > 0;

                $this->postJournal->handle([
                    'date' => $data['date'],
                    'reference' => "ADJ-{$movement->id}",
                    'description' => "Stock adjustment for {$product->sku} — {$product->name}",
                    'created_by' => $data['created_by'] ?? null,
                    'source_type' => StockMovement::class,
                    'source_id' => $movement->id,
                    'lines' => $increasing
                        ? [
                            ['account_id' => $product->inventory_account_id, 'debit' => $value, 'credit' => 0, 'description' => 'Stock adjustment (increase)'],
                            ['account_id' => $product->cogs_account_id, 'debit' => 0, 'credit' => $value, 'description' => 'Stock adjustment (increase)'],
                        ]
                        : [
                            ['account_id' => $product->cogs_account_id, 'debit' => $value, 'credit' => 0, 'description' => 'Stock adjustment (decrease)'],
                            ['account_id' => $product->inventory_account_id, 'debit' => 0, 'credit' => $value, 'description' => 'Stock adjustment (decrease)'],
                        ],
                ]);
            }

            return $movement->load('journal');
        });
    }
}
