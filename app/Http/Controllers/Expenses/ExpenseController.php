<?php

namespace App\Http\Controllers\Expenses;

use App\Actions\Expenses\RecordExpense;
use App\Http\Controllers\Controller;
use App\Http\Requests\Expenses\StoreExpenseRequest;
use App\Models\Accounting\Account;
use App\Models\Contacts\Vendor;
use App\Models\Expenses\Expense;
use App\Models\Expenses\ExpenseCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ExpenseController extends Controller
{
    public function index(Request $request): Response
    {
        $expenses = Expense::query()
            ->with('category:id,name')
            ->when($request->string('search')->toString(), function ($query, $search) {
                $query->where(function ($query) use ($search) {
                    $query->where('expense_number', 'like', "%{$search}%")
                        ->orWhere('payee', 'like', "%{$search}%");
                });
            })
            ->orderByDesc('expense_date')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Expenses/Index', [
            'expenses' => $expenses,
            'filters' => $request->only(['search']),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Expenses/Create', $this->formOptions());
    }

    public function store(StoreExpenseRequest $request, RecordExpense $action): RedirectResponse
    {
        $expense = $action->handle([
            ...$request->validated(),
            'created_by' => $request->user()->id,
        ]);

        return redirect()->route('expenses.entries.show', $expense)->with('success', 'Expense recorded.');
    }

    public function show(Expense $expense): Response
    {
        $expense->load(['category:id,name', 'account:id,code,name', 'paymentAccount:id,code,name', 'vendor:id,name', 'journal']);

        return Inertia::render('Expenses/Show', [
            'expense' => $expense,
        ]);
    }

    private function formOptions(): array
    {
        return [
            'categories' => ExpenseCategory::query()->where('is_active', true)->orderBy('name')->get(['id', 'name', 'default_account_id']),
            'expenseAccounts' => Account::query()->where('is_active', true)->where('type', 'expense')
                ->select('id', 'code', 'name')->orderBy('code')->get(),
            'paymentAccounts' => Account::query()->where('is_active', true)->where('type', 'asset')
                ->select('id', 'code', 'name')->orderBy('code')->get(),
            'vendors' => Vendor::query()->where('is_active', true)->select('id', 'name')->orderBy('name')->get(),
        ];
    }
}
