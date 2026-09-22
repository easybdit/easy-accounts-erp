<?php

namespace App\Http\Controllers\Banking;

use App\Http\Controllers\Controller;
use App\Models\Accounting\Account;
use Inertia\Inertia;
use Inertia\Response;

/**
 * A purpose-built "Bank Accounts" overview (Section 31), distinct from the
 * generic Chart of Accounts page. Reuses the existing General Ledger for
 * per-account transaction history rather than duplicating it.
 */
class BankAccountController extends Controller
{
    public function index(): Response
    {
        $accounts = Account::query()
            ->where('type', 'asset')
            ->where('is_active', true)
            ->where('is_bank_account', true)
            ->orderBy('code')
            ->get()
            ->map(fn (Account $account) => [
                'id' => $account->id,
                'code' => $account->code,
                'name' => $account->name,
                'balance' => $account->balanceAsOf(),
            ]);

        return Inertia::render('Banking/Accounts/Index', [
            'accounts' => $accounts,
        ]);
    }
}
