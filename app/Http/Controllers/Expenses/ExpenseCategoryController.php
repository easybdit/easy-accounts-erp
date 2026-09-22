<?php

namespace App\Http\Controllers\Expenses;

use App\Http\Controllers\Controller;
use App\Http\Requests\Expenses\StoreExpenseCategoryRequest;
use App\Models\Accounting\Account;
use App\Models\Expenses\ExpenseCategory;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class ExpenseCategoryController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Expenses/Categories/Index', [
            'categories' => ExpenseCategory::query()->with('defaultAccount:id,code,name')->orderBy('name')->get(),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Expenses/Categories/Create', $this->formOptions());
    }

    public function store(StoreExpenseCategoryRequest $request): RedirectResponse
    {
        ExpenseCategory::create($request->validated());

        return redirect()->route('expenses.categories.index')->with('success', 'Category created.');
    }

    public function edit(ExpenseCategory $category): Response
    {
        return Inertia::render('Expenses/Categories/Edit', [
            'category' => $category,
            ...$this->formOptions(),
        ]);
    }

    public function update(StoreExpenseCategoryRequest $request, ExpenseCategory $category): RedirectResponse
    {
        $category->update($request->validated());

        return redirect()->route('expenses.categories.index')->with('success', 'Category updated.');
    }

    public function destroy(ExpenseCategory $category): RedirectResponse
    {
        if ($category->expenses()->exists()) {
            return back()->withErrors(['category' => 'This category has expenses recorded against it and cannot be deleted.']);
        }

        $category->delete();

        return redirect()->route('expenses.categories.index')->with('success', 'Category deleted.');
    }

    private function formOptions(): array
    {
        return [
            'accounts' => Account::query()->where('is_active', true)->where('type', 'expense')
                ->select('id', 'code', 'name')->orderBy('code')->get(),
        ];
    }
}
