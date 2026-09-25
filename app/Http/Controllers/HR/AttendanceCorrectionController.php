<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Concerns\FormatsPlainDates;
use App\Http\Controllers\Controller;
use Easybdit\LaravelEasyAttendance\Models\AttendanceCorrection;
use Easybdit\LaravelEasyAttendance\Models\Employee;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AttendanceCorrectionController extends Controller
{
    use FormatsPlainDates;

    public function index(): Response
    {
        return Inertia::render('HR/AttendanceCorrections/Index', [
            'corrections' => AttendanceCorrection::query()
                ->where('subject_type', Employee::class)
                ->with('subject:id,employee_code,name')
                ->orderByDesc('date')
                ->get()
                ->map(fn (AttendanceCorrection $correction) => [
                    ...$this->withPlainDates($correction, ['date']),
                    'employee' => $correction->subject?->only(['id', 'employee_code', 'name']),
                ]),
        ]);
    }

    public function approve(Request $request, AttendanceCorrection $attendanceCorrection): RedirectResponse
    {
        $attendanceCorrection->approve($request->user()->id, $request->input('note'));

        return back()->with('success', 'Correction approved — attendance updated.');
    }

    public function reject(Request $request, AttendanceCorrection $attendanceCorrection): RedirectResponse
    {
        $attendanceCorrection->reject($request->user()->id, $request->input('note'));

        return back()->with('success', 'Correction rejected.');
    }
}
