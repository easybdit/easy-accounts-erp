<?php

namespace App\Http\Controllers\HR;

use App\Actions\Payroll\PostSalarySlip;
use App\Http\Controllers\Controller;
use App\Models\HR\PayrollPosting;
use Easybdit\LaravelEasyAttendance\Models\SalarySlip;
use Easybdit\LaravelEasyAttendance\Services\SalaryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use RuntimeException;

class PayrollController extends Controller
{
    public function index(Request $request): Response
    {
        $year = (int) $request->input('year', now()->year);
        $month = (int) $request->input('month', now()->month);

        $postedSlipIds = PayrollPosting::where('salary_slip_type', SalarySlip::class)
            ->pluck('salary_slip_id');

        $slips = SalarySlip::query()
            ->with('employee:id,employee_code,name')
            ->forMonth($year, $month)
            ->get()
            ->map(fn (SalarySlip $slip) => [
                'id' => $slip->id,
                'employee' => $slip->employee?->only(['id', 'employee_code', 'name']),
                'year' => $slip->year,
                'month' => $slip->month,
                'gross_salary' => $slip->gross_salary,
                'deduction_amount' => $slip->deduction_amount,
                'net_salary' => $slip->net_salary,
                'is_posted' => $postedSlipIds->contains($slip->id),
            ]);

        return Inertia::render('HR/Payroll/Index', [
            'slips' => $slips,
            'year' => $year,
            'month' => $month,
        ]);
    }

    public function generate(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'year' => ['required', 'integer', 'min:2000', 'max:2100'],
            'month' => ['required', 'integer', 'min:1', 'max:12'],
        ]);

        app(SalaryService::class)->generateForMonth($validated['year'], $validated['month']);

        return redirect()
            ->route('hr.payroll.index', ['year' => $validated['year'], 'month' => $validated['month']])
            ->with('success', 'Salary slips generated for the month.');
    }

    public function post(Request $request, SalarySlip $salarySlip, PostSalarySlip $action): RedirectResponse
    {
        try {
            $action->handle($salarySlip, $request->user()->id);
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Salary slip posted to accounts.');
    }
}
