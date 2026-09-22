<?php

namespace Database\Seeders\Inventory;

use App\Actions\Inventory\AdjustStock;
use App\Actions\Inventory\CreateProduct;
use App\Models\Accounting\Account;
use App\Models\Inventory\Product;
use App\Models\Inventory\ProductCategory;
use Illuminate\Database\Seeder;

/**
 * Two demo products — one inventory-tracked (with an opening quantity and
 * a demo stock adjustment exercising the accounting posting), one a
 * service (no stock tracking) — so both paths are visible out of the box.
 */
class ProductSeeder extends Seeder
{
    public function run(): void
    {
        if (Product::where('sku', 'like', 'WID-%')->exists()) {
            return;
        }

        $merchandise = ProductCategory::where('name', 'General Merchandise')->first();
        $consulting = ProductCategory::where('name', 'Consulting Services')->first();
        $salesRevenue = Account::where('code', '4001')->first();
        $serviceRevenue = Account::where('code', '4002')->first();
        $cogs = Account::where('code', '5001')->first();
        $inventory = Account::where('code', '1004')->first();

        if (! $merchandise || ! $consulting || ! $salesRevenue || ! $serviceRevenue || ! $cogs || ! $inventory) {
            return;
        }

        $widget = app(CreateProduct::class)->handle([
            'sku' => 'WID-001',
            'name' => 'Widget A',
            'product_category_id' => $merchandise->id,
            'type' => 'inventory',
            'unit' => 'pcs',
            'purchase_price' => 20,
            'selling_price' => 45,
            'income_account_id' => $salesRevenue->id,
            'cogs_account_id' => $cogs->id,
            'inventory_account_id' => $inventory->id,
            'low_stock_threshold' => 10,
            'is_active' => true,
            'opening_quantity' => 100,
            'opening_date' => now()->subDays(14)->toDateString(),
        ]);

        app(CreateProduct::class)->handle([
            'sku' => 'CONS-001',
            'name' => 'Consulting Hour',
            'product_category_id' => $consulting->id,
            'type' => 'service',
            'unit' => 'hour',
            'purchase_price' => 0,
            'selling_price' => 150,
            'income_account_id' => $serviceRevenue->id,
            'is_active' => true,
        ]);

        // Demo stock adjustment: a physical count found 5 fewer units than
        // the system expected (shrinkage), exercising the real accounting
        // posting (Dr COGS / Cr Inventory).
        app(AdjustStock::class)->handle([
            'product_id' => $widget->id,
            'counted_quantity' => 95,
            'date' => now()->subDays(1)->toDateString(),
            'reference' => 'Demo physical count',
            'notes' => 'Demo stock adjustment seeded for verification purposes.',
            'created_by' => null,
        ]);
    }
}
