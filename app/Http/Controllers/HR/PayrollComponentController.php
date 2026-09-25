<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use App\Http\Requests\HR\StorePayrollComponentRequest;
use App\Models\Accounting\Account;
use App\Models\HR\PayrollComponent;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class PayrollComponentController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('HR/PayrollComponents/Index', [
            'components' => PayrollComponent::query()->with('account:id,code,name')->orderBy('name')->get(),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('HR/PayrollComponents/Create', $this->formOptions());
    }

    public function store(StorePayrollComponentRequest $request): RedirectResponse
    {
        PayrollComponent::create($request->validated());

        return redirect()->route('hr.payroll-components.index')->with('success', 'Payroll component created.');
    }

    public function edit(PayrollComponent $payrollComponent): Response
    {
        return Inertia::render('HR/PayrollComponents/Edit', [
            'component' => $payrollComponent,
            ...$this->formOptions(),
        ]);
    }

    public function update(StorePayrollComponentRequest $request, PayrollComponent $payrollComponent): RedirectResponse
    {
        $payrollComponent->update($request->validated());

        return redirect()->route('hr.payroll-components.index')->with('success', 'Payroll component updated.');
    }

    public function destroy(PayrollComponent $payrollComponent): RedirectResponse
    {
        $payrollComponent->delete();

        return redirect()->route('hr.payroll-components.index')->with('success', 'Payroll component deleted.');
    }

    private function formOptions(): array
    {
        return [
            'accounts' => Account::query()->where('is_active', true)
                ->whereIn('type', ['expense', 'liability'])
                ->select('id', 'code', 'name', 'type')->orderBy('code')->get(),
            'fixedKeys' => PayrollComponent::FIXED_KEYS,
        ];
    }
}
