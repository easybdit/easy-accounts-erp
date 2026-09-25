<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Concerns\FormatsPlainDates;
use App\Http\Controllers\Controller;
use Easybdit\LaravelEasyAttendance\Models\Department;
use Easybdit\LaravelEasyAttendance\Models\Leave;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The department-head stage of leave approval — an ordinary employee who
 * happens to head a department, not necessarily HR staff, so this is
 * gated by leaves.own (like MyLeaveController) rather than leaves.manage.
 */
class TeamLeaveController extends Controller
{
    use FormatsPlainDates;

    public function index(Request $request): Response
    {
        $employee = $request->user()->employee;
        $departmentIds = $employee ? $this->headedDepartmentIds($employee->id) : collect();

        return Inertia::render('HR/TeamLeaves/Index', [
            'isDepartmentHead' => $departmentIds->isNotEmpty(),
            'leaves' => $departmentIds->isNotEmpty()
                ? Leave::query()
                    ->whereHas('employee', fn ($q) => $q->whereIn('department_id', $departmentIds))
                    ->where('dept_head_status', 'pending')
                    ->with(['employee:id,employee_code,name', 'leaveType:id,name'])
                    ->orderBy('start_date')
                    ->get()
                    ->map(fn (Leave $leave) => $this->withPlainDates($leave, ['start_date', 'end_date']))
                : [],
        ]);
    }

    public function approve(Request $request, Leave $leave): RedirectResponse
    {
        $this->authorizeHead($request, $leave);

        $leave->forceFill([
            'dept_head_status' => 'approved',
            'dept_head_by' => $request->user()->id,
            'dept_head_at' => now(),
            'dept_head_note' => $request->input('note'),
        ])->save();

        return back()->with('success', 'Approved — now waiting on HR.');
    }

    public function reject(Request $request, Leave $leave): RedirectResponse
    {
        $this->authorizeHead($request, $leave);

        $leave->forceFill([
            'dept_head_status' => 'rejected',
            'dept_head_by' => $request->user()->id,
            'dept_head_at' => now(),
            'dept_head_note' => $request->input('note'),
        ])->save();

        // Rejected at the department-head stage is final — mirrors
        // LeaveController::reject()'s own use of the package's method, so
        // the same LeaveReviewed event and status transition fire either way.
        $leave->reject($request->user()->id, $request->input('note'));

        return back()->with('success', 'Rejected.');
    }

    private function authorizeHead(Request $request, Leave $leave): void
    {
        $employee = $request->user()->employee;
        $leave->loadMissing('employee');

        abort_if($employee === null, 403);
        abort_unless(
            $this->headedDepartmentIds($employee->id)->contains($leave->employee->department_id),
            403,
            'You are not the department head for this employee.'
        );
        abort_unless($leave->dept_head_status === 'pending', 403, 'This request has already been reviewed.');
    }

    private function headedDepartmentIds(int $employeeId): Collection
    {
        return Department::where('head_employee_id', $employeeId)->pluck('id');
    }
}
