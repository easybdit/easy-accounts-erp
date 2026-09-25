<?php

namespace Tests\Feature\HR;

use App\Models\User;
use Easybdit\LaravelEasyAttendance\Models\Department;
use Easybdit\LaravelEasyAttendance\Models\Employee;
use Easybdit\LaravelEasyAttendance\Services\AttendanceSummaryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TeamAttendanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_department_head_sees_their_teams_attendance_but_not_other_departments(): void
    {
        $head = Employee::create(['employee_code' => 'HEAD-2', 'name' => 'Head Two', 'basic_salary' => 40000, 'status' => 'active']);
        $department = Department::create(['name' => 'Support']);
        $department->forceFill(['head_employee_id' => $head->id])->save();
        $otherDepartment = Department::create(['name' => 'Sales']);

        $teamMember = Employee::create([
            'employee_code' => 'TEAM-1', 'name' => 'Team Member', 'basic_salary' => 20000, 'status' => 'active', 'department_id' => $department->id,
        ]);
        $outsider = Employee::create([
            'employee_code' => 'OUT-1', 'name' => 'Outsider', 'basic_salary' => 20000, 'status' => 'active', 'department_id' => $otherDepartment->id,
        ]);

        $teamMember->checkIn(['time' => '2026-08-03 09:05:00']);
        $teamMember->checkOut(['time' => '2026-08-03 18:00:00']);
        $outsider->checkIn(['time' => '2026-08-03 09:05:00']);
        $outsider->checkOut(['time' => '2026-08-03 18:00:00']);

        $service = app(AttendanceSummaryService::class);
        $service->buildOne($teamMember, '2026-08-03');
        $service->buildOne($outsider, '2026-08-03');

        $headUser = User::factory()->create(['employee_id' => $head->id]);
        $headUser->syncRoles(['Employee']);

        $response = $this->actingAs($headUser)->get(route('hr.team-attendance.index', ['year' => 2026, 'month' => 8]));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->where('isDepartmentHead', true)
            ->has('rows', 1)
            ->where('rows.0.employee_code', 'TEAM-1')
        );
    }

    public function test_a_non_department_head_sees_an_empty_state(): void
    {
        $employee = Employee::create(['employee_code' => 'PLAIN-1', 'name' => 'Plain Employee', 'basic_salary' => 20000, 'status' => 'active']);
        $user = User::factory()->create(['employee_id' => $employee->id]);
        $user->syncRoles(['Employee']);

        $response = $this->actingAs($user)->get(route('hr.team-attendance.index'));

        $response->assertInertia(fn ($page) => $page->where('isDepartmentHead', false)->has('rows', 0));
    }
}
