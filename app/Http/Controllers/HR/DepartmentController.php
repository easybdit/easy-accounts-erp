<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use App\Http\Requests\HR\StoreDepartmentRequest;
use Easybdit\LaravelEasyAttendance\Models\Department;
use Easybdit\LaravelEasyAttendance\Models\Employee;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class DepartmentController extends Controller
{
    public function index(): Response
    {
        $departments = Department::query()->withCount('employees')->orderBy('name')->get();
        $heads = Employee::query()->whereIn('id', $departments->pluck('head_employee_id')->filter())->pluck('name', 'id');

        return Inertia::render('HR/Departments/Index', [
            'departments' => $departments->map(fn (Department $department) => [
                ...$department->toArray(),
                'head_name' => $heads[$department->head_employee_id] ?? null,
            ]),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('HR/Departments/Create', $this->formOptions());
    }

    public function store(StoreDepartmentRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $headEmployeeId = data_get($validated, 'head_employee_id');
        unset($validated['head_employee_id']);

        $department = Department::create($validated);
        // head_employee_id isn't in the package's own Department::$fillable
        // (just ['name', 'description']) — forceFill() the same way 'grade'
        // and 'dept_head_status' need to be, elsewhere in this round.
        $department->forceFill(['head_employee_id' => $headEmployeeId])->save();

        return redirect()->route('hr.departments.index')->with('success', 'Department created.');
    }

    public function edit(Department $department): Response
    {
        return Inertia::render('HR/Departments/Edit', [
            'department' => $department,
            ...$this->formOptions(),
        ]);
    }

    public function update(StoreDepartmentRequest $request, Department $department): RedirectResponse
    {
        $validated = $request->validated();
        $headEmployeeId = data_get($validated, 'head_employee_id');
        unset($validated['head_employee_id']);

        $department->update($validated);
        $department->forceFill(['head_employee_id' => $headEmployeeId])->save();

        return redirect()->route('hr.departments.index')->with('success', 'Department updated.');
    }

    public function destroy(Department $department): RedirectResponse
    {
        if ($department->employees()->exists() || $department->designations()->exists()) {
            return back()->with('error', 'This department has employees or designations and cannot be deleted.');
        }

        $department->delete();

        return redirect()->route('hr.departments.index')->with('success', 'Department deleted.');
    }

    private function formOptions(): array
    {
        return [
            'employees' => Employee::query()->orderBy('name')->get(['id', 'name', 'employee_code']),
        ];
    }
}
