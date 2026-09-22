<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Http\Requests\Accounting\StoreAccountRequest;
use App\Http\Requests\Accounting\UpdateAccountRequest;
use App\Models\Accounting\Account;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AccountController extends Controller
{
    public function index(Request $request): Response
    {
        $accounts = Account::query()
            ->with('parent:id,code,name')
            ->when($request->string('search')->toString(), function ($query, $search) {
                $query->where(function ($query) use ($search) {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('code', 'like', "%{$search}%");
                });
            })
            ->when($request->string('type')->toString(), fn ($query, $type) => $query->where('type', $type))
            ->orderBy('code')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Accounting/Accounts/Index', [
            'accounts' => $accounts,
            'filters' => $request->only(['search', 'type']),
            'types' => Account::TYPES,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Accounting/Accounts/Create', [
            'accounts' => Account::query()->select('id', 'code', 'name', 'type')->orderBy('code')->get(),
            'types' => Account::TYPES,
        ]);
    }

    public function store(StoreAccountRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['cash_flow_category'] ??= Account::defaultCashFlowCategory($data['type']);

        Account::create($data);

        return redirect()->route('accounting.accounts.index')
            ->with('success', 'Account created.');
    }

    public function edit(Account $account): Response
    {
        return Inertia::render('Accounting/Accounts/Edit', [
            'account' => $account,
            'accounts' => Account::query()
                ->select('id', 'code', 'name', 'type')
                ->where('id', '!=', $account->id)
                ->orderBy('code')
                ->get(),
            'types' => Account::TYPES,
        ]);
    }

    public function update(UpdateAccountRequest $request, Account $account): RedirectResponse
    {
        $data = $request->validated();
        $data['cash_flow_category'] ??= Account::defaultCashFlowCategory($data['type']);

        $account->update($data);

        return redirect()->route('accounting.accounts.index')
            ->with('success', 'Account updated.');
    }

    public function destroy(Account $account): RedirectResponse
    {
        if ($account->children()->exists()) {
            return back()->with('error', 'This account has child accounts and cannot be deleted.');
        }

        if ($account->journalEntries()->exists()) {
            return back()->with('error', 'This account has posted journal entries and cannot be deleted.');
        }

        $account->delete();

        return redirect()->route('accounting.accounts.index')
            ->with('success', 'Account deleted.');
    }
}
