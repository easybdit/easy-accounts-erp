<?php

namespace Database\Factories\Inventory;

use App\Models\Accounting\Account;
use App\Models\Inventory\Product;
use App\Models\Inventory\ProductCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition(): array
    {
        return [
            'sku' => 'SKU-'.$this->faker->unique()->numberBetween(1000, 9999),
            'name' => $this->faker->unique()->words(2, true),
            'product_category_id' => ProductCategory::factory(),
            'type' => 'service',
            'unit' => 'pcs',
            'purchase_price' => 0,
            'selling_price' => 100,
            'income_account_id' => Account::factory()->state(['type' => 'income']),
            'cogs_account_id' => null,
            'inventory_account_id' => null,
            'low_stock_threshold' => null,
            'is_active' => true,
        ];
    }

    public function inventoryTracked(): static
    {
        return $this->state(fn () => [
            'type' => 'inventory',
            'purchase_price' => 50,
            'cogs_account_id' => Account::factory()->state(['type' => 'expense']),
            'inventory_account_id' => Account::factory()->state(['type' => 'asset']),
        ]);
    }
}
