<?php

namespace Tests\Feature\HR;

use App\Models\User;
use Easybdit\LaravelEasyAttendance\Models\Department;
use Easybdit\LaravelEasyAttendance\Models\Designation;
use Easybdit\LaravelEasyAttendance\Models\Employee;
use Easybdit\LaravelEasyAttendance\Models\Holiday;
use Easybdit\LaravelEasyAttendance\Models\Shift;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmployeeManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_administrator_can_create_a_department_designation_and_employee(): void
    {
        $admin = User::factory()->create();

        $this->actingAs($admin)->post(route('hr.departments.store'), ['name' => 'Engineering'])
            ->assertRedirect(route('hr.departments.index'));
        $department = Department::firstWhere('name', 'Engineering');

        $this->actingAs($admin)->post(route('hr.designations.store'), [
            'department_id' => $department->id,
            'name' => 'Software Engineer',
        ])->assertRedirect(route('hr.designations.index'));
        $designation = Designation::firstWhere('name', 'Software Engineer');

        $this->actingAs($admin)->post(route('hr.employees.store'), [
            'employee_code' => 'EMP-001',
            'name' => 'Jane Doe',
            'department_id' => $department->id,
            'designation_id' => $designation->id,
            'basic_salary' => 40000,
            'allowances' => ['house_rent' => 5000],
            'status' => 'active',
        ])->assertRedirect();

        $this->assertDatabaseHas('easyattendance_employees', [
            'employee_code' => 'EMP-001',
            'department_id' => $department->id,
            'designation_id' => $designation->id,
        ]);
    }

    public function test_a_shift_can_be_created_and_assigned_to_an_employee(): void
    {
        $admin = User::factory()->create();
        $employee = Employee::create(['employee_code' => 'EMP-010', 'name' => 'Karim', 'basic_salary' => 20000, 'status' => 'active']);

        $this->actingAs($admin)->post(route('hr.shifts.store'), [
            'name' => 'General',
            'start_time' => '09:00',
            'end_time' => '18:00',
            'late_grace_minutes' => 15,
            'off_days' => ['Friday'],
        ])->assertRedirect(route('hr.shifts.index'));
        $shift = Shift::firstWhere('name', 'General');

        $this->actingAs($admin)->post(route('hr.employees.shifts.store', $employee), [
            'shift_id' => $shift->id,
            'start_date' => '2026-10-01',
        ])->assertRedirect();

        $this->assertDatabaseHas('easyattendance_employee_shifts', [
            'employee_id' => $employee->id,
            'shift_id' => $shift->id,
        ]);

        $assignment = $employee->shiftAssignments()->first();

        $this->actingAs($admin)->delete(route('hr.employees.shifts.destroy', [$employee, $assignment]))
            ->assertRedirect();

        $this->assertDatabaseCount('easyattendance_employee_shifts', 0);
    }

    public function test_a_holiday_can_be_created(): void
    {
        $admin = User::factory()->create();

        $this->actingAs($admin)->post(route('hr.holidays.store'), [
            'name' => 'Independence Day',
            'date' => '2026-03-26',
            'is_recurring_yearly' => true,
        ])->assertRedirect(route('hr.holidays.index'));

        $this->assertDatabaseHas('easyattendance_holidays', ['name' => 'Independence Day', 'is_recurring_yearly' => true]);
        $this->assertNotNull(Holiday::onDate('2027-03-26'));
    }

    public function test_an_administrator_can_link_and_unlink_a_user_to_an_employee(): void
    {
        $admin = User::factory()->create();
        $employee = Employee::create(['employee_code' => 'EMP-020', 'name' => 'Nasrin', 'basic_salary' => 22000, 'status' => 'active']);
        $loginUser = User::factory()->create();

        $this->actingAs($admin)->post(route('hr.employees.link-user', $employee), [
            'user_id' => $loginUser->id,
        ])->assertRedirect();

        $this->assertSame($employee->id, $loginUser->fresh()->employee_id);

        $this->actingAs($admin)->delete(route('hr.employees.unlink-user', $employee))->assertRedirect();

        $this->assertNull($loginUser->fresh()->employee_id);
    }

    public function test_a_role_without_employees_manage_cannot_create_an_employee(): void
    {
        $user = User::factory()->create();
        $user->syncRoles(['Viewer']);

        $this->actingAs($user)->post(route('hr.employees.store'), [
            'employee_code' => 'EMP-030',
            'name' => 'Blocked',
            'basic_salary' => 10000,
            'status' => 'active',
        ])->assertForbidden();

        $this->assertDatabaseMissing('easyattendance_employees', ['employee_code' => 'EMP-030']);
    }
}
