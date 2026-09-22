<?php

namespace Tests\Feature\Banking;

use App\Models\Accounting\Account;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BankAccountOverviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_view_bank_accounts(): void
    {
        $this->get(route('banking.accounts.index'))->assertRedirect(route('login'));
    }

    public function test_only_accounts_marked_as_bank_accounts_are_listed(): void
    {
        $user = User::factory()->create();
        $bank = Account::factory()->create(['type' => 'asset', 'is_bank_account' => true, 'opening_balance' => 500]);
        $receivable = Account::factory()->create(['type' => 'asset', 'is_bank_account' => false]);

        $response = $this->actingAs($user)->get(route('banking.accounts.index'));
        $accounts = $response->viewData('page')['props']['accounts'];

        $ids = collect($accounts)->pluck('id');
        $this->assertTrue($ids->contains($bank->id));
        $this->assertFalse($ids->contains($receivable->id));
    }

    public function test_only_an_asset_account_can_be_marked_as_a_bank_account(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('accounting.accounts.store'), [
            'code' => '9999',
            'name' => 'Bad Bank Flag',
            'type' => 'expense',
            'is_bank_account' => true,
        ]);

        $response->assertSessionHasErrors('is_bank_account');
        $this->assertDatabaseMissing('accounts', ['code' => '9999']);
    }
}
