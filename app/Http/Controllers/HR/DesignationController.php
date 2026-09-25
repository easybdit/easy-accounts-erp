<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use App\Http\Requests\HR\StoreDesignationRequest;
use Easybdit\LaravelEasyAttendance\Models\Department;
use Easybdit\LaravelEasyAttendance\Models\Designation;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class DesignationController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('HR/Designations/Index', [
            'designations' => Designation::query()->with('department:id,name')->withCount('employees')->orderBy('name')->get(),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('HR/Designations/Create', $this->formOptions());
    }

    public function store(StoreDesignationRequest $request): RedirectResponse
    {
        Designation::create($request->validated());

        return redirect()->route('hr.designations.index')->with('success', 'Designation created.');
    }

    public function edit(Designation $designation): Response
    {
        return Inertia::render('HR/Designations/Edit', [
            'designation' => $designation,
            ...$this->formOptions(),
        ]);
    }

    public function update(StoreDesignationRequest $request, Designation $designation): RedirectResponse
    {
        $designation->update($request->validated());

        return redirect()->route('hr.designations.index')->with('success', 'Designation updated.');
    }

    public function destroy(Designation $designation): RedirectResponse
    {
        if ($designation->employees()->exists()) {
            return back()->with('error', 'This designation has employees assigned and cannot be deleted.');
        }

        $designation->delete();

        return redirect()->route('hr.designations.index')->with('success', 'Designation deleted.');
    }

    private function formOptions(): array
    {
        return [
            'departments' => Department::query()->orderBy('name')->get(['id', 'name']),
        ];
    }
}
