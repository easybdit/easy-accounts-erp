<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use App\Http\Requests\HR\StoreDepartmentRequest;
use Easybdit\LaravelEasyAttendance\Models\Department;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class DepartmentController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('HR/Departments/Index', [
            'departments' => Department::query()->withCount('employees')->orderBy('name')->get(),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('HR/Departments/Create');
    }

    public function store(StoreDepartmentRequest $request): RedirectResponse
    {
        Department::create($request->validated());

        return redirect()->route('hr.departments.index')->with('success', 'Department created.');
    }

    public function edit(Department $department): Response
    {
        return Inertia::render('HR/Departments/Edit', ['department' => $department]);
    }

    public function update(StoreDepartmentRequest $request, Department $department): RedirectResponse
    {
        $department->update($request->validated());

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
}
