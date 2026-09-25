<?php

namespace Tests\Feature\HR;

use App\Models\HR\HrSettings;
use App\Models\User;
use Easybdit\LaravelEasyAttendance\Models\Department;
use Easybdit\LaravelEasyAttendance\Models\Employee;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MultiStepLeaveApprovalTest extends TestCase
{
    use RefreshDatabase;

    private function departmentWithHead(): array
    {
        $head = Employee::create(['employee_code' => 'HEAD-1', 'name' => 'Dept Head', 'basic_salary' => 40000, 'status' => 'active']);
        $department = Department::create(['name' => 'Engineering']);
        $department->forceFill(['head_employee_id' => $head->id])->save();

        $headUser = User::factory()->create(['employee_id' => $head->id]);
        $headUser->syncRoles(['Employee']);

        return [$department, $head, $headUser];
    }

    private function staffInDepartment(int $departmentId): array
    {
        $employee = Employee::create([
            'employee_code' => 'STAFF-1', 'name' => 'Staff Member', 'basic_salary' => 25000, 'status' => 'active', 'department_id' => $departmentId,
        ]);
        $user = User::factory()->create(['employee_id' => $employee->id]);
        $user->syncRoles(['Employee']);

        return [$employee, $user];
    }

    public function test_when_multi_step_approval_is_off_leaves_go_straight_to_hr_as_before(): void
    {
        [$department] = $this->departmentWithHead();
        [, $staffUser] = $this->staffInDepartment($department->id);
        $admin = User::factory()->create();

        $this->actingAs($staffUser)->post(route('hr.my-leaves.store'), [
            'start_date' => '2026-10-01', 'end_date' => '2026-10-01',
        ]);

        $leave = $staffUser->fresh()->employee->leaves()->first();
        $this->assertSame('skipped', $leave->dept_head_status);

        $this->actingAs($admin)->post(route('hr.leaves.approve', $leave))->assertRedirect();
        $this->assertSame('approved', $leave->fresh()->status);
    }

    public function test_multi_step_approval_blocks_hr_until_the_department_head_approves(): void
    {
        HrSettings::current()->update(['multi_step_leave_approval_enabled' => true]);
        [$department, , $headUser] = $this->departmentWithHead();
        [, $staffUser] = $this->staffInDepartment($department->id);
        $admin = User::factory()->create();

        $this->actingAs($staffUser)->post(route('hr.my-leaves.store'), [
            'start_date' => '2026-10-01', 'end_date' => '2026-10-01',
        ]);
        $leave = $staffUser->fresh()->employee->leaves()->first();
        $this->assertSame('pending', $leave->dept_head_status);

        // HR cannot approve yet — still waiting on the department head.
        $this->actingAs($admin)->post(route('hr.leaves.approve', $leave))
            ->assertRedirect()
            ->assertSessionHas('error');
        $this->assertSame('pending', $leave->fresh()->status);

        // The department head sees it and approves.
        $this->actingAs($headUser)->get(route('hr.team-leaves.index'))
            ->assertInertia(fn ($page) => $page->where('isDepartmentHead', true)->has('leaves', 1));
        $this->actingAs($headUser)->post(route('hr.team-leaves.approve', $leave))->assertRedirect();
        $this->assertSame('approved', $leave->fresh()->dept_head_status);
        $this->assertSame('pending', $leave->fresh()->status);

        // Now HR can finish it.
        $this->actingAs($admin)->post(route('hr.leaves.approve', $leave))->assertRedirect();
        $this->assertSame('approved', $leave->fresh()->status);
    }

    public function test_a_department_head_rejecting_is_final_hr_never_sees_it_as_pending(): void
    {
        HrSettings::current()->update(['multi_step_leave_approval_enabled' => true]);
        [$department, , $headUser] = $this->departmentWithHead();
        [, $staffUser] = $this->staffInDepartment($department->id);

        $this->actingAs($staffUser)->post(route('hr.my-leaves.store'), [
            'start_date' => '2026-10-01', 'end_date' => '2026-10-01',
        ]);
        $leave = $staffUser->fresh()->employee->leaves()->first();

        $this->actingAs($headUser)->post(route('hr.team-leaves.reject', $leave))->assertRedirect();

        $this->assertSame('rejected', $leave->fresh()->dept_head_status);
        $this->assertSame('rejected', $leave->fresh()->status);
    }

    public function test_an_employee_who_is_not_a_department_head_cannot_approve_team_leaves(): void
    {
        HrSettings::current()->update(['multi_step_leave_approval_enabled' => true]);
        [$department] = $this->departmentWithHead();
        [, $staffUser] = $this->staffInDepartment($department->id);

        $this->actingAs($staffUser)->post(route('hr.my-leaves.store'), [
            'start_date' => '2026-10-01', 'end_date' => '2026-10-01',
        ]);
        $leave = $staffUser->fresh()->employee->leaves()->first();

        // staffUser is not the department head — trying to approve their
        // own (or anyone else's) leave via the team-leaves endpoint fails.
        $this->actingAs($staffUser)->post(route('hr.team-leaves.approve', $leave))->assertForbidden();
    }
}
