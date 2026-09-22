<?php

namespace App\Http\Controllers\Tax;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tax\StoreTaxRateRequest;
use App\Models\Accounting\Account;
use App\Models\Tax\TaxRate;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class TaxRateController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Tax/Rates/Index', [
            'rates' => TaxRate::query()->with('taxAccount:id,code,name')->orderBy('name')->get(),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Tax/Rates/Create', $this->formOptions());
    }

    public function store(StoreTaxRateRequest $request): RedirectResponse
    {
        TaxRate::create($request->validated());

        return redirect()->route('tax.rates.index')->with('success', 'Tax rate created.');
    }

    public function edit(TaxRate $rate): Response
    {
        return Inertia::render('Tax/Rates/Edit', [
            'rate' => $rate,
            ...$this->formOptions(),
        ]);
    }

    public function update(StoreTaxRateRequest $request, TaxRate $rate): RedirectResponse
    {
        $rate->update($request->validated());

        return redirect()->route('tax.rates.index')->with('success', 'Tax rate updated.');
    }

    public function destroy(TaxRate $rate): RedirectResponse
    {
        if ($rate->invoiceItems()->exists() || $rate->billItems()->exists()) {
            return back()->withErrors(['rate' => 'This tax rate has been used on invoices or bills and cannot be deleted.']);
        }

        $rate->delete();

        return redirect()->route('tax.rates.index')->with('success', 'Tax rate deleted.');
    }

    private function formOptions(): array
    {
        return [
            'accounts' => Account::query()->where('is_active', true)->where('type', 'liability')
                ->select('id', 'code', 'name')->orderBy('code')->get(),
        ];
    }
}
