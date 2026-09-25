<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Concerns\FormatsPlainDates;
use App\Http\Controllers\Controller;
use App\Http\Requests\HR\StoreSpecialWorkingDayRequest;
use App\Models\HR\HrSettings;
use Easybdit\LaravelEasyAttendance\Models\Employee;
use Easybdit\LaravelEasyAttendance\Models\SpecialWorkingDay;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class SpecialWorkingDayController extends Controller
{
    use FormatsPlainDates;

    public function index(): Response
    {
        return Inertia::render('HR/SpecialWorkingDays/Index', [
            'specialWorkingDays' => SpecialWorkingDay::query()
                ->with('employee:id,employee_code,name,grade')
                ->orderByDesc('date')
                ->get()
                ->map(fn (SpecialWorkingDay $day) => $this->withPlainDates($day, ['date'])),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('HR/SpecialWorkingDays/Create', $this->formOptions());
    }

    public function store(StoreSpecialWorkingDayRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $validated['payment_amount'] = $this->resolvePaymentAmount($validated);

        SpecialWorkingDay::create($validated);

        return redirect()->route('hr.special-working-days.index')->with('success', 'Special working day recorded.');
    }

    public function edit(SpecialWorkingDay $specialWorkingDay): Response
    {
        return Inertia::render('HR/SpecialWorkingDays/Edit', [
            'specialWorkingDay' => $this->withPlainDates($specialWorkingDay, ['date']),
            ...$this->formOptions(),
        ]);
    }

    public function update(StoreSpecialWorkingDayRequest $request, SpecialWorkingDay $specialWorkingDay): RedirectResponse
    {
        $validated = $request->validated();
        $validated['payment_amount'] = $this->resolvePaymentAmount($validated);

        $specialWorkingDay->update($validated);

        return redirect()->route('hr.special-working-days.index')->with('success', 'Special working day updated.');
    }

    public function destroy(SpecialWorkingDay $specialWorkingDay): RedirectResponse
    {
        $specialWorkingDay->delete();

        return redirect()->route('hr.special-working-days.index')->with('success', 'Special working day deleted.');
    }

    /**
     * A blank amount defaults to the employee's grade rate (HR Settings) —
     * an admin who does supply one always keeps that exact figure, matching
     * SpecialWorkingDay::getPaymentAmount()'s own priority (a record's own
     * payment_amount always wins over any config-driven default).
     */
    private function resolvePaymentAmount(array $validated): ?float
    {
        if (filled($validated['payment_amount'] ?? null)) {
            return (float) $validated['payment_amount'];
        }

        $employee = Employee::find($validated['employee_id']);

        return HrSettings::current()->gradeRate($employee?->grade);
    }

    private function formOptions(): array
    {
        return [
            'employees' => Employee::query()->orderBy('name')->get(['id', 'employee_code', 'name', 'grade']),
        ];
    }
}
