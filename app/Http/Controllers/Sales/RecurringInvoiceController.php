<?php

namespace App\Http\Controllers\Sales;

use App\Actions\Sales\GenerateInvoiceFromRecurring;
use App\Actions\Sales\SaveRecurringInvoice;
use App\Http\Controllers\Concerns\FormatsPlainDates;
use App\Http\Controllers\Controller;
use App\Http\Requests\Sales\StoreRecurringInvoiceRequest;
use App\Models\Accounting\Account;
use App\Models\Contacts\Customer;
use App\Models\Sales\RecurringInvoice;
use App\Models\Tax\TaxRate;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class RecurringInvoiceController extends Controller
{
    use FormatsPlainDates;

    public function index(): Response
    {
        $templates = RecurringInvoice::query()
            ->with('customer:id,name')
            ->withCount('items')
            ->orderBy('name')
            ->get()
            ->map(fn (RecurringInvoice $template) => [
                'id' => $template->id,
                'name' => $template->name,
                'customer' => ['name' => $template->customer->name],
                'items_count' => $template->items_count,
                'is_active' => $template->is_active,
                'next_generation_date' => $template->next_generation_date?->toDateString(),
            ]);

        return Inertia::render('Sales/RecurringInvoices/Index', [
            'templates' => $templates,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Sales/RecurringInvoices/Create', $this->formOptions());
    }

    public function store(StoreRecurringInvoiceRequest $request, SaveRecurringInvoice $action): RedirectResponse
    {
        $template = $action->handle([
            ...$request->validated(),
            'created_by' => $request->user()->id,
        ]);

        return redirect()->route('sales.recurring-invoices.index')->with('success', "Template \"{$template->name}\" saved.");
    }

    public function edit(RecurringInvoice $recurringInvoice): Response
    {
        $recurringInvoice->load('items');

        return Inertia::render('Sales/RecurringInvoices/Edit', [
            'template' => $this->withPlainDates($recurringInvoice, ['next_generation_date']),
            ...$this->formOptions(),
        ]);
    }

    public function update(StoreRecurringInvoiceRequest $request, RecurringInvoice $recurringInvoice, SaveRecurringInvoice $action): RedirectResponse
    {
        $action->handle($request->validated(), $recurringInvoice);

        return redirect()->route('sales.recurring-invoices.index')->with('success', 'Template updated.');
    }

    public function destroy(RecurringInvoice $recurringInvoice): RedirectResponse
    {
        $recurringInvoice->delete();

        return redirect()->route('sales.recurring-invoices.index')->with('success', 'Template deleted.');
    }

    public function generate(RecurringInvoice $recurringInvoice, GenerateInvoiceFromRecurring $action): RedirectResponse
    {
        $invoice = $action->handle($recurringInvoice, auth()->id());

        return redirect()->route('sales.invoices.edit', $invoice)
            ->with('success', "Draft invoice {$invoice->invoice_number} generated from \"{$recurringInvoice->name}\" — review before posting.");
    }

    private function formOptions(): array
    {
        return [
            'customers' => Customer::query()->where('is_active', true)->select('id', 'name')->orderBy('name')->get(),
            'receivableAccounts' => Account::query()->where('is_active', true)->where('type', 'asset')
                ->select('id', 'code', 'name')->orderBy('code')->get(),
            'incomeAccounts' => Account::query()->where('is_active', true)->where('type', 'income')
                ->select('id', 'code', 'name')->orderBy('code')->get(),
            'taxRates' => TaxRate::query()->where('is_active', true)->select('id', 'name', 'rate')->orderBy('name')->get(),
        ];
    }
}
