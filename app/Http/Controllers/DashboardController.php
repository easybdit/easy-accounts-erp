<?php

namespace App\Http\Controllers;

use App\Models\Accounting\Account;
use App\Models\Accounting\JournalEntry;
use App\Models\Contacts\Customer;
use App\Models\Contacts\Vendor;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    /**
     * A QuickBooks-style "money at a glance" panel: bank balance, this
     * month's income/expense/profit, and outstanding AR/AP — all derived
     * from already-posted data via the same bulk-query patterns as the
     * Reports module (Section 54: no N+1).
     */
    public function index(): Response
    {
        $today = now()->toDateString();
        $monthStart = now()->startOfMonth()->toDateString();

        $bankBalance = Account::query()
            ->where('is_bank_account', true)
            ->where('is_active', true)
            ->get()
            ->reduce(fn (string $carry, Account $account) => bcadd($carry, $account->balanceAsOf($today), 4), '0.0000');

        $incomeExpense = $this->accountAmountsByType(['income', 'expense'], $monthStart, $today);
        $totalIncome = $incomeExpense->where('type', 'income')->reduce(fn (string $c, array $r) => bcadd($c, $r['amount'], 4), '0.0000');
        $totalExpense = $incomeExpense->where('type', 'expense')->reduce(fn (string $c, array $r) => bcadd($c, $r['amount'], 4), '0.0000');

        $arOutstanding = Customer::query()
            ->where('is_active', true)
            ->withSum('journalEntries as entries_debit', 'debit')
            ->withSum('journalEntries as entries_credit', 'credit')
            ->get()
            ->reduce(fn (string $carry, Customer $customer) => bcadd($carry, bcsub(
                bcadd((string) $customer->opening_balance, (string) ($customer->entries_debit ?? '0.0000'), 4),
                (string) ($customer->entries_credit ?? '0.0000'),
                4
            ), 4), '0.0000');

        $apOutstanding = Vendor::query()
            ->where('is_active', true)
            ->withSum('journalEntries as entries_debit', 'debit')
            ->withSum('journalEntries as entries_credit', 'credit')
            ->get()
            ->reduce(fn (string $carry, Vendor $vendor) => bcadd($carry, bcsub(
                bcadd((string) $vendor->opening_balance, (string) ($vendor->entries_credit ?? '0.0000'), 4),
                (string) ($vendor->entries_debit ?? '0.0000'),
                4
            ), 4), '0.0000');

        return Inertia::render('Dashboard', [
            'kpis' => [
                'bankBalance' => $bankBalance,
                'monthIncome' => $totalIncome,
                'monthExpense' => $totalExpense,
                'monthProfit' => bcsub($totalIncome, $totalExpense, 4),
                'arOutstanding' => $arOutstanding,
                'apOutstanding' => $apOutstanding,
            ],
            'trend' => $this->monthlyIncomeExpenseTrend(),
        ]);
    }

    /**
     * Income vs Expense for each of the last $months calendar months
     * (oldest first), grouped in PHP rather than a driver-specific SQL
     * date-group-by so this stays portable across MySQL/MariaDB and the
     * SQLite test database (Section 75 Portability Audit).
     *
     * @return array<int, array{month:string,income:string,expense:string}>
     */
    private function monthlyIncomeExpenseTrend(int $months = 6): array
    {
        $start = now()->subMonths($months - 1)->startOfMonth();
        $end = now()->endOfMonth();

        $accountTypes = Account::query()
            ->whereIn('type', ['income', 'expense'])
            ->where('is_active', true)
            ->pluck('type', 'id');

        $buckets = [];
        for ($i = $months - 1; $i >= 0; $i--) {
            $cursor = now()->subMonths($i);
            $buckets[$cursor->format('Y-m')] = [
                'month' => $cursor->format('M Y'),
                'income' => '0.0000',
                'expense' => '0.0000',
            ];
        }

        if ($accountTypes->isEmpty()) {
            return array_values($buckets);
        }

        JournalEntry::query()
            ->whereIn('account_id', $accountTypes->keys())
            ->whereDate('date', '>=', $start->toDateString())
            ->whereDate('date', '<=', $end->toDateString())
            ->get(['account_id', 'date', 'debit', 'credit'])
            ->each(function (JournalEntry $entry) use (&$buckets, $accountTypes) {
                $key = $entry->date->format('Y-m');

                if (! isset($buckets[$key])) {
                    return;
                }

                $type = $accountTypes[$entry->account_id];
                $debit = (string) $entry->debit;
                $credit = (string) $entry->credit;

                if ($type === 'income') {
                    $buckets[$key]['income'] = bcadd($buckets[$key]['income'], bcsub($credit, $debit, 4), 4);
                } else {
                    $buckets[$key]['expense'] = bcadd($buckets[$key]['expense'], bcsub($debit, $credit, 4), 4);
                }
            });

        return array_values($buckets);
    }

    /**
     * @return Collection<int, array{type:string,amount:string}>
     */
    private function accountAmountsByType(array $types, ?string $from, ?string $to): Collection
    {
        return Account::query()
            ->whereIn('type', $types)
            ->where('is_active', true)
            ->withSum(['journalEntries as period_debit' => fn ($q) => $q->when($from, fn ($q2) => $q2->whereDate('date', '>=', $from))->when($to, fn ($q2) => $q2->whereDate('date', '<=', $to))], 'debit')
            ->withSum(['journalEntries as period_credit' => fn ($q) => $q->when($from, fn ($q2) => $q2->whereDate('date', '>=', $from))->when($to, fn ($q2) => $q2->whereDate('date', '<=', $to))], 'credit')
            ->get()
            ->map(function (Account $account) {
                $debit = (string) ($account->period_debit ?? '0.0000');
                $credit = (string) ($account->period_credit ?? '0.0000');
                $amount = $account->type === 'income' ? bcsub($credit, $debit, 4) : bcsub($debit, $credit, 4);

                return ['type' => $account->type, 'amount' => $amount];
            });
    }
}
