<?php

namespace Tests\Feature\Accounting;

use App\Models\Accounting\Account;
use App\Models\Accounting\Journal;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class JournalTest extends TestCase
{
    use RefreshDatabase;

    private function payload(array $overrides = []): array
    {
        $cash = Account::factory()->create(['type' => 'asset', 'is_active' => true]);
        $revenue = Account::factory()->create(['type' => 'income', 'is_active' => true]);

        return array_merge([
            'date' => '2026-09-22',
            'reference' => 'REF-1',
            'description' => 'Cash sale',
            'lines' => [
                ['account_id' => $cash->id, 'debit' => 100, 'credit' => 0, 'description' => null],
                ['account_id' => $revenue->id, 'debit' => 0, 'credit' => 100, 'description' => null],
            ],
        ], $overrides);
    }

    public function test_guest_cannot_view_journal(): void
    {
        $this->get(route('accounting.journals.index'))->assertRedirect(route('login'));
    }

    public function test_balanced_journal_can_be_posted(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('accounting.journals.store'), $this->payload());

        $response->assertRedirect();
        $this->assertDatabaseCount('journals', 1);
        $this->assertDatabaseCount('journal_entries', 2);
    }

    public function test_unbalanced_journal_is_rejected_and_nothing_is_persisted(): void
    {
        $user = User::factory()->create();
        $cash = Account::factory()->create(['type' => 'asset']);
        $revenue = Account::factory()->create(['type' => 'income']);

        $response = $this->actingAs($user)->post(route('accounting.journals.store'), [
            'date' => '2026-09-22',
            'lines' => [
                ['account_id' => $cash->id, 'debit' => 100, 'credit' => 0],
                ['account_id' => $revenue->id, 'debit' => 0, 'credit' => 50],
            ],
        ]);

        $response->assertSessionHasErrors('lines');
        $this->assertDatabaseCount('journals', 0);
        $this->assertDatabaseCount('journal_entries', 0);
    }

    public function test_line_cannot_have_both_debit_and_credit(): void
    {
        $user = User::factory()->create();
        $a = Account::factory()->create(['type' => 'asset']);
        $b = Account::factory()->create(['type' => 'income']);

        $response = $this->actingAs($user)->post(route('accounting.journals.store'), [
            'date' => '2026-09-22',
            'lines' => [
                ['account_id' => $a->id, 'debit' => 100, 'credit' => 100],
                ['account_id' => $b->id, 'debit' => 0, 'credit' => 100],
            ],
        ]);

        $response->assertSessionHasErrors('lines.0');
        $this->assertDatabaseCount('journals', 0);
    }

    public function test_line_cannot_have_neither_debit_nor_credit(): void
    {
        $user = User::factory()->create();
        $a = Account::factory()->create(['type' => 'asset']);
        $b = Account::factory()->create(['type' => 'income']);

        $response = $this->actingAs($user)->post(route('accounting.journals.store'), [
            'date' => '2026-09-22',
            'lines' => [
                ['account_id' => $a->id, 'debit' => 0, 'credit' => 0],
                ['account_id' => $b->id, 'debit' => 0, 'credit' => 100],
            ],
        ]);

        $response->assertSessionHasErrors('lines.0');
    }

    public function test_journal_requires_at_least_two_lines(): void
    {
        $user = User::factory()->create();
        $a = Account::factory()->create(['type' => 'asset']);

        $response = $this->actingAs($user)->post(route('accounting.journals.store'), [
            'date' => '2026-09-22',
            'lines' => [
                ['account_id' => $a->id, 'debit' => 100, 'credit' => 0],
            ],
        ]);

        $response->assertSessionHasErrors('lines');
    }

    public function test_cannot_post_to_inactive_account(): void
    {
        $user = User::factory()->create();
        $active = Account::factory()->create(['type' => 'asset', 'is_active' => true]);
        $inactive = Account::factory()->create(['type' => 'income', 'is_active' => false]);

        $response = $this->actingAs($user)->post(route('accounting.journals.store'), [
            'date' => '2026-09-22',
            'lines' => [
                ['account_id' => $active->id, 'debit' => 100, 'credit' => 0],
                ['account_id' => $inactive->id, 'debit' => 0, 'credit' => 100],
            ],
        ]);

        $response->assertSessionHasErrors('lines.1.account_id');
        $this->assertDatabaseCount('journals', 0);
    }

    public function test_journal_cannot_be_entirely_zero(): void
    {
        $user = User::factory()->create();
        $a = Account::factory()->create(['type' => 'asset']);
        $b = Account::factory()->create(['type' => 'income']);

        $response = $this->actingAs($user)->post(route('accounting.journals.store'), [
            'date' => '2026-09-22',
            'lines' => [
                ['account_id' => $a->id, 'debit' => 0, 'credit' => 0],
                ['account_id' => $b->id, 'debit' => 0, 'credit' => 0],
            ],
        ]);

        $response->assertSessionHasErrors();
        $this->assertDatabaseCount('journals', 0);
    }

    public function test_account_with_journal_entries_cannot_be_deleted(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->post(route('accounting.journals.store'), $this->payload());

        $account = Account::first();

        $this->actingAs($user)
            ->delete(route('accounting.accounts.destroy', $account))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('accounts', ['id' => $account->id]);
    }

    public function test_journal_index_and_show_pages_render(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->post(route('accounting.journals.store'), $this->payload());
        $journal = Journal::first();

        $this->actingAs($user)->get(route('accounting.journals.index'))->assertOk();
        $this->actingAs($user)->get(route('accounting.journals.show', $journal))->assertOk();
    }

    public function test_no_edit_or_delete_routes_exist_for_posted_journals(): void
    {
        $this->assertFalse(Route::has('accounting.journals.edit'));
        $this->assertFalse(Route::has('accounting.journals.update'));
        $this->assertFalse(Route::has('accounting.journals.destroy'));
    }

    public function test_voiding_a_journal_posts_an_exact_opposite_reversal_and_nets_the_account_to_zero(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->post(route('accounting.journals.store'), $this->payload());
        $journal = Journal::first();
        $cashAccount = $journal->entries()->where('debit', '>', 0)->first()->account;

        $this->actingAs($user)->post(route('accounting.journals.void', $journal))
            ->assertRedirect(route('accounting.journals.show', $journal));

        $journal->refresh();
        $this->assertNotNull($journal->voided_at);
        $this->assertDatabaseCount('journals', 2);

        $reversal = Journal::where('reversal_of_journal_id', $journal->id)->first();
        $this->assertNotNull($reversal);
        $this->assertTrue($reversal->isBalanced());
        $this->assertSame('0.0000', $cashAccount->fresh()->balanceAsOf(now()->toDateString()));

        // The original entries are never touched (Section 20 immutability).
        $this->assertDatabaseCount('journal_entries', 4);
    }

    public function test_a_voided_journal_cannot_be_voided_again(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->post(route('accounting.journals.store'), $this->payload());
        $journal = Journal::first();
        $this->actingAs($user)->post(route('accounting.journals.void', $journal));

        $this->actingAs($user)->post(route('accounting.journals.void', $journal->fresh()))
            ->assertSessionHas('error');

        $this->assertDatabaseCount('journals', 2);
    }

    public function test_a_reversal_journal_itself_cannot_be_voided(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->post(route('accounting.journals.store'), $this->payload());
        $journal = Journal::first();
        $this->actingAs($user)->post(route('accounting.journals.void', $journal));
        $reversal = Journal::where('reversal_of_journal_id', $journal->id)->first();

        $this->actingAs($user)->post(route('accounting.journals.void', $reversal))
            ->assertSessionHas('error');

        $this->assertDatabaseCount('journals', 2);
    }

    public function test_a_journal_generated_behind_an_invoice_cannot_be_voided_here(): void
    {
        $user = User::factory()->create();
        $customer = \App\Models\Contacts\Customer::factory()->create();
        $receivable = Account::factory()->create(['type' => 'asset']);
        $income = Account::factory()->create(['type' => 'income']);
        $invoice = \App\Models\Sales\Invoice::factory()->create([
            'customer_id' => $customer->id,
            'receivable_account_id' => $receivable->id,
            'status' => 'draft',
            'total' => 0,
        ]);
        $invoice->items()->create([
            'account_id' => $income->id,
            'description' => 'Line',
            'quantity' => 1,
            'unit_price' => 100,
            'discount' => 0,
            'line_total' => 100,
        ]);
        (new \App\Actions\Sales\PostInvoice(new \App\Actions\Accounting\PostJournal))->handle($invoice->fresh());
        $journal = $invoice->fresh()->journal;

        $this->actingAs($user)->post(route('accounting.journals.void', $journal))
            ->assertSessionHas('error');

        $this->assertDatabaseCount('journals', 1);
    }
}
