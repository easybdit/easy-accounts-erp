<?php

namespace Tests\Feature\Banking;

use App\Models\Accounting\Account;
use App\Models\Accounting\JournalEntry;
use App\Models\Banking\BankReconciliation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReconciliationTest extends TestCase
{
    use RefreshDatabase;

    private function postJournal($user, Account $bank, Account $other, string $date, string $amount, bool $debitBank = true): void
    {
        $this->actingAs($user)->post(route('accounting.journals.store'), [
            'date' => $date,
            'lines' => $debitBank
                ? [
                    ['account_id' => $bank->id, 'debit' => $amount, 'credit' => 0],
                    ['account_id' => $other->id, 'debit' => 0, 'credit' => $amount],
                ]
                : [
                    ['account_id' => $other->id, 'debit' => $amount, 'credit' => 0],
                    ['account_id' => $bank->id, 'debit' => 0, 'credit' => $amount],
                ],
        ]);
    }

    public function test_guest_cannot_view_the_reconciliation_page(): void
    {
        $bank = Account::factory()->create(['type' => 'asset', 'is_bank_account' => true]);

        $this->get(route('banking.reconciliation.index', $bank))->assertRedirect(route('login'));
    }

    public function test_selecting_entries_that_match_the_statement_balance_completes_the_reconciliation(): void
    {
        $user = User::factory()->create();
        $bank = Account::factory()->create(['type' => 'asset', 'is_bank_account' => true, 'opening_balance' => 0]);
        $income = Account::factory()->create(['type' => 'income']);
        $this->postJournal($user, $bank, $income, '2026-01-05', '500');

        $entry = JournalEntry::where('account_id', $bank->id)->first();

        $response = $this->actingAs($user)->post(route('banking.reconciliation.store', $bank), [
            'statement_date' => '2026-01-31',
            'statement_balance' => 500,
            'entry_ids' => [$entry->id],
        ]);

        $response->assertRedirect();
        $this->assertDatabaseCount('bank_reconciliations', 1);
        $this->assertNotNull($entry->fresh()->reconciled_at);
        $this->assertSame(BankReconciliation::first()->id, $entry->fresh()->bank_reconciliation_id);
    }

    public function test_a_mismatched_statement_balance_is_rejected_and_nothing_is_reconciled(): void
    {
        $user = User::factory()->create();
        $bank = Account::factory()->create(['type' => 'asset', 'is_bank_account' => true, 'opening_balance' => 0]);
        $income = Account::factory()->create(['type' => 'income']);
        $this->postJournal($user, $bank, $income, '2026-01-05', '500');

        $entry = JournalEntry::where('account_id', $bank->id)->first();

        $response = $this->actingAs($user)->post(route('banking.reconciliation.store', $bank), [
            'statement_date' => '2026-01-31',
            'statement_balance' => 450,
            'entry_ids' => [$entry->id],
        ]);

        $response->assertSessionHas('error');
        $this->assertDatabaseCount('bank_reconciliations', 0);
        $this->assertNull($entry->fresh()->reconciled_at);
    }

    public function test_an_already_reconciled_entry_does_not_appear_in_a_later_session(): void
    {
        $user = User::factory()->create();
        $bank = Account::factory()->create(['type' => 'asset', 'is_bank_account' => true, 'opening_balance' => 0]);
        $income = Account::factory()->create(['type' => 'income']);
        $this->postJournal($user, $bank, $income, '2026-01-05', '500');
        $entry = JournalEntry::where('account_id', $bank->id)->first();
        $this->actingAs($user)->post(route('banking.reconciliation.store', $bank), [
            'statement_date' => '2026-01-31',
            'statement_balance' => 500,
            'entry_ids' => [$entry->id],
        ]);

        $this->postJournal($user, $bank, $income, '2026-02-05', '200');

        $response = $this->actingAs($user)->get(route('banking.reconciliation.index', [
            'account' => $bank->id,
            'statement_date' => '2026-02-28',
        ]));
        $props = $response->viewData('page')['props'];

        $this->assertCount(1, $props['entries']);
        $this->assertSame('500.0000', $props['beginningBalance']);
    }

    public function test_a_second_reconciliation_beginning_balance_includes_the_first(): void
    {
        $user = User::factory()->create();
        $bank = Account::factory()->create(['type' => 'asset', 'is_bank_account' => true, 'opening_balance' => 0]);
        $income = Account::factory()->create(['type' => 'income']);
        $this->postJournal($user, $bank, $income, '2026-01-05', '500');
        $firstEntry = JournalEntry::where('account_id', $bank->id)->first();
        $this->actingAs($user)->post(route('banking.reconciliation.store', $bank), [
            'statement_date' => '2026-01-31',
            'statement_balance' => 500,
            'entry_ids' => [$firstEntry->id],
        ]);
        $this->postJournal($user, $bank, $income, '2026-02-05', '200');
        $secondEntry = JournalEntry::where('account_id', $bank->id)->whereNull('reconciled_at')->first();

        $response = $this->actingAs($user)->post(route('banking.reconciliation.store', $bank), [
            'statement_date' => '2026-02-28',
            'statement_balance' => 700,
            'entry_ids' => [$secondEntry->id],
        ]);

        $response->assertRedirect();
        $this->assertDatabaseCount('bank_reconciliations', 2);
        $this->assertNotNull($secondEntry->fresh()->reconciled_at);
    }
}
