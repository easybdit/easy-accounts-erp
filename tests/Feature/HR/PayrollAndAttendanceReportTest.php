<?php

namespace Tests\Feature\HR;

use App\Actions\Payroll\PostSalarySlip;
use App\Models\Accounting\Account;
use App\Models\HR\PayrollComponent;
use App\Models\User;
use Easybdit\LaravelEasyAttendance\Models\Employee;
use Easybdit\LaravelEasyAttendance\Models\SalarySlip;
use Easybdit\LaravelEasyAttendance\Services\AttendanceSummaryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PayrollAndAttendanceReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_payroll_register_lists_generated_slips_and_their_posted_status(): void
    {
        $admin = User::factory()->create();

        $employee = Employee::create([
            'employee_code' => 'EMP-500', 'name' => 'Report Subject', 'basic_salary' => 30000, 'status' => 'active',
        ]);

        PayrollComponent::create([
            'name' => 'Basic Salary', 'type' => 'earning', 'source_component_key' => 'basic_salary',
            'account_id' => Account::factory()->create(['type' => 'expense'])->id, 'is_active' => true,
        ]);
        PayrollComponent::create([
            'name' => 'Net Salary Payable', 'type' => 'deduction', 'source_component_key' => 'net_salary',
            'account_id' => Account::factory()->create(['type' => 'liability'])->id, 'is_active' => true,
        ]);

        $slip = SalarySlip::create([
            'employee_id' => $employee->id, 'year' => 2026, 'month' => 8,
            'basic_salary' => 30000, 'net_salary' => 30000,
        ]);

        app(PostSalarySlip::class)->handle($slip, $admin->id);

        $response = $this->actingAs($admin)->get(route('reports.payroll-register', ['year' => 2026, 'month' => 8]));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Reports/PayrollRegister')
            ->where('rows.0.employee_code', 'EMP-500')
            // SalarySlip casts money at 2 decimals, not the accounting
            // side's 4 — this report displays the slip's own value as-is.
            ->where('rows.0.net', '30000.00')
            ->where('rows.0.is_posted', true)
        );
    }

    public function test_attendance_summary_counts_present_absent_and_late_days(): void
    {
        $admin = User::factory()->create();

        $employee = Employee::create([
            'employee_code' => 'EMP-501', 'name' => 'Attendance Subject', 'basic_salary' => 20000, 'status' => 'active',
        ]);

        $employee->checkIn(['time' => '2026-08-03 09:05:00']);
        $employee->checkOut(['time' => '2026-08-03 18:00:00']);
        $employee->checkIn(['time' => '2026-08-04 09:05:00']);
        $employee->checkOut(['time' => '2026-08-04 18:00:00']);
        // 2026-08-05 deliberately has no punch at all — an absence.

        // buildOne() for exactly these three dates, not buildForMonth() —
        // the latter fills in every remaining day of August as "absent"
        // too, which would make this assertion about the counting logic
        // itself (not about a full month's worth of real data).
        $service = app(AttendanceSummaryService::class);
        $service->buildOne($employee, '2026-08-03');
        $service->buildOne($employee, '2026-08-04');
        $service->buildOne($employee, '2026-08-05');

        $response = $this->actingAs($admin)->get(route('reports.attendance-summary', ['year' => 2026, 'month' => 8]));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Reports/AttendanceSummaryReport')
            ->where('rows.0.employee_code', 'EMP-501')
            ->where('rows.0.present', 2)
            ->where('rows.0.absent', 1)
        );
    }

    public function test_guest_cannot_view_either_report(): void
    {
        $this->get(route('reports.payroll-register'))->assertRedirect(route('login'));
        $this->get(route('reports.attendance-summary'))->assertRedirect(route('login'));
    }
}
