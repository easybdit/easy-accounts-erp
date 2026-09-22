<?php

namespace App\Http\Controllers\Sales;

use App\Actions\Sales\PostInvoice;
use App\Actions\Sales\SaveInvoiceDraft;
use App\Http\Controllers\Controller;
use App\Http\Requests\Sales\StoreInvoiceRequest;
use App\Models\Accounting\Account;
use App\Models\Contacts\Customer;
use App\Models\Sales\Invoice;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class InvoiceController extends Controller
{
    public function index(Request $request): Response
    {
        $invoices = Invoice::query()
            ->with('customer:id,name')
            ->when($request->string('search')->toString(), function ($query, $search) {
                $query->where(function ($query) use ($search) {
                    $query->where('invoice_number', 'like', "%{$search}%")
                        ->orWhereHas('customer', fn ($q) => $q->where('name', 'like', "%{$search}%"));
                });
            })
            ->when($request->string('status')->toString(), fn ($query, $status) => $query->where('status', $status))
            ->orderByDesc('invoice_date')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Sales/Invoices/Index', [
            'invoices' => $invoices,
            'filters' => $request->only(['search', 'status']),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Sales/Invoices/Create', $this->formOptions());
    }

    public function store(StoreInvoiceRequest $request, SaveInvoiceDraft $action): RedirectResponse
    {
        $invoice = $action->handle([
            ...$request->validated(),
            'created_by' => $request->user()->id,
        ]);

        return redirect()->route('sales.invoices.show', $invoice)->with('success', 'Invoice saved as draft.');
    }

    public function edit(Invoice $invoice): Response
    {
        abort_unless($invoice->isDraft(), 403, 'A posted invoice cannot be edited.');

        return Inertia::render('Sales/Invoices/Edit', [
            'invoice' => $invoice->load('items'),
            ...$this->formOptions(),
        ]);
    }

    public function update(StoreInvoiceRequest $request, Invoice $invoice, SaveInvoiceDraft $action): RedirectResponse
    {
        abort_unless($invoice->isDraft(), 403, 'A posted invoice cannot be edited.');

        $action->handle($request->validated(), $invoice);

        return redirect()->route('sales.invoices.show', $invoice)->with('success', 'Invoice updated.');
    }

    public function show(Invoice $invoice): Response
    {
        $invoice->load(['customer:id,name', 'receivableAccount:id,code,name', 'items.account:id,code,name', 'journal']);

        return Inertia::render('Sales/Invoices/Show', [
            'invoice' => $invoice,
        ]);
    }

    public function destroy(Invoice $invoice): RedirectResponse
    {
        abort_unless($invoice->isDraft(), 403, 'A posted invoice cannot be deleted.');

        $invoice->delete();

        return redirect()->route('sales.invoices.index')->with('success', 'Invoice deleted.');
    }

    public function post(Invoice $invoice, PostInvoice $action): RedirectResponse
    {
        try {
            $action->handle($invoice);
        } catch (\RuntimeException $e) {
            return back()->withErrors(['invoice' => $e->getMessage()]);
        }

        return redirect()->route('sales.invoices.show', $invoice)->with('success', 'Invoice posted.');
    }

    private function formOptions(): array
    {
        return [
            'customers' => Customer::query()->where('is_active', true)->select('id', 'name')->orderBy('name')->get(),
            'receivableAccounts' => Account::query()->where('is_active', true)->where('type', 'asset')
                ->select('id', 'code', 'name')->orderBy('code')->get(),
            'incomeAccounts' => Account::query()->where('is_active', true)->where('type', 'income')
                ->select('id', 'code', 'name')->orderBy('code')->get(),
        ];
    }
}
