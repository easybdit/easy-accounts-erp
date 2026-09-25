<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use App\Http\Requests\HR\StoreLeaveRequestRequest;
use Easybdit\LaravelEasyAttendance\Models\LeaveType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Self-service: everything here is scoped to the current user's own linked
 * employee record (auth()->user()->employee), never another employee's —
 * this is the "leaves.own" permission tier, distinct from leaves.manage.
 */
class MyLeaveController extends Controller
{
    public function index(Request $request): Response
    {
        $employee = $request->user()->employee;

        return Inertia::render('HR/MyLeaves/Index', [
            'employee' => $employee?->only(['id', 'employee_code', 'name']),
            'leaves' => $employee
                ? $employee->leaves()->with('leaveType:id,name')->orderByDesc('start_date')->get()
                : [],
            'balances' => $employee ? $employee->leaveBalances() : [],
        ]);
    }

    public function create(Request $request): Response
    {
        return Inertia::render('HR/MyLeaves/Create', [
            'leaveTypes' => LeaveType::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(StoreLeaveRequestRequest $request): RedirectResponse
    {
        $employee = $request->user()->employee;

        if ($employee === null) {
            return back()->with('error', 'Your account is not linked to an employee record. Ask an administrator to link it before applying for leave.');
        }

        $employee->requestLeave($request->validated());

        return redirect()->route('hr.my-leaves.index')->with('success', 'Leave request submitted.');
    }
}
