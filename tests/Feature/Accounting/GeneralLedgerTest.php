<?php

namespace Tests\Feature\Accounting;

use App\Actions\Accounting\PostJournal;
use App\Models\Accounting\Account;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GeneralLedgerTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_view_ledger(): void
    {
        $this->get(route('accounting.ledger.index'))->assertRedirect(route('login'));
    }

    public function test_ledger_page_renders_without_an_account_selected(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('accounting.ledger.index'))->assertOk();
    }

    public function test_running_balance_accumulates_correctly_for_a_debit_normal_account(): void
    {
        $user = User::factory()->create();
        $cash = Account::factory()->create(['type' => 'asset', 'opening_balance' => 1000]);
        $revenue = Account::factory()->create(['type' => 'income']);

        $post = new PostJournal;
        $post->handle([
            'date' => '2026-01-10',
            'reference' => null,
            'description' => null,
            'created_by' => $user->id,
            'lines' => [
                ['account_id' => $cash->id, 'debit' => 200, 'credit' => 0],
                ['account_id' => $revenue->id, 'debit' => 0, 'credit' => 200],
            ],
        ]);
        $post->handle([
            'date' => '2026-01-15',
            'reference' => null,
            'description' => null,
            'created_by' => $user->id,
            'lines' => [
                ['account_id' => $revenue->id, 'debit' => 50, 'credit' => 0],
                ['account_id' => $cash->id, 'debit' => 0, 'credit' => 50],
            ],
        ]);

        $response = $this->actingAs($user)->get(route('accounting.ledger.index', ['account_id' => $cash->id]));

        $ledger = $response->viewData('page')['props']['ledger'];

        $this->assertSame('1000.0000', $ledger['starting_balance']);
        $this->assertSame('1200.0000', $ledger['entries'][0]['running_balance']);
        $this->assertSame('1150.0000', $ledger['entries'][1]['running_balance']);
        $this->assertSame('1150.0000', $ledger['ending_balance']);
    }

    public function test_date_range_excludes_out_of_range_entries_but_keeps_starting_balance_correct(): void
    {
        $user = User::factory()->create();
        $cash = Account::factory()->create(['type' => 'asset', 'opening_balance' => 0]);
        $revenue = Account::factory()->create(['type' => 'income']);

        (new PostJournal)->handle([
            'date' => '2026-01-01',
            'reference' => null,
            'description' => null,
            'created_by' => null,
            'lines' => [
                ['account_id' => $cash->id, 'debit' => 300, 'credit' => 0],
                ['account_id' => $revenue->id, 'debit' => 0, 'credit' => 300],
            ],
        ]);
        (new PostJournal)->handle([
            'date' => '2026-02-01',
            'reference' => null,
            'description' => null,
            'created_by' => null,
            'lines' => [
                ['account_id' => $cash->id, 'debit' => 100, 'credit' => 0],
                ['account_id' => $revenue->id, 'debit' => 0, 'credit' => 100],
            ],
        ]);

        $response = $this->actingAs($user)->get(route('accounting.ledger.index', [
            'account_id' => $cash->id,
            'from' => '2026-02-01',
        ]));

        $ledger = $response->viewData('page')['props']['ledger'];

        $this->assertSame('300.0000', $ledger['starting_balance']);
        $this->assertCount(1, $ledger['entries']);
        $this->assertSame('400.0000', $ledger['ending_balance']);
    }
}
