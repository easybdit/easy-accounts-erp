<?php

namespace App\Http\Controllers\Purchases;

use App\Actions\Purchases\GenerateBillFromRecurring;
use App\Actions\Purchases\SaveRecurringBill;
use App\Http\Controllers\Concerns\FormatsPlainDates;
use App\Http\Controllers\Controller;
use App\Http\Requests\Purchases\StoreRecurringBillRequest;
use App\Models\Accounting\Account;
use App\Models\Contacts\Vendor;
use App\Models\Purchases\RecurringBill;
use App\Models\Tax\TaxRate;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class RecurringBillController extends Controller
{
    use FormatsPlainDates;

    public function index(): Response
    {
        $templates = RecurringBill::query()
            ->with('vendor:id,name')
            ->withCount('items')
            ->orderBy('name')
            ->get()
            ->map(fn (RecurringBill $template) => [
                'id' => $template->id,
                'name' => $template->name,
                'vendor' => ['name' => $template->vendor->name],
                'items_count' => $template->items_count,
                'is_active' => $template->is_active,
                'next_generation_date' => $template->next_generation_date?->toDateString(),
            ]);

        return Inertia::render('Purchases/RecurringBills/Index', [
            'templates' => $templates,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Purchases/RecurringBills/Create', $this->formOptions());
    }

    public function store(StoreRecurringBillRequest $request, SaveRecurringBill $action): RedirectResponse
    {
        $template = $action->handle([
            ...$request->validated(),
            'created_by' => $request->user()->id,
        ]);

        return redirect()->route('purchases.recurring-bills.index')->with('success', "Template \"{$template->name}\" saved.");
    }

    public function edit(RecurringBill $recurringBill): Response
    {
        $recurringBill->load('items');

        return Inertia::render('Purchases/RecurringBills/Edit', [
            'template' => $this->withPlainDates($recurringBill, ['next_generation_date']),
            ...$this->formOptions(),
        ]);
    }

    public function update(StoreRecurringBillRequest $request, RecurringBill $recurringBill, SaveRecurringBill $action): RedirectResponse
    {
        $action->handle($request->validated(), $recurringBill);

        return redirect()->route('purchases.recurring-bills.index')->with('success', 'Template updated.');
    }

    public function destroy(RecurringBill $recurringBill): RedirectResponse
    {
        $recurringBill->delete();

        return redirect()->route('purchases.recurring-bills.index')->with('success', 'Template deleted.');
    }

    public function generate(RecurringBill $recurringBill, GenerateBillFromRecurring $action): RedirectResponse
    {
        $bill = $action->handle($recurringBill, auth()->id());

        return redirect()->route('purchases.bills.edit', $bill)
            ->with('success', "Draft bill {$bill->bill_number} generated from \"{$recurringBill->name}\" — review before posting.");
    }

    private function formOptions(): array
    {
        return [
            'vendors' => Vendor::query()->where('is_active', true)->select('id', 'name')->orderBy('name')->get(),
            'payableAccounts' => Account::query()->where('is_active', true)->where('type', 'liability')
                ->select('id', 'code', 'name')->orderBy('code')->get(),
            'expenseAccounts' => Account::query()->where('is_active', true)->where('type', 'expense')
                ->select('id', 'code', 'name')->orderBy('code')->get(),
            'taxRates' => TaxRate::query()->where('is_active', true)->select('id', 'name', 'rate')->orderBy('name')->get(),
        ];
    }
}
