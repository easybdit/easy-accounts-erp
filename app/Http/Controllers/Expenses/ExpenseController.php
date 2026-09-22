<?php

namespace App\Http\Controllers\Expenses;

use App\Actions\Expenses\RecordExpense;
use App\Actions\Expenses\StoreExpenseAttachments;
use App\Http\Controllers\Concerns\FormatsPlainDates;
use App\Http\Controllers\Controller;
use App\Http\Requests\Expenses\StoreExpenseRequest;
use App\Models\Accounting\Account;
use App\Models\Contacts\Vendor;
use App\Models\Expenses\Expense;
use App\Models\Expenses\ExpenseAttachment;
use App\Models\Expenses\ExpenseCategory;
use App\Models\Tax\TaxRate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExpenseController extends Controller
{
    use FormatsPlainDates;

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
            ->withQueryString()
            ->through(fn (Expense $expense) => [
                'id' => $expense->id,
                'expense_number' => $expense->expense_number,
                'payee' => $expense->payee,
                'category' => ['name' => $expense->category->name],
                'expense_date' => $expense->expense_date->toDateString(),
                'amount' => (string) $expense->amount,
            ]);

        return Inertia::render('Expenses/Index', [
            'expenses' => $expenses,
            'filters' => $request->only(['search']),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Expenses/Create', $this->formOptions());
    }

    public function store(StoreExpenseRequest $request, RecordExpense $action, StoreExpenseAttachments $attachmentsAction): RedirectResponse
    {
        $expense = $action->handle([
            ...$request->validated(),
            'created_by' => $request->user()->id,
        ]);

        if ($request->hasFile('attachments')) {
            $attachmentsAction->handle($expense, $request->file('attachments'), $request->user()->id);
        }

        return redirect()->route('expenses.entries.show', $expense)->with('success', 'Expense recorded.');
    }

    public function show(Expense $expense): Response
    {
        $expense->load(['category:id,name', 'account:id,code,name', 'paymentAccount:id,code,name', 'vendor:id,name', 'taxRate:id,name,rate', 'journal', 'attachments']);

        return Inertia::render('Expenses/Show', [
            'expense' => $this->withPlainDates($expense, ['expense_date']),
            'totalPaid' => $expense->totalPaid(),
        ]);
    }

    public function downloadAttachment(Expense $expense, ExpenseAttachment $attachment): StreamedResponse
    {
        abort_unless($attachment->expense_id === $expense->id, 404);

        return Storage::disk('local')->download($attachment->stored_path, $attachment->original_filename);
    }

    public function destroyAttachment(Expense $expense, ExpenseAttachment $attachment, StoreExpenseAttachments $action): RedirectResponse
    {
        abort_unless($attachment->expense_id === $expense->id, 404);

        $action->delete($expense, $attachment->id);

        return back()->with('success', 'Attachment deleted.');
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
