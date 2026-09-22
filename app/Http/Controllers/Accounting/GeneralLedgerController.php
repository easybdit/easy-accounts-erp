<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\Accounting\Account;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class GeneralLedgerController extends Controller
{
    public function index(Request $request): Response
    {
        $accounts = Account::query()
            ->where('is_active', true)
            ->orderBy('code')
            ->get(['id', 'code', 'name']);

        $accountId = $request->integer('account_id') ?: null;
        $from = $request->date('from')?->toDateString();
        $to = $request->date('to')?->toDateString();

        $ledger = null;

        if ($accountId) {
            $account = Account::findOrFail($accountId);

            $startingBalance = $from
                ? $account->balanceAsOf(Carbon::parse($from)->subDay()->toDateString())
                : (string) $account->opening_balance;

            $entries = $account->journalEntries()
                ->with('journal:id,reference,description')
                ->when($from, fn ($query) => $query->where('date', '>=', $from))
                ->when($to, fn ($query) => $query->where('date', '<=', $to))
                ->orderBy('date')
                ->orderBy('id')
                ->get();

            $running = $startingBalance;
            $normalSide = $account->normalBalance();

            $rows = $entries->map(function ($entry) use (&$running, $normalSide) {
                $debit = (string) $entry->debit;
                $credit = (string) $entry->credit;

                $running = $normalSide === 'debit'
                    ? bcsub(bcadd($running, $debit, 4), $credit, 4)
                    : bcsub(bcadd($running, $credit, 4), $debit, 4);

                return [
                    'id' => $entry->id,
                    'date' => $entry->date->toDateString(),
                    'reference' => $entry->journal->reference,
                    'description' => $entry->description ?? $entry->journal->description,
                    'debit' => $debit,
                    'credit' => $credit,
                    'running_balance' => $running,
                    'journal_id' => $entry->journal_id,
                ];
            });

            $ledger = [
                'account' => [
                    'id' => $account->id,
                    'code' => $account->code,
                    'name' => $account->name,
                    'type' => $account->type,
                ],
                'starting_balance' => $startingBalance,
                'ending_balance' => $running,
                'entries' => $rows,
            ];
        }

        return Inertia::render('Accounting/Reports/GeneralLedger', [
            'accounts' => $accounts,
            'ledger' => $ledger,
            'filters' => [
                'account_id' => $accountId,
                'from' => $from,
                'to' => $to,
            ],
        ]);
    }
}
