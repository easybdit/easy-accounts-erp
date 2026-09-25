<?php

namespace Tests\Feature\HR;

use App\Models\HR\HrSettings;
use App\Models\User;
use Easybdit\LaravelEasyAttendance\Models\Employee;
use Easybdit\LaravelEasyAttendance\Models\SpecialWorkingDay;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SpecialWorkingDayTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 'grade' isn't in the package's own Employee::$fillable, so a plain
     * Employee::create([...,'grade'=>...]) silently drops it — same gap
     * EmployeeController::store()/update() works around with forceFill().
     * Test setup needs the same workaround, not just production code.
     */
    private function employeeWithGrade(string $code, string $name, string $grade): Employee
    {
        $employee = Employee::create(['employee_code' => $code, 'name' => $name, 'basic_salary' => 25000, 'status' => 'active']);
        $employee->forceFill(['grade' => $grade])->save();

        return $employee;
    }

    public function test_an_administrator_can_set_an_employees_grade(): void
    {
        $admin = User::factory()->create();
        $employee = Employee::create(['employee_code' => 'EMP-600', 'name' => 'Grade Test', 'basic_salary' => 20000, 'status' => 'active']);

        $this->actingAs($admin)->put(route('hr.employees.update', $employee), [
            'employee_code' => 'EMP-600',
            'name' => 'Grade Test',
            'grade' => 'A',
            'basic_salary' => 20000,
            'status' => 'active',
        ])->assertRedirect();

        $this->assertSame('A', $employee->fresh()->grade);
    }

    public function test_a_blank_payment_amount_defaults_to_the_employees_grade_rate(): void
    {
        $admin = User::factory()->create();
        $employee = $this->employeeWithGrade('EMP-601', 'Grade B Worker', 'B');

        $this->actingAs($admin)->post(route('hr.special-working-days.store'), [
            'employee_id' => $employee->id,
            'date' => '2026-08-14', // a Friday — the demo shift's off day, so this reads as a payable "day_off" type
            'is_payable' => true,
        ])->assertRedirect(route('hr.special-working-days.index'));

        $day = SpecialWorkingDay::first();
        $this->assertSame(700.0, (float) $day->payment_amount);
    }

    public function test_a_supplied_payment_amount_overrides_the_grade_rate(): void
    {
        $admin = User::factory()->create();
        $employee = $this->employeeWithGrade('EMP-602', 'Custom Amount', 'A');

        $this->actingAs($admin)->post(route('hr.special-working-days.store'), [
            'employee_id' => $employee->id,
            'date' => '2026-08-14',
            'is_payable' => true,
            'payment_amount' => 2500,
        ])->assertRedirect();

        $this->assertSame(2500.0, (float) SpecialWorkingDay::first()->payment_amount);
    }

    public function test_custom_grade_rates_from_hr_settings_are_used(): void
    {
        HrSettings::current()->update(['special_working_day_grade_rates' => ['A' => 1500, 'B' => 900, 'C' => 600]]);

        $admin = User::factory()->create();
        $employee = $this->employeeWithGrade('EMP-603', 'Custom Rate', 'C');

        $this->actingAs($admin)->post(route('hr.special-working-days.store'), [
            'employee_id' => $employee->id,
            'date' => '2026-08-14',
            'is_payable' => true,
        ]);

        $this->assertSame(600.0, (float) SpecialWorkingDay::first()->payment_amount);
    }
}
