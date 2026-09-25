<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use Easybdit\LaravelEasyAttendance\Models\AttendanceSummary;
use Easybdit\LaravelEasyAttendance\Models\Department;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Attendance summary for the employees in departments the current user's
 * employee heads — same grouping shape as
 * Reports\ReportController::attendanceSummary(), but self-service
 * (leaves.own, not reports.view) and scoped to just the head's team.
 */
class TeamAttendanceController extends Controller
{
    public function index(Request $request): Response
    {
        $employee = $request->user()->employee;
        $departmentIds = $employee ? Department::where('head_employee_id', $employee->id)->pluck('id') : collect();

        $year = (int) ($request->input('year') ?: now()->year);
        $month = (int) ($request->input('month') ?: now()->month);

        $rows = collect();

        if ($departmentIds->isNotEmpty()) {
            $rows = AttendanceSummary::query()
                ->whereHas('employee', fn ($q) => $q->whereIn('department_id', $departmentIds))
                ->with('employee:id,employee_code,name')
                ->forMonth($year, $month)
                ->get()
                ->groupBy('employee_id')
                ->map(function ($summaries) {
                    $employee = $summaries->first()->employee;

                    return [
                        'employee_code' => $employee->employee_code,
                        'employee_name' => $employee->name,
                        'present' => $summaries->whereIn('status', ['present', 'late'])->count(),
                        'absent' => $summaries->where('status', 'absent')->count(),
                        'late' => $summaries->where('status', 'late')->count(),
                        'leave' => $summaries->where('status', 'leave')->count(),
                    ];
                })
                ->sortBy('employee_name')
                ->values();
        }

        return Inertia::render('HR/TeamAttendance/Index', [
            'isDepartmentHead' => $departmentIds->isNotEmpty(),
            'rows' => $rows,
            'year' => $year,
            'month' => $month,
        ]);
    }
}
