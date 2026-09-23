<?php

namespace App\Http\Controllers\Accounting;

use App\Actions\Accounting\SaveBudget;
use App\Http\Controllers\Controller;
use App\Http\Requests\Accounting\StoreBudgetRequest;
use App\Models\Accounting\Account;
use App\Models\Accounting\Budget;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class BudgetController extends Controller
{
    public function index(): Response
    {
        $budgets = Budget::query()
            ->withCount('lines')
            ->withSum('lines as total_amount', 'amount')
            ->orderByDesc('fiscal_year')
            ->orderBy('name')
            ->get()
            ->map(fn (Budget $budget) => [
                'id' => $budget->id,
                'name' => $budget->name,
                'fiscal_year' => $budget->fiscal_year,
                'lines_count' => $budget->lines_count,
                'total_amount' => (string) ($budget->total_amount ?? '0.0000'),
            ]);

        return Inertia::render('Accounting/Budgets/Index', [
            'budgets' => $budgets,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Accounting/Budgets/Create', $this->formOptions());
    }

    public function store(StoreBudgetRequest $request, SaveBudget $action): RedirectResponse
    {
        $budget = $action->handle([
            ...$request->validated(),
            'created_by' => $request->user()->id,
        ]);

        return redirect()->route('accounting.budgets.index')->with('success', "Budget \"{$budget->name}\" saved.");
    }

    public function edit(Budget $budget): Response
    {
        $budget->load('lines');

        return Inertia::render('Accounting/Budgets/Edit', [
            'budget' => [
                'id' => $budget->id,
                'name' => $budget->name,
                'fiscal_year' => $budget->fiscal_year,
                'notes' => $budget->notes,
                'amounts' => $budget->lines->pluck('amount', 'account_id')->map(fn ($amount) => (string) $amount),
            ],
            ...$this->formOptions(),
        ]);
    }

    public function update(StoreBudgetRequest $request, Budget $budget, SaveBudget $action): RedirectResponse
    {
        $action->handle($request->validated(), $budget);

        return redirect()->route('accounting.budgets.index')->with('success', 'Budget updated.');
    }

    public function destroy(Budget $budget): RedirectResponse
    {
        $budget->delete();

        return redirect()->route('accounting.budgets.index')->with('success', 'Budget deleted.');
    }

    private function formOptions(): array
    {
        return [
            'incomeAccounts' => Account::query()->where('is_active', true)->where('type', 'income')
                ->select('id', 'code', 'name')->orderBy('code')->get(),
            'expenseAccounts' => Account::query()->where('is_active', true)->where('type', 'expense')
                ->select('id', 'code', 'name')->orderBy('code')->get(),
        ];
    }
}
