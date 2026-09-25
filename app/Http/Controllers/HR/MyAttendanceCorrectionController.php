<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Concerns\FormatsPlainDates;
use App\Http\Controllers\Controller;
use App\Http\Requests\HR\StoreAttendanceCorrectionRequest;
use Easybdit\LaravelEasyAttendance\Models\AttendanceCorrection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Self-service: request a correction for a missing/wrong punch on the
 * current user's own linked employee — attendance.own, never another
 * employee's.
 */
class MyAttendanceCorrectionController extends Controller
{
    use FormatsPlainDates;

    public function index(Request $request): Response
    {
        $employee = $request->user()->employee;

        return Inertia::render('HR/MyAttendanceCorrections/Index', [
            'employee' => $employee?->only(['id', 'employee_code', 'name']),
            'corrections' => $employee
                ? $employee->attendanceCorrections()->orderByDesc('date')->get()
                    ->map(fn (AttendanceCorrection $correction) => $this->withPlainDates($correction, ['date']))
                : [],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('HR/MyAttendanceCorrections/Create');
    }

    public function store(StoreAttendanceCorrectionRequest $request): RedirectResponse
    {
        $employee = $request->user()->employee;

        if ($employee === null) {
            return back()->with('error', 'Your account is not linked to an employee record.');
        }

        $employee->requestAttendanceCorrection($request->validated());

        return redirect()->route('hr.my-attendance-corrections.index')->with('success', 'Correction request submitted.');
    }
}
