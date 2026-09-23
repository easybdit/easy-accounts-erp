<?php

namespace App\Http\Controllers\Tax;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tax\StoreWithholdingTaxRateRequest;
use App\Models\Accounting\Account;
use App\Models\Tax\WithholdingTaxRate;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class WithholdingTaxRateController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Tax/WithholdingRates/Index', [
            'rates' => WithholdingTaxRate::query()->with('liabilityAccount:id,code,name')->orderBy('name')->get(),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Tax/WithholdingRates/Create', $this->formOptions());
    }

    public function store(StoreWithholdingTaxRateRequest $request): RedirectResponse
    {
        WithholdingTaxRate::create($request->validated());

        return redirect()->route('tax.withholding-rates.index')->with('success', 'Withholding tax rate created.');
    }

    public function edit(WithholdingTaxRate $withholdingTaxRate): Response
    {
        return Inertia::render('Tax/WithholdingRates/Edit', [
            'rate' => $withholdingTaxRate,
            ...$this->formOptions(),
        ]);
    }

    public function update(StoreWithholdingTaxRateRequest $request, WithholdingTaxRate $withholdingTaxRate): RedirectResponse
    {
        $withholdingTaxRate->update($request->validated());

        return redirect()->route('tax.withholding-rates.index')->with('success', 'Withholding tax rate updated.');
    }

    public function destroy(WithholdingTaxRate $withholdingTaxRate): RedirectResponse
    {
        if ($withholdingTaxRate->vendorPayments()->exists()) {
            return back()->with('error', 'This rate has been used on vendor payments and cannot be deleted.');
        }

        $withholdingTaxRate->delete();

        return redirect()->route('tax.withholding-rates.index')->with('success', 'Withholding tax rate deleted.');
    }

    private function formOptions(): array
    {
        return [
            'accounts' => Account::query()->where('is_active', true)->where('type', 'liability')
                ->select('id', 'code', 'name')->orderBy('code')->get(),
        ];
    }
}
