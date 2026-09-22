<?php

namespace Tests\Feature\Inventory;

use App\Models\Inventory\Product;
use App\Models\Inventory\StockMovement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class StockAdjustmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_view_stock_movements(): void
    {
        $this->get(route('inventory.stock-movements.index'))->assertRedirect(route('login'));
    }

    public function test_increasing_stock_debits_inventory_and_credits_cogs(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->inventoryTracked()->create(['purchase_price' => 10]);
        $product->stockMovements()->create(['date' => '2026-01-01', 'quantity' => 20, 'reason' => 'opening']);

        $response = $this->actingAs($user)->post(route('inventory.stock-movements.store'), [
            'product_id' => $product->id,
            'counted_quantity' => 25,
            'date' => '2026-01-15',
        ]);

        $response->assertRedirect();
        $this->assertSame('25.0000', $product->fresh()->currentStock());

        $movement = StockMovement::where('reason', 'adjustment')->first();
        $this->assertSame('5.0000', $movement->quantity);

        $journal = $movement->journal;
        $this->assertNotNull($journal);
        $this->assertTrue($journal->isBalanced());
        // 5 units x 10 purchase price = 50
        $this->assertSame('50.0000', $journal->totalDebit());
        $this->assertSame($product->inventory_account_id, $journal->entries->firstWhere('debit', '50.0000')->account_id);
    }

    public function test_decreasing_stock_debits_cogs_and_credits_inventory(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->inventoryTracked()->create(['purchase_price' => 10]);
        $product->stockMovements()->create(['date' => '2026-01-01', 'quantity' => 20, 'reason' => 'opening']);

        $this->actingAs($user)->post(route('inventory.stock-movements.store'), [
            'product_id' => $product->id,
            'counted_quantity' => 15,
            'date' => '2026-01-15',
        ]);

        $this->assertSame('15.0000', $product->fresh()->currentStock());

        $movement = StockMovement::where('reason', 'adjustment')->first();
        $this->assertSame('-5.0000', $movement->quantity);
        $this->assertSame($product->cogs_account_id, $movement->journal->entries->firstWhere('debit', '50.0000')->account_id);
    }

    public function test_adjustment_matching_current_stock_is_rejected(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->inventoryTracked()->create();
        $product->stockMovements()->create(['date' => '2026-01-01', 'quantity' => 20, 'reason' => 'opening']);

        $response = $this->actingAs($user)->post(route('inventory.stock-movements.store'), [
            'product_id' => $product->id,
            'counted_quantity' => 20,
            'date' => '2026-01-15',
        ]);

        $response->assertSessionHasErrors('adjustment');
        $this->assertSame(1, StockMovement::count());
    }

    public function test_service_products_cannot_be_adjusted(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create(['type' => 'service']);

        $response = $this->actingAs($user)->post(route('inventory.stock-movements.store'), [
            'product_id' => $product->id,
            'counted_quantity' => 10,
            'date' => '2026-01-15',
        ]);

        $response->assertSessionHasErrors('product_id');
    }

    public function test_no_edit_or_delete_routes_exist_for_stock_movements(): void
    {
        $this->assertFalse(Route::has('inventory.stock-movements.edit'));
        $this->assertFalse(Route::has('inventory.stock-movements.update'));
        $this->assertFalse(Route::has('inventory.stock-movements.destroy'));
    }
}
