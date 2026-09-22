<?php

namespace Tests\Feature\Security;

use App\Models\Accounting\Account;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PermissionGatingTest extends TestCase
{
    use RefreshDatabase;

    public function test_viewer_role_can_see_index_pages(): void
    {
        $user = User::factory()->create();
        $user->syncRoles(['Viewer']);

        $this->actingAs($user)->get(route('accounting.accounts.index'))->assertOk();
        $this->actingAs($user)->get(route('reports.index'))->assertOk();
    }

    public function test_viewer_role_is_blocked_from_manage_actions(): void
    {
        $user = User::factory()->create();
        $user->syncRoles(['Viewer']);

        $this->actingAs($user)->get(route('accounting.accounts.create'))->assertForbidden();

        $this->actingAs($user)->post(route('accounting.accounts.store'), [
            'code' => '9999',
            'name' => 'Should Not Be Created',
            'type' => 'asset',
        ])->assertForbidden();

        $this->assertDatabaseMissing('accounts', ['code' => '9999']);
    }

    /**
     * Viewer is granted every "*.view" permission by design (RoleSeeder), so
     * it can see Security index pages read-only, but has no "*.manage"
     * permissions at all, so it cannot reach role/user management actions.
     */
    public function test_viewer_role_can_view_but_not_manage_the_security_module(): void
    {
        $user = User::factory()->create();
        $user->syncRoles(['Viewer']);

        $this->actingAs($user)->get(route('security.users.index'))->assertOk();
        $this->actingAs($user)->get(route('security.roles.index'))->assertOk();
        $this->actingAs($user)->get(route('security.audit-log.index'))->assertOk();

        $this->actingAs($user)->get(route('security.roles.create'))->assertForbidden();
        $this->actingAs($user)->post(route('security.roles.store'), ['name' => 'Should Not Be Created'])
            ->assertForbidden();

        $this->assertDatabaseMissing('roles', ['name' => 'Should Not Be Created']);
    }

    public function test_administrator_can_manage_every_module(): void
    {
        $user = User::factory()->create();
        $user->syncRoles(['Administrator']);

        $this->actingAs($user)->get(route('accounting.accounts.create'))->assertOk();
        $this->actingAs($user)->get(route('security.users.index'))->assertOk();
        $this->actingAs($user)->get(route('security.roles.index'))->assertOk();
        $this->actingAs($user)->get(route('security.audit-log.index'))->assertOk();
    }

    public function test_sales_role_cannot_manage_bills(): void
    {
        $user = User::factory()->create();
        $user->syncRoles(['Sales']);
        $payable = Account::factory()->create(['type' => 'liability']);

        $this->actingAs($user)->get(route('purchases.bills.index'))->assertForbidden();

        $this->actingAs($user)->post(route('purchases.bills.store'), [
            'vendor_id' => 1,
            'payable_account_id' => $payable->id,
            'bill_date' => '2026-01-01',
            'items' => [],
        ])->assertForbidden();
    }

    public function test_user_with_no_role_is_blocked_from_the_dashboard(): void
    {
        $user = User::factory()->create();
        $user->syncRoles([]);

        $this->actingAs($user)->get(route('dashboard'))->assertForbidden();
    }
}
