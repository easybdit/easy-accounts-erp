<?php

namespace App\Http\Controllers\Banking;

use App\Actions\Banking\ReconcileAccount;
use App\Http\Controllers\Controller;
use App\Models\Accounting\Account;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use RuntimeException;

class ReconciliationController extends Controller
{
    public function __construct(private ReconcileAccount $reconcileAccount) {}

    public function index(Account $account, Request $request): Response
    {
        abort_unless($account->is_bank_account, 404);

        $statementDate = $request->date('statement_date')?->toDateString() ?? now()->toDateString();

        $entries = $account->journalEntries()
            ->whereNull('reconciled_at')
            ->whereDate('date', '<=', $statementDate)
            ->with('journal:id,reference,description')
            ->orderBy('date')
            ->orderBy('id')
            ->get()
            ->map(fn ($entry) => [
                'id' => $entry->id,
                'date' => $entry->date->toDateString(),
                'reference' => $entry->journal?->reference,
                'description' => $entry->description ?? $entry->journal?->description,
                'debit' => (string) $entry->debit,
                'credit' => (string) $entry->credit,
            ]);

        $history = $account->reconciliations()
            ->latest('statement_date')
            ->withCount('entries')
            ->take(10)
            ->get(['id', 'statement_date', 'statement_balance', 'reconciled_at'])
            ->map(fn ($reconciliation) => [
                'id' => $reconciliation->id,
                'statement_date' => $reconciliation->statement_date->toDateString(),
                'statement_balance' => (string) $reconciliation->statement_balance,
                'entries_count' => $reconciliation->entries_count,
            ]);

        $beginningBalance = $this->reconcileAccount->reconciledBalanceAsOf($account, $statementDate);

        return Inertia::render('Banking/Reconciliation/Index', [
            'account' => ['id' => $account->id, 'code' => $account->code, 'name' => $account->name],
            'statementDate' => $statementDate,
            'beginningBalance' => $beginningBalance,
            'entries' => $entries,
            'history' => $history,
        ]);
    }

    public function store(Account $account, Request $request): RedirectResponse
    {
        abort_unless($account->is_bank_account, 404);

        $validated = $request->validate([
            'statement_date' => ['required', 'date'],
            'statement_balance' => ['required', 'numeric'],
            'entry_ids' => ['required', 'array', 'min:1'],
            'entry_ids.*' => ['integer', 'exists:journal_entries,id'],
        ]);

        try {
            $this->reconcileAccount->handle(
                $account,
                $validated['statement_date'],
                (string) $validated['statement_balance'],
                $validated['entry_ids'],
                $request->user()->id,
            );
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage())->withInput();
        }

        return redirect()->route('banking.reconciliation.index', [
            'account' => $account,
            'statement_date' => now()->toDateString(),
        ])->with('success', 'Account reconciled.');
    }
}
