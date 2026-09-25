<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Concerns\FormatsPlainDates;
use App\Http\Controllers\Controller;
use App\Http\Requests\HR\StoreEmployeeRequest;
use App\Models\User;
use Easybdit\LaravelEasyAttendance\Models\Department;
use Easybdit\LaravelEasyAttendance\Models\Designation;
use Easybdit\LaravelEasyAttendance\Models\Employee;
use Easybdit\LaravelEasyAttendance\Models\EmployeeShift;
use Easybdit\LaravelEasyAttendance\Models\Shift;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class EmployeeController extends Controller
{
    use FormatsPlainDates;

    public function index(): Response
    {
        return Inertia::render('HR/Employees/Index', [
            'employees' => Employee::query()
                ->with(['department:id,name', 'designationRecord:id,name'])
                ->orderBy('name')
                ->get()
                ->map(fn (Employee $employee) => $this->withPlainDates($employee, ['joined_at'])),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('HR/Employees/Create', $this->formOptions());
    }

    public function store(StoreEmployeeRequest $request): RedirectResponse
    {
        $employee = Employee::create($request->validated());

        return redirect()->route('hr.employees.edit', $employee)->with('success', 'Employee created.');
    }

    public function edit(Employee $employee): Response
    {
        $employee->load(['shiftAssignments.shift']);

        return Inertia::render('HR/Employees/Edit', [
            'employee' => $this->withPlainDates($employee, ['joined_at']),
            'shiftAssignments' => $employee->shiftAssignments
                ->map(fn (EmployeeShift $assignment) => $this->withPlainDates($assignment, ['start_date', 'end_date'])),
            'linkedUser' => User::where('employee_id', $employee->id)->first(['id', 'name', 'email']),
            'availableUsers' => User::whereNull('employee_id')->orWhere('employee_id', $employee->id)
                ->orderBy('name')->get(['id', 'name', 'email']),
            'shifts' => Shift::query()->orderBy('name')->get(['id', 'name']),
            ...$this->formOptions(),
        ]);
    }

    public function update(StoreEmployeeRequest $request, Employee $employee): RedirectResponse
    {
        $employee->update($request->validated());

        return redirect()->route('hr.employees.edit', $employee)->with('success', 'Employee updated.');
    }

    public function destroy(Employee $employee): RedirectResponse
    {
        if ($employee->leaves()->exists() || $employee->salarySlips()->exists() || $employee->summaries()->exists()) {
            return back()->with('error', 'This employee has attendance/leave/payroll history and cannot be deleted.');
        }

        $employee->delete();

        return redirect()->route('hr.employees.index')->with('success', 'Employee deleted.');
    }

    public function linkUser(Request $request, Employee $employee): RedirectResponse
    {
        $validated = $request->validate([
            'user_id' => ['required', 'integer', Rule::exists('users', 'id')],
        ]);

        User::where('employee_id', $employee->id)->update(['employee_id' => null]);
        User::where('id', $validated['user_id'])->update(['employee_id' => $employee->id]);

        return back()->with('success', 'User account linked to this employee.');
    }

    public function unlinkUser(Employee $employee): RedirectResponse
    {
        User::where('employee_id', $employee->id)->update(['employee_id' => null]);

        return back()->with('success', 'User account unlinked from this employee.');
    }

    public function assignShift(Request $request, Employee $employee): RedirectResponse
    {
        $validated = $request->validate([
            'shift_id' => ['required', 'integer', Rule::exists((new Shift)->getTable(), 'id')],
            'start_date' => ['required', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
        ]);

        $employee->shiftAssignments()->create($validated);

        return back()->with('success', 'Shift assigned.');
    }

    public function removeShiftAssignment(Employee $employee, int $assignment): RedirectResponse
    {
        $employee->shiftAssignments()->where('id', $assignment)->delete();

        return back()->with('success', 'Shift assignment removed.');
    }

    private function formOptions(): array
    {
        return [
            'departments' => Department::query()->orderBy('name')->get(['id', 'name']),
            'designations' => Designation::query()->orderBy('name')->get(['id', 'name', 'department_id']),
        ];
    }
}
