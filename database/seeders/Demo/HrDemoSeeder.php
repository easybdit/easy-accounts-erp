<?php

namespace Database\Seeders\Demo;

use App\Models\Accounting\Account;
use App\Models\HR\PayrollComponent;
use App\Models\User;
use Carbon\CarbonPeriod;
use Database\Seeders\Accounting\ChartOfAccountsSeeder;
use Database\Seeders\Security\PermissionSeeder;
use Database\Seeders\Security\RoleSeeder;
use Easybdit\LaravelEasyAttendance\Models\Department;
use Easybdit\LaravelEasyAttendance\Models\Designation;
use Easybdit\LaravelEasyAttendance\Models\Employee;
use Easybdit\LaravelEasyAttendance\Models\Holiday;
use Easybdit\LaravelEasyAttendance\Models\LeaveType;
use Easybdit\LaravelEasyAttendance\Models\Shift;
use Illuminate\Database\Seeder;

/**
 * Stand-alone HR/Payroll demo dataset — four employees with a shift,
 * a full historical month (August 2026) of check-in/out punches, one
 * approved leave, and the payroll component → account mappings needed
 * to generate and post a real salary run end to end:
 *
 *   php artisan db:seed --class="Database\Seeders\Demo\HrDemoSeeder"
 *
 * Independent of which vertical demo (hosting/education/hospital) is
 * active — only assumes the core Chart of Accounts exists.
 */
class HrDemoSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(PermissionSeeder::class);
        $this->call(RoleSeeder::class);
        $this->call(ChartOfAccountsSeeder::class);

        $department = Department::updateOrCreate(['name' => 'Operations']);
        $managerRole = Designation::updateOrCreate(['name' => 'Manager', 'department_id' => $department->id]);
        $sysAdminRole = Designation::updateOrCreate(['name' => 'System Administrator', 'department_id' => $department->id]);
        $supportRole = Designation::updateOrCreate(['name' => 'Support Engineer', 'department_id' => $department->id]);

        $shift = Shift::updateOrCreate(['name' => 'General Shift'], [
            'start_time' => '09:00',
            'end_time' => '18:00',
            'late_grace_minutes' => 15,
            'off_days' => ['Friday'],
        ]);

        $employees = [
            'murad' => Employee::updateOrCreate(['employee_code' => '5114'], [
                'name' => 'Murad', 'designation_id' => $managerRole->id, 'department_id' => $department->id,
                'basic_salary' => 60000, 'allowances' => ['house_rent' => 15000], 'status' => 'active',
                'joined_at' => '2024-01-01',
            ]),
            'tuhin' => Employee::updateOrCreate(['employee_code' => '5150'], [
                'name' => 'Tuhin', 'designation_id' => $sysAdminRole->id, 'department_id' => $department->id,
                'basic_salary' => 35000, 'allowances' => ['house_rent' => 8000], 'status' => 'active',
                'joined_at' => '2024-06-01',
            ]),
            'arman' => Employee::updateOrCreate(['employee_code' => '5160'], [
                'name' => 'Arman', 'designation_id' => $supportRole->id, 'department_id' => $department->id,
                'basic_salary' => 25000, 'allowances' => ['house_rent' => 5000], 'status' => 'active',
                'joined_at' => '2025-01-01',
            ]),
            'antu' => Employee::updateOrCreate(['employee_code' => '5125'], [
                'name' => 'Antu', 'designation_id' => $supportRole->id, 'department_id' => $department->id,
                'basic_salary' => 22000, 'allowances' => ['house_rent' => 5000], 'status' => 'active',
                'joined_at' => '2025-03-01',
            ]),
        ];

        foreach ($employees as $employee) {
            if (! $employee->shiftAssignments()->where('shift_id', $shift->id)->exists()) {
                $employee->shiftAssignments()->create(['shift_id' => $shift->id, 'start_date' => '2026-08-01']);
            }
        }

        // Murad is the person actually running this demo — link the
        // existing hosting-demo admin login so self-service (My Leave, My
        // Attendance, My Payslips) can be tried without a second login.
        $admin = User::where('email', 'admin@hosting-demo.test')->first();
        if ($admin && $admin->employee_id === null) {
            $admin->update(['employee_id' => $employees['murad']->id]);
        }

        Holiday::updateOrCreate(['name' => 'Company Foundation Day'], ['date' => '2026-08-15', 'is_recurring_yearly' => false]);

        $leaveType = LeaveType::updateOrCreate(['name' => 'Casual Leave'], ['days_allowed_per_year' => 10]);

        if ($employees['antu']->leaves()->count() === 0) {
            $leave = $employees['antu']->requestLeave([
                'leave_type_id' => $leaveType->id,
                'start_date' => '2026-08-10',
                'end_date' => '2026-08-11',
                'reason' => 'Family emergency',
            ]);
            $leave->approve($admin?->id);
        }

        // A per-employee absent/late pattern across August 2026 (a
        // complete past month) — Fridays are the shift's off day, so
        // they're skipped rather than counted as absences.
        $absentDays = ['murad' => [], 'tuhin' => [22], 'arman' => [5, 19], 'antu' => []];
        $lateDays = ['murad' => [], 'tuhin' => [8], 'arman' => [12, 26], 'antu' => [3, 17]];

        foreach ($employees as $key => $employee) {
            if ($employee->attendances()->exists()) {
                continue;
            }

            foreach (CarbonPeriod::create('2026-08-01', '2026-08-31') as $date) {
                if ($date->isFriday()) {
                    continue;
                }

                $day = (int) $date->format('j');

                if (in_array($day, $absentDays[$key], true)) {
                    continue;
                }

                // Antu's approved-leave days shouldn't also get a punch.
                if ($key === 'antu' && $day >= 10 && $day <= 11) {
                    continue;
                }

                $checkInTime = in_array($day, $lateDays[$key], true) ? '09:28:00' : '09:05:00';

                $employee->checkIn(['time' => $date->toDateString().' '.$checkInTime]);
                $employee->checkOut(['time' => $date->toDateString().' 18:05:00']);
            }
        }

        $expense = fn (string $code) => Account::where('code', $code)->firstOrFail();
        $liability = fn (string $code) => Account::where('code', $code)->firstOrFail();

        PayrollComponent::updateOrCreate(['source_component_key' => 'basic_salary'], [
            'name' => 'Basic Salary', 'type' => 'earning', 'account_id' => $expense('5004')->id, 'is_active' => true,
        ]);
        PayrollComponent::updateOrCreate(['source_component_key' => 'house_rent'], [
            'name' => 'House Rent Allowance', 'type' => 'earning', 'account_id' => $expense('5004')->id, 'is_active' => true,
        ]);
        PayrollComponent::updateOrCreate(['source_component_key' => 'deduction_amount'], [
            'name' => 'Attendance Deduction', 'type' => 'deduction', 'account_id' => $expense('5004')->id, 'is_active' => true,
        ]);
        PayrollComponent::updateOrCreate(['source_component_key' => 'net_salary'], [
            'name' => 'Net Salary Payable', 'type' => 'deduction', 'account_id' => $liability('2004')->id, 'is_active' => true,
        ]);
    }
}
