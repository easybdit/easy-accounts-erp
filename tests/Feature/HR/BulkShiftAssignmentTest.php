<?php

namespace Tests\Feature\HR;

use App\Models\User;
use Easybdit\LaravelEasyAttendance\Models\Employee;
use Easybdit\LaravelEasyAttendance\Models\Shift;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BulkShiftAssignmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_shift_can_be_assigned_to_multiple_employees_at_once(): void
    {
        $admin = User::factory()->create();
        $shift = Shift::create(['name' => 'Night Shift', 'start_time' => '22:00', 'end_time' => '06:00', 'late_grace_minutes' => 10]);

        $employeeA = Employee::create(['employee_code' => 'BULK-1', 'name' => 'Bulk A', 'basic_salary' => 20000, 'status' => 'active']);
        $employeeB = Employee::create(['employee_code' => 'BULK-2', 'name' => 'Bulk B', 'basic_salary' => 20000, 'status' => 'active']);

        $this->actingAs($admin)->post(route('hr.employees.bulk-assign-shift'), [
            'employee_ids' => [$employeeA->id, $employeeB->id],
            'shift_id' => $shift->id,
            'start_date' => '2026-09-01',
        ])->assertRedirect();

        $this->assertSame(1, $employeeA->shiftAssignments()->where('shift_id', $shift->id)->count());
        $this->assertSame(1, $employeeB->shiftAssignments()->where('shift_id', $shift->id)->count());
    }

    public function test_re_running_the_same_bulk_assignment_does_not_create_duplicates(): void
    {
        $admin = User::factory()->create();
        $shift = Shift::create(['name' => 'Day Shift', 'start_time' => '09:00', 'end_time' => '18:00', 'late_grace_minutes' => 15]);
        $employee = Employee::create(['employee_code' => 'BULK-3', 'name' => 'Bulk C', 'basic_salary' => 20000, 'status' => 'active']);

        $payload = [
            'employee_ids' => [$employee->id],
            'shift_id' => $shift->id,
            'start_date' => '2026-09-01',
        ];

        $this->actingAs($admin)->post(route('hr.employees.bulk-assign-shift'), $payload);
        $this->actingAs($admin)->post(route('hr.employees.bulk-assign-shift'), $payload);

        $this->assertSame(1, $employee->shiftAssignments()->count());
    }

    public function test_a_role_without_employees_manage_cannot_bulk_assign(): void
    {
        $user = User::factory()->create();
        $user->syncRoles(['Viewer']);
        $shift = Shift::create(['name' => 'Day Shift', 'start_time' => '09:00', 'end_time' => '18:00', 'late_grace_minutes' => 15]);
        $employee = Employee::create(['employee_code' => 'BULK-4', 'name' => 'Bulk D', 'basic_salary' => 20000, 'status' => 'active']);

        $this->actingAs($user)->post(route('hr.employees.bulk-assign-shift'), [
            'employee_ids' => [$employee->id],
            'shift_id' => $shift->id,
            'start_date' => '2026-09-01',
        ])->assertForbidden();
    }
}
