<?php

namespace Tests\Feature\Accounting;

use App\Actions\Accounting\PostDepreciation;
use App\Models\Accounting\Account;
use App\Models\Accounting\FixedAsset;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class FixedAssetTest extends TestCase
{
    use RefreshDatabase;

    private function payload(array $overrides = []): array
    {
        $assetAccount = Account::factory()->create(['type' => 'asset']);
        $accumulatedAccount = Account::factory()->create(['type' => 'asset']);
        $expenseAccount = Account::factory()->create(['type' => 'expense']);

        return array_merge([
            'name' => 'Dell PowerEdge Server',
            'asset_account_id' => $assetAccount->id,
            'accumulated_depreciation_account_id' => $accumulatedAccount->id,
            'depreciation_expense_account_id' => $expenseAccount->id,
            'purchase_date' => '2026-01-01',
            'purchase_cost' => 1200,
            'salvage_value' => 0,
            'useful_life_months' => 12,
        ], $overrides);
    }

    public function test_guest_cannot_view_fixed_assets(): void
    {
        $this->get(route('accounting.fixed-assets.index'))->assertRedirect(route('login'));
    }

    public function test_an_asset_can_be_created(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('accounting.fixed-assets.store'), $this->payload())->assertRedirect();

        $asset = FixedAsset::first();
        $this->assertSame('Dell PowerEdge Server', $asset->name);
        $this->assertSame('active', $asset->status);
        $this->assertSame(0, $asset->months_depreciated);
    }

    public function test_accumulated_depreciation_account_must_differ_from_asset_account(): void
    {
        $user = User::factory()->create();
        $account = Account::factory()->create(['type' => 'asset']);

        $response = $this->actingAs($user)->post(route('accounting.fixed-assets.store'), $this->payload([
            'asset_account_id' => $account->id,
            'accumulated_depreciation_account_id' => $account->id,
        ]));

        $response->assertSessionHasErrors('accumulated_depreciation_account_id');
        $this->assertDatabaseCount('fixed_assets', 0);
    }

    public function test_salvage_value_must_be_less_than_purchase_cost(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('accounting.fixed-assets.store'), $this->payload([
            'purchase_cost' => 1000,
            'salvage_value' => 1000,
        ]));

        $response->assertSessionHasErrors('salvage_value');
    }

    public function test_posting_a_month_of_depreciation_debits_expense_and_credits_accumulated_depreciation(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->post(route('accounting.fixed-assets.store'), $this->payload([
            'purchase_cost' => 1200,
            'useful_life_months' => 12,
        ]));
        $asset = FixedAsset::first();

        $this->actingAs($user)->post(route('accounting.fixed-assets.post-depreciation', $asset))->assertRedirect();

        $asset->refresh();
        $this->assertSame(1, $asset->months_depreciated);
        $this->assertSame('100.0000', $asset->accumulatedDepreciation());
        $this->assertSame('1100.0000', $asset->bookValue());

        $depreciation = $asset->depreciations()->first();
        $this->assertTrue($depreciation->journal->isBalanced());
        $this->assertSame('100.0000', $depreciation->journal->totalDebit());

        $expenseLine = $depreciation->journal->entries->firstWhere('account_id', $asset->depreciation_expense_account_id);
        $this->assertSame('100.0000', $expenseLine->debit);

        $accumulatedLine = $depreciation->journal->entries->firstWhere('account_id', $asset->accumulated_depreciation_account_id);
        $this->assertSame('100.0000', $accumulatedLine->credit);
    }

    public function test_the_same_period_cannot_be_depreciated_twice(): void
    {
        $assetAccount = Account::factory()->create(['type' => 'asset']);
        $accumulatedAccount = Account::factory()->create(['type' => 'asset']);
        $expenseAccount = Account::factory()->create(['type' => 'expense']);
        $asset = FixedAsset::create([
            'name' => 'Router',
            'asset_account_id' => $assetAccount->id,
            'accumulated_depreciation_account_id' => $accumulatedAccount->id,
            'depreciation_expense_account_id' => $expenseAccount->id,
            'purchase_date' => '2026-01-01',
            'purchase_cost' => 600,
            'salvage_value' => 0,
            'useful_life_months' => 6,
            'status' => 'active',
            'months_depreciated' => 0,
        ]);

        app(PostDepreciation::class)->handle($asset, '2026-01-01');

        $this->expectException(RuntimeException::class);
        app(PostDepreciation::class)->handle($asset, '2026-01-01');
    }

    public function test_the_final_month_is_capped_so_total_depreciation_never_exceeds_the_depreciable_base(): void
    {
        $assetAccount = Account::factory()->create(['type' => 'asset']);
        $accumulatedAccount = Account::factory()->create(['type' => 'asset']);
        $expenseAccount = Account::factory()->create(['type' => 'expense']);
        // 1000 / 3 months = 333.3333/mo, so 3 x 333.3333 = 999.9999 — the
        // final month must absorb the leftover 0.0001 rounding remainder.
        $asset = FixedAsset::create([
            'name' => 'Switch',
            'asset_account_id' => $assetAccount->id,
            'accumulated_depreciation_account_id' => $accumulatedAccount->id,
            'depreciation_expense_account_id' => $expenseAccount->id,
            'purchase_date' => '2026-01-01',
            'purchase_cost' => 1000,
            'salvage_value' => 0,
            'useful_life_months' => 3,
            'status' => 'active',
            'months_depreciated' => 0,
        ]);

        app(PostDepreciation::class)->handle($asset, '2026-01-01');
        app(PostDepreciation::class)->handle($asset, '2026-02-01');
        app(PostDepreciation::class)->handle($asset, '2026-03-01');

        $asset->refresh();
        $this->assertSame('1000.0000', $asset->accumulatedDepreciation());
        $this->assertSame('0.0000', $asset->bookValue());
        $this->assertSame('fully_depreciated', $asset->status);

        $this->expectException(RuntimeException::class);
        app(PostDepreciation::class)->handle($asset, '2026-04-01');
    }

    public function test_a_disposed_asset_no_longer_depreciates(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->post(route('accounting.fixed-assets.store'), $this->payload());
        $asset = FixedAsset::first();

        $this->actingAs($user)->post(route('accounting.fixed-assets.dispose', $asset), ['disposal_notes' => 'Sold'])->assertRedirect();

        $asset->refresh();
        $this->assertSame('disposed', $asset->status);
        $this->assertNotNull($asset->disposed_at);

        $this->expectException(RuntimeException::class);
        app(PostDepreciation::class)->handle($asset, '2026-01-01');
    }

    public function test_locked_fields_cannot_change_once_depreciation_has_started(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->post(route('accounting.fixed-assets.store'), $this->payload(['purchase_cost' => 1200, 'useful_life_months' => 12]));
        $asset = FixedAsset::first();
        app(PostDepreciation::class)->handle($asset, '2026-01-01');

        $response = $this->actingAs($user)->put(route('accounting.fixed-assets.update', $asset->fresh()), [
            'name' => $asset->name,
            'asset_account_id' => $asset->asset_account_id,
            'accumulated_depreciation_account_id' => $asset->accumulated_depreciation_account_id,
            'depreciation_expense_account_id' => $asset->depreciation_expense_account_id,
            'purchase_date' => $asset->purchase_date->toDateString(),
            'purchase_cost' => 5000,
            'salvage_value' => 0,
            'useful_life_months' => 12,
        ]);

        $response->assertSessionHas('error');
        $this->assertSame('1200.0000', $asset->fresh()->purchase_cost);
    }

    public function test_an_asset_with_depreciation_cannot_be_deleted(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->post(route('accounting.fixed-assets.store'), $this->payload());
        $asset = FixedAsset::first();
        app(PostDepreciation::class)->handle($asset, '2026-01-01');

        $this->actingAs($user)->delete(route('accounting.fixed-assets.destroy', $asset->fresh()))->assertSessionHas('error');
        $this->assertDatabaseCount('fixed_assets', 1);
    }

    public function test_the_scheduled_command_posts_depreciation_for_every_active_asset(): void
    {
        $assetAccount = Account::factory()->create(['type' => 'asset']);
        $accumulatedAccount = Account::factory()->create(['type' => 'asset']);
        $expenseAccount = Account::factory()->create(['type' => 'expense']);
        FixedAsset::create([
            'name' => 'Server A', 'asset_account_id' => $assetAccount->id,
            'accumulated_depreciation_account_id' => $accumulatedAccount->id,
            'depreciation_expense_account_id' => $expenseAccount->id,
            'purchase_date' => '2026-01-01', 'purchase_cost' => 1200, 'salvage_value' => 0,
            'useful_life_months' => 12, 'status' => 'active', 'months_depreciated' => 0,
        ]);
        FixedAsset::create([
            'name' => 'Server B', 'asset_account_id' => $assetAccount->id,
            'accumulated_depreciation_account_id' => $accumulatedAccount->id,
            'depreciation_expense_account_id' => $expenseAccount->id,
            'purchase_date' => '2026-01-01', 'purchase_cost' => 600, 'salvage_value' => 0,
            'useful_life_months' => 6, 'status' => 'active', 'months_depreciated' => 0,
        ]);

        $this->artisan('assets:post-depreciation', ['--period' => '2026-01-01'])->assertSuccessful();

        $this->assertDatabaseCount('fixed_asset_depreciations', 2);

        // Idempotent: running the same period again posts nothing new.
        $this->artisan('assets:post-depreciation', ['--period' => '2026-01-01'])->assertSuccessful();
        $this->assertDatabaseCount('fixed_asset_depreciations', 2);
    }
}
