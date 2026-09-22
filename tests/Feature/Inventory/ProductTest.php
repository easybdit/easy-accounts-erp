<?php

namespace Tests\Feature\Inventory;

use App\Models\Accounting\Account;
use App\Models\Inventory\Product;
use App\Models\Inventory\ProductCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductTest extends TestCase
{
    use RefreshDatabase;

    private function inventoryPayload(array $overrides = []): array
    {
        $category = ProductCategory::factory()->create();
        $income = Account::factory()->create(['type' => 'income']);
        $cogs = Account::factory()->create(['type' => 'expense']);
        $inventory = Account::factory()->create(['type' => 'asset']);

        return array_merge([
            'sku' => 'SKU-1001',
            'name' => 'Widget',
            'product_category_id' => $category->id,
            'type' => 'inventory',
            'unit' => 'pcs',
            'purchase_price' => 10,
            'selling_price' => 25,
            'income_account_id' => $income->id,
            'cogs_account_id' => $cogs->id,
            'inventory_account_id' => $inventory->id,
        ], $overrides);
    }

    public function test_guest_cannot_view_products(): void
    {
        $this->get(route('inventory.products.index'))->assertRedirect(route('login'));
    }

    public function test_product_can_be_created_with_opening_quantity(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('inventory.products.store'), array_merge(
            $this->inventoryPayload(),
            ['opening_quantity' => 50, 'opening_date' => '2026-01-01']
        ))->assertRedirect();

        $product = Product::first();
        $this->assertSame('50.0000', $product->currentStock());
        $this->assertDatabaseCount('stock_movements', 1);
        $this->assertDatabaseHas('stock_movements', ['product_id' => $product->id, 'reason' => 'opening']);
    }

    public function test_opening_stock_does_not_post_a_journal(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('inventory.products.store'), array_merge(
            $this->inventoryPayload(),
            ['opening_quantity' => 50, 'opening_date' => '2026-01-01']
        ));

        $this->assertDatabaseCount('journals', 0);
    }

    public function test_service_product_does_not_require_inventory_accounts(): void
    {
        $user = User::factory()->create();
        $category = ProductCategory::factory()->create();
        $income = Account::factory()->create(['type' => 'income']);

        $response = $this->actingAs($user)->post(route('inventory.products.store'), [
            'sku' => 'SVC-1',
            'name' => 'Consulting Hour',
            'product_category_id' => $category->id,
            'type' => 'service',
            'unit' => 'hour',
            'selling_price' => 150,
            'income_account_id' => $income->id,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('products', ['sku' => 'SVC-1', 'type' => 'service']);
    }

    public function test_inventory_product_requires_cogs_and_inventory_accounts(): void
    {
        $user = User::factory()->create();
        $category = ProductCategory::factory()->create();
        $income = Account::factory()->create(['type' => 'income']);

        $response = $this->actingAs($user)->post(route('inventory.products.store'), [
            'sku' => 'SKU-2',
            'name' => 'Gadget',
            'product_category_id' => $category->id,
            'type' => 'inventory',
            'unit' => 'pcs',
            'selling_price' => 25,
            'income_account_id' => $income->id,
        ]);

        $response->assertSessionHasErrors(['cogs_account_id', 'inventory_account_id']);
    }

    public function test_sku_must_be_unique(): void
    {
        Product::factory()->create(['sku' => 'DUPLICATE']);
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(
            route('inventory.products.store'),
            $this->inventoryPayload(['sku' => 'DUPLICATE'])
        );

        $response->assertSessionHasErrors('sku');
    }

    public function test_product_with_stock_movements_cannot_be_deleted(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->post(route('inventory.products.store'), array_merge(
            $this->inventoryPayload(),
            ['opening_quantity' => 10]
        ));
        $product = Product::first();

        $this->actingAs($user)->delete(route('inventory.products.destroy', $product))
            ->assertSessionHasErrors('product');

        $this->assertDatabaseHas('products', ['id' => $product->id]);
    }

    public function test_low_stock_is_flagged(): void
    {
        $product = Product::factory()->inventoryTracked()->create(['low_stock_threshold' => 5]);
        $product->stockMovements()->create(['date' => '2026-01-01', 'quantity' => 3, 'reason' => 'opening']);

        $this->assertTrue($product->fresh()->isLowStock());
    }
}
