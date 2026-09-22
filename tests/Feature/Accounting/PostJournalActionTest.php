<?php

namespace Tests\Feature\Accounting;

use App\Actions\Accounting\PostJournal;
use App\Models\Accounting\Account;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

/**
 * Accounting invariant tests (Section 64): SUM(debit) = SUM(credit) for
 * every posted journal, and no partial posting on failure.
 */
class PostJournalActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_posts_a_balanced_journal(): void
    {
        $cash = Account::factory()->create(['type' => 'asset']);
        $revenue = Account::factory()->create(['type' => 'income']);

        $journal = (new PostJournal)->handle([
            'date' => '2026-09-22',
            'reference' => null,
            'description' => null,
            'created_by' => null,
            'lines' => [
                ['account_id' => $cash->id, 'debit' => 250.5, 'credit' => 0],
                ['account_id' => $revenue->id, 'debit' => 0, 'credit' => 250.5],
            ],
        ]);

        $this->assertNotNull($journal->posted_at);
        $this->assertTrue($journal->fresh()->isBalanced());
        $this->assertDatabaseCount('journal_entries', 2);
    }

    public function test_it_rejects_an_unbalanced_journal_before_writing_anything(): void
    {
        $cash = Account::factory()->create(['type' => 'asset']);
        $revenue = Account::factory()->create(['type' => 'income']);

        $this->expectException(RuntimeException::class);

        try {
            (new PostJournal)->handle([
                'date' => '2026-09-22',
                'reference' => null,
                'description' => null,
                'created_by' => null,
                'lines' => [
                    ['account_id' => $cash->id, 'debit' => 100, 'credit' => 0],
                    ['account_id' => $revenue->id, 'debit' => 0, 'credit' => 99],
                ],
            ]);
        } finally {
            $this->assertDatabaseCount('journals', 0);
            $this->assertDatabaseCount('journal_entries', 0);
        }
    }

    public function test_a_failure_mid_posting_leaves_no_partial_journal(): void
    {
        $cash = Account::factory()->create(['type' => 'asset']);
        $nonExistentAccountId = Account::query()->max('id') + 1000;

        try {
            (new PostJournal)->handle([
                'date' => '2026-09-22',
                'reference' => null,
                'description' => null,
                'created_by' => null,
                'lines' => [
                    ['account_id' => $cash->id, 'debit' => 100, 'credit' => 0],
                    ['account_id' => $nonExistentAccountId, 'debit' => 0, 'credit' => 100],
                ],
            ]);
            $this->fail('Expected a database constraint violation for the non-existent account.');
        } catch (QueryException $e) {
            $this->assertDatabaseCount('journals', 0);
            $this->assertDatabaseCount('journal_entries', 0);
        }
    }
}
