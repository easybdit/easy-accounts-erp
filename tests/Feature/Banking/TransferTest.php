<?php

namespace Tests\Feature\Banking;

use App\Models\Accounting\Account;
use App\Models\Banking\Transfer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransferTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_view_transfers(): void
    {
        $this->get(route('banking.transfers.index'))->assertRedirect(route('login'));
    }

    public function test_transfer_is_recorded_and_posted_immediately(): void
    {
        $user = User::factory()->create();
        $cash = Account::factory()->create(['type' => 'asset']);
        $bank = Account::factory()->create(['type' => 'asset']);

        $response = $this->actingAs($user)->post(route('banking.transfers.store'), [
            'from_account_id' => $cash->id,
            'to_account_id' => $bank->id,
            'transfer_date' => '2026-01-10',
            'amount' => 500,
        ]);

        $response->assertRedirect();
        $transfer = Transfer::first();
        $this->assertNotNull($transfer->journal);
        $this->assertTrue($transfer->journal->isBalanced());
        $this->assertSame('500.0000', $transfer->journal->totalDebit());
    }

    public function test_from_and_to_accounts_must_differ(): void
    {
        $user = User::factory()->create();
        $cash = Account::factory()->create(['type' => 'asset']);

        $response = $this->actingAs($user)->post(route('banking.transfers.store'), [
            'from_account_id' => $cash->id,
            'to_account_id' => $cash->id,
            'transfer_date' => '2026-01-10',
            'amount' => 500,
        ]);

        $response->assertSessionHasErrors('from_account_id');
        $this->assertDatabaseCount('transfers', 0);
    }

    public function test_accounts_must_be_asset_type(): void
    {
        $user = User::factory()->create();
        $cash = Account::factory()->create(['type' => 'asset']);
        $expenseAsDestination = Account::factory()->create(['type' => 'expense']);

        $response = $this->actingAs($user)->post(route('banking.transfers.store'), [
            'from_account_id' => $cash->id,
            'to_account_id' => $expenseAsDestination->id,
            'transfer_date' => '2026-01-10',
            'amount' => 500,
        ]);

        $response->assertSessionHasErrors('to_account_id');
    }

    public function test_correct_account_balances_after_transfer(): void
    {
        $user = User::factory()->create();
        $cash = Account::factory()->create(['type' => 'asset', 'opening_balance' => 1000]);
        $bank = Account::factory()->create(['type' => 'asset', 'opening_balance' => 0]);

        $this->actingAs($user)->post(route('banking.transfers.store'), [
            'from_account_id' => $cash->id,
            'to_account_id' => $bank->id,
            'transfer_date' => '2026-01-10',
            'amount' => 300,
        ]);

        $this->assertSame('700.0000', $cash->fresh()->balanceAsOf());
        $this->assertSame('300.0000', $bank->fresh()->balanceAsOf());
    }
}
