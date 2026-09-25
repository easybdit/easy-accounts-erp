<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use App\Http\Requests\HR\StoreAttendancePunchRequest;
use Easybdit\LaravelEasyAttendance\Models\Attendance;
use Easybdit\LaravelEasyAttendance\Models\Employee;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Admin/HR-entered attendance — for correcting a punch, backfilling
 * history, or recording attendance for employees without a device/login.
 * Real-time punches from a logged-in employee go through
 * MyAttendanceController instead.
 */
class AttendanceController extends Controller
{
    public function index(Request $request): Response
    {
        $employeeId = $request->integer('employee_id') ?: null;

        return Inertia::render('HR/Attendance/Index', [
            'employees' => Employee::query()->orderBy('name')->get(['id', 'employee_code', 'name']),
            'selectedEmployeeId' => $employeeId,
            'punches' => Attendance::query()
                ->where('subject_type', Employee::class)
                ->when($employeeId, fn ($query) => $query->where('subject_id', $employeeId))
                ->with('subject:id,employee_code,name')
                ->orderByDesc('time')
                ->limit(200)
                ->get()
                ->map(fn (Attendance $punch) => [
                    'id' => $punch->id,
                    'employee' => $punch->subject?->only(['id', 'employee_code', 'name']),
                    'type' => $punch->type,
                    'time' => $punch->time->toDateTimeString(),
                    'source' => $punch->source,
                    'is_manual' => $punch->is_manual,
                ]),
        ]);
    }

    public function store(StoreAttendancePunchRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $employee = Employee::findOrFail($validated['employee_id']);

        $method = $validated['type'] === 'check_in' ? 'checkIn' : 'checkOut';
        $employee->{$method}(['time' => "{$validated['date']} {$validated['time']}:00"]);

        return back()->with('success', 'Attendance recorded.');
    }
}
