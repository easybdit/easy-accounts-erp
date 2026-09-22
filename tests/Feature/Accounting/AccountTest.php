<?php

namespace Tests\Feature\Accounting;

use App\Models\Accounting\Account;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccountTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_view_accounts(): void
    {
        $this->get(route('accounting.accounts.index'))
            ->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_view_accounts_index(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('accounting.accounts.index'))
            ->assertOk();
    }

    public function test_account_can_be_created(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('accounting.accounts.store'), [
                'code' => '1000',
                'name' => 'Assets',
                'type' => 'asset',
                'parent_id' => null,
                'opening_balance' => 0,
                'is_active' => true,
            ])
            ->assertRedirect(route('accounting.accounts.index'));

        $this->assertDatabaseHas('accounts', [
            'code' => '1000',
            'name' => 'Assets',
            'type' => 'asset',
        ]);
    }

    public function test_account_code_must_be_unique(): void
    {
        Account::factory()->create(['code' => '1000']);
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('accounting.accounts.store'), [
                'code' => '1000',
                'name' => 'Duplicate',
                'type' => 'asset',
            ])
            ->assertSessionHasErrors('code');
    }

    public function test_child_account_must_match_parent_type(): void
    {
        $parent = Account::factory()->create(['type' => 'asset']);
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('accounting.accounts.store'), [
                'code' => '1001',
                'name' => 'Mismatched Child',
                'type' => 'liability',
                'parent_id' => $parent->id,
            ])
            ->assertSessionHasErrors('parent_id');

        $this->assertDatabaseMissing('accounts', ['code' => '1001']);
    }

    public function test_account_can_be_updated(): void
    {
        $account = Account::factory()->create(['name' => 'Old Name']);
        $user = User::factory()->create();

        $this->actingAs($user)
            ->put(route('accounting.accounts.update', $account), [
                'code' => $account->code,
                'name' => 'New Name',
                'type' => $account->type,
                'parent_id' => null,
                'opening_balance' => 0,
                'is_active' => true,
            ])
            ->assertRedirect(route('accounting.accounts.index'));

        $this->assertDatabaseHas('accounts', [
            'id' => $account->id,
            'name' => 'New Name',
        ]);
    }

    public function test_account_parent_cannot_be_its_own_descendant(): void
    {
        $grandparent = Account::factory()->create(['type' => 'asset']);
        $parent = Account::factory()->create(['type' => 'asset', 'parent_id' => $grandparent->id]);
        $user = User::factory()->create();

        $this->actingAs($user)
            ->put(route('accounting.accounts.update', $grandparent), [
                'code' => $grandparent->code,
                'name' => $grandparent->name,
                'type' => 'asset',
                'parent_id' => $parent->id,
                'opening_balance' => 0,
                'is_active' => true,
            ])
            ->assertSessionHasErrors('parent_id');
    }

    public function test_account_with_children_cannot_be_deleted(): void
    {
        $parent = Account::factory()->create(['type' => 'asset']);
        Account::factory()->create(['type' => 'asset', 'parent_id' => $parent->id]);
        $user = User::factory()->create();

        $this->actingAs($user)
            ->delete(route('accounting.accounts.destroy', $parent))
            ->assertSessionHasErrors('account');

        $this->assertDatabaseHas('accounts', ['id' => $parent->id]);
    }

    public function test_account_without_children_can_be_deleted(): void
    {
        $account = Account::factory()->create();
        $user = User::factory()->create();

        $this->actingAs($user)
            ->delete(route('accounting.accounts.destroy', $account))
            ->assertRedirect(route('accounting.accounts.index'));

        $this->assertDatabaseMissing('accounts', ['id' => $account->id]);
    }
}
