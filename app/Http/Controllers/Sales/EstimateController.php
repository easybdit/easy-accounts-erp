<?php

namespace App\Http\Controllers\Sales;

use App\Actions\Sales\ConvertEstimateToInvoice;
use App\Actions\Sales\SaveEstimateDraft;
use App\Actions\Sales\SendEstimateEmail;
use App\Http\Controllers\Concerns\FormatsPlainDates;
use App\Http\Controllers\Controller;
use App\Http\Requests\Sales\StoreEstimateRequest;
use App\Models\Accounting\Account;
use App\Models\Contacts\Customer;
use App\Models\Sales\Estimate;
use App\Models\Tax\TaxRate;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Inertia\Inertia;
use Inertia\Response;
use RuntimeException;

class EstimateController extends Controller
{
    use FormatsPlainDates;

    public function index(Request $request): Response
    {
        $estimates = Estimate::query()
            ->with('customer:id,name')
            ->when($request->string('search')->toString(), function ($query, $search) {
                $query->where(function ($query) use ($search) {
                    $query->where('estimate_number', 'like', "%{$search}%")
                        ->orWhereHas('customer', fn ($q) => $q->where('name', 'like', "%{$search}%"));
                });
            })
            ->when($request->string('status')->toString(), fn ($query, $status) => $query->where('status', $status))
            ->orderByDesc('estimate_date')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString()
            ->through(fn (Estimate $estimate) => [
                'id' => $estimate->id,
                'estimate_number' => $estimate->estimate_number,
                'customer' => ['id' => $estimate->customer->id, 'name' => $estimate->customer->name],
                'estimate_date' => $estimate->estimate_date->toDateString(),
                'total' => (string) $estimate->total,
                'status' => $estimate->status,
            ]);

        return Inertia::render('Sales/Estimates/Index', [
            'estimates' => $estimates,
            'filters' => $request->only(['search', 'status']),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Sales/Estimates/Create', $this->formOptions());
    }

    public function store(StoreEstimateRequest $request, SaveEstimateDraft $action): RedirectResponse
    {
        $estimate = $action->handle([
            ...$request->validated(),
            'created_by' => $request->user()->id,
        ]);

        return redirect()->route('sales.estimates.show', $estimate)->with('success', 'Estimate saved.');
    }

    public function edit(Estimate $estimate): Response
    {
        abort_unless($estimate->isEditable(), 403, 'A converted estimate cannot be edited.');

        return Inertia::render('Sales/Estimates/Edit', [
            'estimate' => $estimate->load('items'),
            ...$this->formOptions(),
        ]);
    }

    public function update(StoreEstimateRequest $request, Estimate $estimate, SaveEstimateDraft $action): RedirectResponse
    {
        abort_unless($estimate->isEditable(), 403, 'A converted estimate cannot be edited.');

        $action->handle($request->validated(), $estimate);

        return redirect()->route('sales.estimates.show', $estimate)->with('success', 'Estimate updated.');
    }

    public function show(Estimate $estimate): Response
    {
        $estimate->load([
            'customer:id,name,email',
            'receivableAccount:id,code,name',
            'items.account:id,code,name',
            'items.taxRate:id,name,rate',
            'convertedInvoice:id,invoice_number,status',
        ]);

        return Inertia::render('Sales/Estimates/Show', [
            'estimate' => $this->withPlainDates($estimate, ['estimate_date', 'expiry_date']),
        ]);
    }

    public function pdf(Estimate $estimate): HttpResponse
    {
        $estimate->load(['customer', 'items.account', 'items.taxRate']);

        $pdf = Pdf::loadView('pdfs.estimate', [
            'estimate' => $estimate,
            'appName' => config('app.name'),
        ]);

        return $pdf->download("{$estimate->estimate_number}.pdf");
    }

    public function sendEmail(Estimate $estimate, Request $request, SendEstimateEmail $action): RedirectResponse
    {
        $validated = $request->validate([
            'recipient_email' => ['nullable', 'email'],
        ]);

        try {
            $action->handle($estimate, $validated['recipient_email'] ?? null);
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Estimate emailed.');
    }

    public function destroy(Estimate $estimate): RedirectResponse
    {
        abort_unless($estimate->isEditable(), 403, 'A converted estimate cannot be deleted.');

        $estimate->delete();

        return redirect()->route('sales.estimates.index')->with('success', 'Estimate deleted.');
    }

    public function convert(Estimate $estimate, ConvertEstimateToInvoice $action): RedirectResponse
    {
        try {
            $invoice = $action->handle($estimate, request()->user()->id);
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('sales.invoices.edit', $invoice)
            ->with('success', "Draft invoice {$invoice->invoice_number} created from {$estimate->estimate_number} — review before posting.");
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
