<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Concerns\ExportsCsv;
use App\Http\Controllers\Controller;
use App\Models\Accounting\Account;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TrialBalanceController extends Controller
{
    use ExportsCsv;

    public function index(Request $request): Response|StreamedResponse
    {
        $asOf = $request->date('as_of')?->toDateString() ?? now()->toDateString();

        $accounts = Account::query()
            ->where('is_active', true)
            ->withSum(['journalEntries as period_debit' => fn ($query) => $query->whereDate('date', '<=', $asOf)], 'debit')
            ->withSum(['journalEntries as period_credit' => fn ($query) => $query->whereDate('date', '<=', $asOf)], 'credit')
            ->orderBy('code')
            ->get()
            ->map(function (Account $account) {
                $opening = (string) $account->opening_balance;
                $periodDebit = (string) ($account->period_debit ?? '0.0000');
                $periodCredit = (string) ($account->period_credit ?? '0.0000');

                if ($account->normalBalance() === 'debit') {
                    $debitSide = bcadd($opening, $periodDebit, 4);
                    $creditSide = $periodCredit;
                } else {
                    $debitSide = $periodDebit;
                    $creditSide = bcadd($opening, $periodCredit, 4);
                }

                $net = bcsub($debitSide, $creditSide, 4);

                return [
                    'id' => $account->id,
                    'code' => $account->code,
                    'name' => $account->name,
                    'type' => $account->type,
                    'debit' => bccomp($net, '0', 4) > 0 ? $net : '0.0000',
                    'credit' => bccomp($net, '0', 4) < 0 ? bcmul($net, '-1', 4) : '0.0000',
                ];
            });

        $totalDebit = $accounts->reduce(fn (string $carry, array $row) => bcadd($carry, $row['debit'], 4), '0.0000');
        $totalCredit = $accounts->reduce(fn (string $carry, array $row) => bcadd($carry, $row['credit'], 4), '0.0000');

        if ($this->wantsCsv()) {
            return $this->csvResponse('trial-balance.csv', ['Code', 'Account', 'Type', 'Debit', 'Credit'], [
                ...$accounts->map(fn (array $r) => [$r['code'], $r['name'], $r['type'], $r['debit'], $r['credit']]),
                ['', '', 'Total', $totalDebit, $totalCredit],
            ]);
        }

        return Inertia::render('Accounting/Reports/TrialBalance', [
            'accounts' => $accounts->values(),
            'totalDebit' => $totalDebit,
            'totalCredit' => $totalCredit,
            'isBalanced' => bccomp($totalDebit, $totalCredit, 4) === 0,
            'asOf' => $asOf,
        ]);
    }
}
