<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Concerns\FormatsPlainDates;
use App\Http\Controllers\Controller;
use App\Http\Requests\HR\StoreLeaveRequestRequest;
use App\Models\HR\HrSettings;
use Easybdit\LaravelEasyAttendance\Models\Leave;
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
    use FormatsPlainDates;

    public function index(Request $request): Response
    {
        $employee = $request->user()->employee;

        return Inertia::render('HR/MyLeaves/Index', [
            'employee' => $employee?->only(['id', 'employee_code', 'name']),
            'leaves' => $employee
                ? $employee->leaves()->with('leaveType:id,name')->orderByDesc('start_date')->get()
                    ->map(fn (Leave $leave) => $this->withPlainDates($leave, ['start_date', 'end_date']))
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

        $leave = $employee->requestLeave($request->validated());

        // dept_head_status isn't in the package's own Leave::$fillable, so
        // requestLeave()'s mass-assignment left it at the column default
        // ('skipped') — only promote it to 'pending' when multi-step
        // approval is actually on and this employee's department has a
        // head to send it to.
        $employee->loadMissing('department');
        $needsDeptHeadApproval = HrSettings::current()->multi_step_leave_approval_enabled
            && $employee->department?->head_employee_id !== null;

        if ($needsDeptHeadApproval) {
            $leave->forceFill(['dept_head_status' => 'pending'])->save();
        }

        return redirect()->route('hr.my-leaves.index')->with('success', 'Leave request submitted.');
    }
}
