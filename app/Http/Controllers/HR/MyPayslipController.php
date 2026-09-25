<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use Barryvdh\DomPDF\Facade\Pdf;
use Easybdit\LaravelEasyAttendance\Models\SalarySlip;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Self-service: only the current user's own linked employee's slips —
 * never another employee's (payroll.own, not payroll.manage/view).
 */
class MyPayslipController extends Controller
{
    public function index(Request $request): Response
    {
        $employee = $request->user()->employee;

        return Inertia::render('HR/MyPayslips/Index', [
            'employee' => $employee?->only(['id', 'employee_code', 'name']),
            'slips' => $employee
                ? $employee->salarySlips()->orderByDesc('year')->orderByDesc('month')->get()
                : [],
        ]);
    }

    public function pdf(Request $request, SalarySlip $salarySlip): HttpResponse
    {
        $employee = $request->user()->employee;

        abort_if($employee === null || $salarySlip->employee_id !== $employee->id, 403);

        $salarySlip->loadMissing('employee');

        $pdf = Pdf::loadView('pdfs.salary-slip', [
            'slip' => $salarySlip,
            'employee' => $salarySlip->employee,
            'appName' => config('app.name'),
        ]);

        return $pdf->download(sprintf('salary-slip-%04d-%02d.pdf', $salarySlip->year, $salarySlip->month));
    }
}
