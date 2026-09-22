<?php

namespace Tests\Feature\Accounting;

use App\Actions\Accounting\PostJournal;
use App\Models\Accounting\Account;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TrialBalanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_view_trial_balance(): void
    {
        $this->get(route('accounting.trial-balance.index'))->assertRedirect(route('login'));
    }

    public function test_trial_balance_stays_balanced_after_posting(): void
    {
        $user = User::factory()->create();
        $cash = Account::factory()->create(['type' => 'asset']);
        $revenue = Account::factory()->create(['type' => 'income']);

        (new PostJournal)->handle([
            'date' => '2026-01-10',
            'reference' => null,
            'description' => null,
            'created_by' => null,
            'lines' => [
                ['account_id' => $cash->id, 'debit' => 500, 'credit' => 0],
                ['account_id' => $revenue->id, 'debit' => 0, 'credit' => 500],
            ],
        ]);

        $response = $this->actingAs($user)->get(route('accounting.trial-balance.index', ['as_of' => '2026-12-31']));
        $props = $response->viewData('page')['props'];

        $this->assertTrue($props['isBalanced']);
        $this->assertSame('500.0000', $props['totalDebit']);
        $this->assertSame('500.0000', $props['totalCredit']);

        $cashRow = collect($props['accounts'])->firstWhere('id', $cash->id);
        $revenueRow = collect($props['accounts'])->firstWhere('id', $revenue->id);

        $this->assertSame('500.0000', $cashRow['debit']);
        $this->assertSame('0.0000', $cashRow['credit']);
        $this->assertSame('500.0000', $revenueRow['credit']);
        $this->assertSame('0.0000', $revenueRow['debit']);
    }

    public function test_as_of_date_excludes_later_transactions(): void
    {
        $user = User::factory()->create();
        $cash = Account::factory()->create(['type' => 'asset']);
        $revenue = Account::factory()->create(['type' => 'income']);

        (new PostJournal)->handle([
            'date' => '2026-03-01',
            'reference' => null,
            'description' => null,
            'created_by' => null,
            'lines' => [
                ['account_id' => $cash->id, 'debit' => 500, 'credit' => 0],
                ['account_id' => $revenue->id, 'debit' => 0, 'credit' => 500],
            ],
        ]);

        $response = $this->actingAs($user)->get(route('accounting.trial-balance.index', ['as_of' => '2026-01-01']));
        $props = $response->viewData('page')['props'];

        $cashRow = collect($props['accounts'])->firstWhere('id', $cash->id);

        $this->assertSame('0.0000', $cashRow['debit']);
        $this->assertTrue($props['isBalanced']);
    }

    public function test_opening_balance_is_included_and_shown_on_correct_side(): void
    {
        $user = User::factory()->create();
        $payable = Account::factory()->create(['type' => 'liability', 'opening_balance' => 750]);

        $response = $this->actingAs($user)->get(route('accounting.trial-balance.index'));
        $props = $response->viewData('page')['props'];

        $row = collect($props['accounts'])->firstWhere('id', $payable->id);

        $this->assertSame('750.0000', $row['credit']);
        $this->assertSame('0.0000', $row['debit']);
    }
}
