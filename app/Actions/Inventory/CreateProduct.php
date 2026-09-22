<?php

namespace App\Actions\Inventory;

use App\Models\Inventory\Product;
use Illuminate\Support\Facades\DB;

/**
 * Creates a product and, if an inventory-tracked product is given a
 * non-zero opening quantity, an 'opening' StockMovement for it.
 *
 * The opening movement is deliberately NOT posted to accounting — it is
 * treated the same way Account/Customer/Vendor opening balances are: an
 * assumed starting position, not a journaled event. Only later
 * adjustments (via AdjustStock) post a journal.
 */
class CreateProduct
{
    /**
     * @param  array{sku:string, name:string, product_category_id:int, type:string, unit:string, purchase_price:numeric-string|float, selling_price:numeric-string|float, income_account_id:int, cogs_account_id:?int, inventory_account_id:?int, low_stock_threshold:numeric-string|float|null, is_active:bool, opening_quantity?:numeric-string|float|null, opening_date?:?string, created_by?:?int}  $data
     */
    public function handle(array $data): Product
    {
        return DB::transaction(function () use ($data) {
            $product = Product::create([
                'sku' => $data['sku'],
                'name' => $data['name'],
                'product_category_id' => $data['product_category_id'],
                'type' => $data['type'],
                'unit' => $data['unit'],
                'purchase_price' => $data['purchase_price'] ?? 0,
                'selling_price' => $data['selling_price'],
                'income_account_id' => $data['income_account_id'],
                'cogs_account_id' => $data['cogs_account_id'] ?? null,
                'inventory_account_id' => $data['inventory_account_id'] ?? null,
                'low_stock_threshold' => $data['low_stock_threshold'] ?? null,
                'is_active' => $data['is_active'] ?? true,
            ]);

            $openingQuantity = (string) ($data['opening_quantity'] ?? 0);

            if ($product->type === 'inventory' && bccomp($openingQuantity, '0', 4) !== 0) {
                $product->stockMovements()->create([
                    'date' => $data['opening_date'] ?? now()->toDateString(),
                    'quantity' => $openingQuantity,
                    'reason' => 'opening',
                    'reference' => null,
                    'notes' => 'Opening stock quantity.',
                    'created_by' => $data['created_by'] ?? null,
                ]);
            }

            return $product;
        });
    }
}
