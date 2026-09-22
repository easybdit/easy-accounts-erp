<?php

namespace App\Http\Controllers\Expenses;

use App\Actions\Expenses\GenerateExpenseFromRecurring;
use App\Actions\Expenses\SaveRecurringExpense;
use App\Http\Controllers\Concerns\FormatsPlainDates;
use App\Http\Controllers\Controller;
use App\Http\Requests\Expenses\StoreRecurringExpenseRequest;
use App\Models\Accounting\Account;
use App\Models\Contacts\Vendor;
use App\Models\Expenses\ExpenseCategory;
use App\Models\Expenses\RecurringExpense;
use App\Models\Tax\TaxRate;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class RecurringExpenseController extends Controller
{
    use FormatsPlainDates;

    public function index(): Response
    {
        $templates = RecurringExpense::query()
            ->with('category:id,name')
            ->orderBy('name')
            ->get()
            ->map(fn (RecurringExpense $template) => [
                'id' => $template->id,
                'name' => $template->name,
                'category' => ['name' => $template->category->name],
                'payee' => $template->payee,
                'amount' => (string) $template->amount,
                'is_active' => $template->is_active,
                'next_generation_date' => $template->next_generation_date?->toDateString(),
            ]);

        return Inertia::render('Expenses/Recurring/Index', [
            'templates' => $templates,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Expenses/Recurring/Create', $this->formOptions());
    }

    public function store(StoreRecurringExpenseRequest $request, SaveRecurringExpense $action): RedirectResponse
    {
        $template = $action->handle([
            ...$request->validated(),
            'created_by' => $request->user()->id,
        ]);

        return redirect()->route('expenses.recurring.index')->with('success', "Template \"{$template->name}\" saved.");
    }

    public function edit(RecurringExpense $recurringExpense): Response
    {
        return Inertia::render('Expenses/Recurring/Edit', [
            'template' => $this->withPlainDates($recurringExpense, ['next_generation_date']),
            ...$this->formOptions(),
        ]);
    }

    public function update(StoreRecurringExpenseRequest $request, RecurringExpense $recurringExpense, SaveRecurringExpense $action): RedirectResponse
    {
        $action->handle($request->validated(), $recurringExpense);

        return redirect()->route('expenses.recurring.index')->with('success', 'Template updated.');
    }

    public function destroy(RecurringExpense $recurringExpense): RedirectResponse
    {
        $recurringExpense->delete();

        return redirect()->route('expenses.recurring.index')->with('success', 'Template deleted.');
    }

    public function generate(RecurringExpense $recurringExpense, GenerateExpenseFromRecurring $action): RedirectResponse
    {
        $expense = $action->handle($recurringExpense, auth()->id());

        return redirect()->route('expenses.entries.show', $expense)
            ->with('success', "Expense {$expense->expense_number} recorded from \"{$recurringExpense->name}\".");
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
            'taxRates' => TaxRate::query()->where('is_active', true)->select('id', 'name', 'rate')->orderBy('name')->get(),
        ];
    }
}
