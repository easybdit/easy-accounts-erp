<?php

namespace App\Actions\Payroll;

use App\Actions\Accounting\PostJournal;
use App\Models\HR\PayrollComponent;
use App\Models\HR\PayrollPosting;
use Easybdit\LaravelEasyAttendance\Models\SalarySlip;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Turns one attendance-package salary slip into a balanced journal via the
 * app's single shared posting entry point (PostJournal), then records a
 * durable, package-independent snapshot in payroll_postings.
 *
 * Every earning/deduction line on the slip must be mapped to a Chart of
 * Accounts account through payroll_components first — this is deliberate:
 * silently dropping an unmapped amount would misstate the books.
 */
class PostSalarySlip
{
    public function __construct(private PostJournal $postJournal) {}

    public function handle(SalarySlip $slip, ?int $postedById = null): PayrollPosting
    {
        if ($this->alreadyPosted($slip)) {
            throw new RuntimeException('This salary slip has already been posted to accounts.');
        }

        $slip->loadMissing('employee');
        $employee = $slip->employee;

        $components = PayrollComponent::where('is_active', true)->get()->keyBy('source_component_key');

        $lines = [];
        $totalEarnings = '0.0000';
        $totalDeductions = '0.0000';

        $addLine = function (string $key, string $label, string $amount, string $side) use (
            &$lines, $components, &$totalEarnings, &$totalDeductions
        ) {
            if (bccomp($amount, '0', 4) === 0) {
                return;
            }

            $component = $components->get($key);

            if ($component === null) {
                throw new RuntimeException(
                    "No payroll component is configured for \"{$label}\" (key: {$key}). Map it to a Chart of Accounts account before posting."
                );
            }

            $lines[] = [
                'account_id' => $component->account_id,
                'debit' => $side === 'debit' ? $amount : '0',
                'credit' => $side === 'credit' ? $amount : '0',
                'description' => $component->name,
            ];

            if ($side === 'debit') {
                $totalEarnings = bcadd($totalEarnings, $amount, 4);
            } else {
                $totalDeductions = bcadd($totalDeductions, $amount, 4);
            }
        };

        $addLine('basic_salary', 'Basic Salary', (string) $slip->basic_salary, 'debit');

        foreach ($slip->allowances ?? [] as $key => $amount) {
            $addLine($key, $key, (string) $amount, 'debit');
        }

        $addLine('overtime_amount', 'Overtime', (string) $slip->overtime_amount, 'debit');
        $addLine('special_pay_amount', 'Special Pay', (string) $slip->special_pay_amount, 'debit');
        $addLine('deduction_amount', 'Attendance Deduction', (string) $slip->deduction_amount, 'credit');
        $addLine('net_salary', 'Net Salary Payable', (string) $slip->net_salary, 'credit');

        return DB::transaction(function () use ($slip, $employee, $lines, $totalEarnings, $totalDeductions, $postedById) {
            $journal = $this->postJournal->handle([
                'date' => now()->toDateString(),
                'reference' => sprintf('Salary %04d-%02d', $slip->year, $slip->month),
                'description' => "Salary for {$employee->name} ({$slip->year}-{$slip->month})",
                'created_by' => $postedById,
                'source_type' => SalarySlip::class,
                'source_id' => $slip->id,
                'lines' => $lines,
            ]);

            return PayrollPosting::create([
                'salary_slip_type' => SalarySlip::class,
                'salary_slip_id' => $slip->id,
                'journal_id' => $journal->id,
                'gross_amount' => $totalEarnings,
                'total_deductions' => $totalDeductions,
                'net_amount' => (string) $slip->net_salary,
                'employee_name' => $employee->name,
                'employee_code' => $employee->employee_code,
                'period_year' => $slip->year,
                'period_month' => $slip->month,
                'posted_by' => $postedById,
                'posted_at' => now(),
            ]);
        });
    }

    private function alreadyPosted(SalarySlip $slip): bool
    {
        return PayrollPosting::where('salary_slip_type', SalarySlip::class)
            ->where('salary_slip_id', $slip->id)
            ->exists();
    }
}
