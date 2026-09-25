<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use Easybdit\LaravelEasyAttendance\Models\Attendance;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Self-service: the current user's own linked employee punches themselves
 * in/out — never another employee's (attendance.own).
 */
class MyAttendanceController extends Controller
{
    public function index(Request $request): Response
    {
        $employee = $request->user()->employee;

        $today = $employee?->attendanceOn(now()->toDateString());

        return Inertia::render('HR/MyAttendance/Index', [
            'employee' => $employee?->only(['id', 'employee_code', 'name']),
            'todayFirstIn' => $today['first_in']?->toTimeString(),
            'todayLastOut' => $today['last_out']?->toTimeString(),
            'recentPunches' => $employee
                ? $employee->attendances()->orderByDesc('time')->limit(30)->get()
                    ->map(fn (Attendance $punch) => [
                        'id' => $punch->id,
                        'type' => $punch->type,
                        'time' => $punch->time->toDateTimeString(),
                    ])
                : [],
        ]);
    }

    public function checkIn(Request $request): RedirectResponse
    {
        $employee = $request->user()->employee;

        if ($employee === null) {
            return back()->with('error', 'Your account is not linked to an employee record.');
        }

        $employee->checkIn();

        return back()->with('success', 'Checked in.');
    }

    public function checkOut(Request $request): RedirectResponse
    {
        $employee = $request->user()->employee;

        if ($employee === null) {
            return back()->with('error', 'Your account is not linked to an employee record.');
        }

        $employee->checkOut();

        return back()->with('success', 'Checked out.');
    }
}
