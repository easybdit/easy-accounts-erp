<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Concerns\FormatsPlainDates;
use App\Http\Controllers\Controller;
use Easybdit\LaravelEasyAttendance\Models\Leave;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class LeaveController extends Controller
{
    use FormatsPlainDates;

    public function index(): Response
    {
        return Inertia::render('HR/Leaves/Index', [
            'leaves' => Leave::query()
                ->with(['employee:id,employee_code,name', 'leaveType:id,name'])
                ->orderByDesc('start_date')
                ->get()
                ->map(fn (Leave $leave) => $this->withPlainDates($leave, ['start_date', 'end_date'])),
        ]);
    }

    public function approve(Request $request, Leave $leave): RedirectResponse
    {
        if (! in_array($leave->dept_head_status, ['approved', 'skipped'], true)) {
            return back()->with('error', 'This request is still waiting on the department head\'s approval.');
        }

        $leave->approve($request->user()->id, $request->input('note'));

        return back()->with('success', 'Leave request approved.');
    }

    public function reject(Request $request, Leave $leave): RedirectResponse
    {
        $leave->reject($request->user()->id, $request->input('note'));

        return back()->with('success', 'Leave request rejected.');
    }
}
