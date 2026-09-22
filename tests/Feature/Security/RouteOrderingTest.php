<?php

namespace Tests\Feature\Security;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * When a Route::resource() is split into individual routes across separate
 * permission-middleware groups, a wildcard show route ("{model}") registered
 * before a static "create" route swallows "create" as the wildcard
 * parameter, since Laravel matches GET routes in registration order, not
 * specificity. Every route file was ordered index -> create/store -> show
 * to avoid this; these tests assert that "/create" actually resolves to the
 * Create page component rather than failing model binding on "create".
 */
class RouteOrderingTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public static function createRoutes(): array
    {
        return [
            'accounting journals' => ['accounting.journals.create', 'Accounting/Journals/Create'],
            'purchases bills' => ['purchases.bills.create', 'Purchases/Bills/Create'],
            'purchases vendor payments' => ['purchases.vendor-payments.create', 'Purchases/VendorPayments/Create'],
            'expenses entries' => ['expenses.entries.create', 'Expenses/Create'],
            'banking transfers' => ['banking.transfers.create', 'Banking/Transfers/Create'],
            'inventory products' => ['inventory.products.create', 'Inventory/Products/Create'],
            'inventory stock movements' => ['inventory.stock-movements.create', 'Inventory/StockMovements/Create'],
        ];
    }

    #[DataProvider('createRoutes')]
    public function test_create_route_resolves_to_the_create_page(string $routeName, string $component): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route($routeName))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component($component));
    }
}
