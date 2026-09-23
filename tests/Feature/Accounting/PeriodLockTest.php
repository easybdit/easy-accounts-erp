<?php

namespace Tests\Feature\Accounting;

use App\Actions\Accounting\PostJournal;
use App\Models\Accounting\Account;
use App\Models\Accounting\AccountingSettings;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class PeriodLockTest extends TestCase
{
    use RefreshDatabase;

    private function twoLines(): array
    {
        $cash = Account::factory()->create(['type' => 'asset']);
        $revenue = Account::factory()->create(['type' => 'income']);

        return [
            ['account_id' => $cash->id, 'debit' => 100, 'credit' => 0],
            ['account_id' => $revenue->id, 'debit' => 0, 'credit' => 100],
        ];
    }

    public function test_post_journal_rejects_a_date_on_or_before_the_lock(): void
    {
        AccountingSettings::current()->update(['locked_through_date' => '2026-06-30']);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('locked accounting period');

        (new PostJournal)->handle([
            'date' => '2026-06-30',
            'reference' => null,
            'description' => null,
            'created_by' => null,
            'lines' => $this->twoLines(),
        ]);
    }

    public function test_post_journal_allows_a_date_after_the_lock(): void
    {
        AccountingSettings::current()->update(['locked_through_date' => '2026-06-30']);

        $journal = (new PostJournal)->handle([
            'date' => '2026-07-01',
            'reference' => null,
            'description' => null,
            'created_by' => null,
            'lines' => $this->twoLines(),
        ]);

        $this->assertNotNull($journal->posted_at);
    }

    public function test_post_journal_allows_any_date_when_unlocked(): void
    {
        $journal = (new PostJournal)->handle([
            'date' => '2020-01-01',
            'reference' => null,
            'description' => null,
            'created_by' => null,
            'lines' => $this->twoLines(),
        ]);

        $this->assertNotNull($journal->posted_at);
    }

    public function test_guest_cannot_view_settings(): void
    {
        $this->get(route('accounting.settings.edit'))->assertRedirect(route('login'));
    }

    public function test_a_user_without_settings_permission_cannot_lock_a_period(): void
    {
        $user = User::factory()->create();
        $user->syncRoles(['Sales']);

        $this->actingAs($user)->put(route('accounting.settings.update'), [
            'locked_through_date' => '2026-06-30',
        ])->assertForbidden();

        $this->assertNull(AccountingSettings::current()->locked_through_date);
    }

    public function test_an_accountant_can_lock_and_unlock_a_period(): void
    {
        $user = User::factory()->create();
        $user->syncRoles(['Accountant']);

        $this->actingAs($user)->put(route('accounting.settings.update'), [
            'locked_through_date' => '2026-06-30',
        ])->assertRedirect();

        $this->assertSame('2026-06-30', AccountingSettings::current()->fresh()->locked_through_date->toDateString());

        $this->actingAs($user)->put(route('accounting.settings.update'), [
            'locked_through_date' => null,
        ])->assertRedirect();

        $this->assertNull(AccountingSettings::current()->fresh()->locked_through_date);
    }

    public function test_locking_a_period_blocks_posting_a_manual_journal_into_it_with_a_friendly_error(): void
    {
        $user = User::factory()->create();
        AccountingSettings::current()->update(['locked_through_date' => '2026-06-30']);
        $cash = Account::factory()->create(['type' => 'asset']);
        $revenue = Account::factory()->create(['type' => 'income']);

        $response = $this->actingAs($user)->post(route('accounting.journals.store'), [
            'date' => '2026-06-15',
            'lines' => [
                ['account_id' => $cash->id, 'debit' => 100, 'credit' => 0],
                ['account_id' => $revenue->id, 'debit' => 0, 'credit' => 100],
            ],
        ]);

        $response->assertSessionHas('error');
        $this->assertDatabaseCount('journals', 0);
    }
}
