<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Concerns\FormatsPlainDates;
use App\Http\Controllers\Controller;
use Easybdit\LaravelEasyAttendance\Models\OvertimeRecord;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class OvertimeController extends Controller
{
    use FormatsPlainDates;

    public function index(): Response
    {
        return Inertia::render('HR/Overtime/Index', [
            'records' => OvertimeRecord::query()
                ->with('employee:id,employee_code,name')
                ->orderByDesc('date')
                ->get()
                ->map(fn (OvertimeRecord $record) => $this->withPlainDates($record, ['date'])),
        ]);
    }

    public function approve(Request $request, OvertimeRecord $overtimeRecord): RedirectResponse
    {
        $overtimeRecord->approve($request->user()->id, $request->input('note'));

        return back()->with('success', 'Overtime record approved.');
    }

    public function reject(Request $request, OvertimeRecord $overtimeRecord): RedirectResponse
    {
        $overtimeRecord->reject($request->user()->id, $request->input('note'));

        return back()->with('success', 'Overtime record rejected.');
    }
}
