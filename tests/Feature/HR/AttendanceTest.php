<?php

namespace Tests\Feature\HR;

use App\Models\User;
use Easybdit\LaravelEasyAttendance\Models\Employee;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceTest extends TestCase
{
    use RefreshDatabase;

    private function employeeUser(): array
    {
        $employee = Employee::create([
            'employee_code' => 'EMP-400',
            'name' => 'Test Employee',
            'basic_salary' => 20000,
            'status' => 'active',
        ]);

        $user = User::factory()->create(['employee_id' => $employee->id]);
        $user->syncRoles(['Employee']);

        return [$user, $employee];
    }

    public function test_an_administrator_can_record_a_manual_punch_for_an_employee(): void
    {
        $admin = User::factory()->create();
        $employee = Employee::create([
            'employee_code' => 'EMP-401',
            'name' => 'Punch Target',
            'basic_salary' => 20000,
            'status' => 'active',
        ]);

        $this->actingAs($admin)->post(route('hr.attendance.store'), [
            'employee_id' => $employee->id,
            'type' => 'check_in',
            'date' => '2026-08-10',
            'time' => '09:05',
        ])->assertRedirect();

        $this->assertDatabaseHas('easyattendance_attendances', [
            'subject_type' => Employee::class,
            'subject_id' => $employee->id,
            'type' => 'check_in',
        ]);
    }

    public function test_an_employee_can_check_in_and_out_for_themselves(): void
    {
        [$user, $employee] = $this->employeeUser();

        $this->actingAs($user)->post(route('hr.my-attendance.check-in'))->assertRedirect();
        $this->actingAs($user)->post(route('hr.my-attendance.check-out'))->assertRedirect();

        $this->assertSame(2, $employee->attendances()->count());
        $this->assertSame(1, $employee->attendances()->where('type', 'check_in')->count());
        $this->assertSame(1, $employee->attendances()->where('type', 'check_out')->count());
    }

    public function test_a_user_not_linked_to_an_employee_cannot_check_in(): void
    {
        $user = User::factory()->create();
        $user->syncRoles(['Employee']);

        $this->actingAs($user)->post(route('hr.my-attendance.check-in'))
            ->assertRedirect()
            ->assertSessionHas('error');
    }

    public function test_a_role_without_employees_view_cannot_see_the_admin_attendance_list(): void
    {
        // 'Viewer' deliberately not used here: it holds every "%.view"
        // permission (including employees.view), so it would pass. 'Sales'
        // has neither employees.view nor employees.manage.
        $user = User::factory()->create();
        $user->syncRoles(['Sales']);

        $this->actingAs($user)->get(route('hr.attendance.index'))->assertForbidden();
    }
}
