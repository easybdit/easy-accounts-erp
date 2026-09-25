<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use App\Http\Requests\HR\StoreLeaveTypeRequest;
use Easybdit\LaravelEasyAttendance\Models\LeaveType;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class LeaveTypeController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('HR/LeaveTypes/Index', [
            'leaveTypes' => LeaveType::query()->orderBy('name')->get(),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('HR/LeaveTypes/Create');
    }

    public function store(StoreLeaveTypeRequest $request): RedirectResponse
    {
        LeaveType::create($request->validated());

        return redirect()->route('hr.leave-types.index')->with('success', 'Leave type created.');
    }

    public function edit(LeaveType $leaveType): Response
    {
        return Inertia::render('HR/LeaveTypes/Edit', [
            'leaveType' => $leaveType,
        ]);
    }

    public function update(StoreLeaveTypeRequest $request, LeaveType $leaveType): RedirectResponse
    {
        $leaveType->update($request->validated());

        return redirect()->route('hr.leave-types.index')->with('success', 'Leave type updated.');
    }

    public function destroy(LeaveType $leaveType): RedirectResponse
    {
        if ($leaveType->leaves()->exists()) {
            return back()->with('error', 'This leave type has requests recorded against it and cannot be deleted.');
        }

        $leaveType->delete();

        return redirect()->route('hr.leave-types.index')->with('success', 'Leave type deleted.');
    }
}
