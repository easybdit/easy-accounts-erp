<?php

namespace App\Actions\Accounting;

use App\Models\Accounting\AccountingSettings;
use App\Models\Accounting\Journal;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * The single, shared entry point for creating a posted, balanced journal.
 *
 * Every financial transaction (manual journal, and later invoices, bills,
 * payments, expenses per the spec's posting diagrams) must post through
 * this action so that "debit = credit" and atomicity (Section 19) are
 * enforced in exactly one place. The Period Lock check below piggybacks on
 * that same guarantee: every source of a journal is blocked from posting
 * into a closed period without needing to duplicate the check anywhere
 * else.
 */
class PostJournal
{
    /**
     * @param  array{date: string, reference: ?string, description: ?string, created_by: ?int, source_type?: ?string, source_id?: ?int, lines: array<int, array{account_id: int, debit: numeric-string|float|int, credit: numeric-string|float|int, description?: ?string}>}  $data
     */
    public function handle(array $data): Journal
    {
        $lockedThroughDate = AccountingSettings::current()->locked_through_date;

        if ($lockedThroughDate !== null && $lockedThroughDate->toDateString() >= $data['date']) {
            throw new RuntimeException(
                "This date falls within a locked accounting period (locked through {$lockedThroughDate->toDateString()}) and cannot be posted to."
            );
        }

        $lines = $data['lines'];

        $totalDebit = '0.0000';
        $totalCredit = '0.0000';

        foreach ($lines as $line) {
            $totalDebit = bcadd($totalDebit, (string) ($line['debit'] ?? 0), 4);
            $totalCredit = bcadd($totalCredit, (string) ($line['credit'] ?? 0), 4);
        }

        if (bccomp($totalDebit, $totalCredit, 4) !== 0) {
            throw new RuntimeException(
                "Journal is not balanced: total debit ({$totalDebit}) does not equal total credit ({$totalCredit})."
            );
        }

        return DB::transaction(function () use ($data, $lines) {
            $journal = Journal::create([
                'date' => $data['date'],
                'reference' => $data['reference'] ?? null,
                'description' => $data['description'] ?? null,
                'posted_at' => now(),
                'source_type' => $data['source_type'] ?? null,
                'source_id' => $data['source_id'] ?? null,
                'created_by' => $data['created_by'] ?? null,
            ]);

            foreach ($lines as $line) {
                $journal->entries()->create([
                    'account_id' => $line['account_id'],
                    'customer_id' => $line['customer_id'] ?? null,
                    'vendor_id' => $line['vendor_id'] ?? null,
                    'date' => $data['date'],
                    'debit' => $line['debit'] ?? 0,
                    'credit' => $line['credit'] ?? 0,
                    'description' => $line['description'] ?? null,
                ]);
            }

            return $journal->load('entries.account');
        });
    }
}
