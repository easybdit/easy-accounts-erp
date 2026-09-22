<?php

namespace App\Actions\Banking;

use App\Models\Accounting\Account;
use App\Models\Accounting\JournalEntry;
use App\Models\Banking\BankReconciliation;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Completes a bank reconciliation "session": the selected, still-
 * unreconciled entries must sum (added to the account's already-reconciled
 * balance) to exactly the statement's ending balance, or nothing is saved.
 * Once saved, every reconciled entry's reconciled_at is set permanently —
 * a later reconciliation session only ever offers entries that are still
 * null, which is what "locks" a prior reconciled period (Section 90 Phase
 * 7 open question, now resolved this way).
 */
class ReconcileAccount
{
    public function handle(Account $account, string $statementDate, string $statementBalance, array $entryIds, ?int $userId): BankReconciliation
    {
        return DB::transaction(function () use ($account, $statementDate, $statementBalance, $entryIds, $userId) {
            $entries = JournalEntry::query()
                ->whereIn('id', $entryIds)
                ->where('account_id', $account->id)
                ->whereNull('reconciled_at')
                ->get();

            if ($entries->count() !== count($entryIds)) {
                throw new RuntimeException('One or more selected entries have already been reconciled elsewhere. Refresh and try again.');
            }

            $beginningBalance = $this->reconciledBalanceAsOf($account, $statementDate);
            $clearedAmount = $this->netInNormalDirection($account, $entries);
            $computedBalance = bcadd($beginningBalance, $clearedAmount, 4);

            if (bccomp($computedBalance, $statementBalance, 4) !== 0) {
                $difference = bcsub($statementBalance, $computedBalance, 4);

                throw new RuntimeException(
                    "The selected entries total {$computedBalance}, which is {$difference} away from the statement balance of {$statementBalance}."
                );
            }

            $reconciliation = BankReconciliation::create([
                'account_id' => $account->id,
                'statement_date' => $statementDate,
                'statement_balance' => $statementBalance,
                'reconciled_at' => now(),
                'created_by' => $userId,
            ]);

            JournalEntry::whereIn('id', $entryIds)->update([
                'reconciled_at' => now(),
                'bank_reconciliation_id' => $reconciliation->id,
            ]);

            return $reconciliation;
        });
    }

    /**
     * The account's balance built from already-reconciled entries only
     * (plus opening balance) — the reconciliation's "beginning balance".
     * Public so the controller can show it before a session is submitted.
     */
    public function reconciledBalanceAsOf(Account $account, string $asOf): string
    {
        $row = $account->journalEntries()
            ->whereNotNull('reconciled_at')
            ->whereDate('date', '<=', $asOf)
            ->selectRaw('COALESCE(SUM(debit), 0) as debit, COALESCE(SUM(credit), 0) as credit')
            ->first();

        $debit = (string) ($row->debit ?? '0.0000');
        $credit = (string) ($row->credit ?? '0.0000');
        $movement = $account->normalBalance() === 'debit' ? bcsub($debit, $credit, 4) : bcsub($credit, $debit, 4);

        return bcadd((string) $account->opening_balance, $movement, 4);
    }

    private function netInNormalDirection(Account $account, Collection $entries): string
    {
        $debit = $entries->reduce(fn (string $c, JournalEntry $e) => bcadd($c, (string) $e->debit, 4), '0.0000');
        $credit = $entries->reduce(fn (string $c, JournalEntry $e) => bcadd($c, (string) $e->credit, 4), '0.0000');

        return $account->normalBalance() === 'debit' ? bcsub($debit, $credit, 4) : bcsub($credit, $debit, 4);
    }
}
