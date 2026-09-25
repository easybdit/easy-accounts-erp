<?php

namespace Tests\Feature\HR;

use App\Models\User;
use Easybdit\LaravelEasyAttendance\Models\Employee;
use Easybdit\LaravelEasyAttendance\Models\LeaveType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeaveTest extends TestCase
{
    use RefreshDatabase;

    private function employeeUser(): User
    {
        $employee = Employee::create([
            'employee_code' => 'EMP-100',
            'name' => 'Rina Akter',
            'basic_salary' => 20000,
            'status' => 'active',
        ]);

        $user = User::factory()->create(['employee_id' => $employee->id]);
        $user->syncRoles(['Employee']);

        return $user;
    }

    public function test_an_employee_can_apply_for_their_own_leave(): void
    {
        $leaveType = LeaveType::create(['name' => 'Annual', 'days_allowed_per_year' => 10]);
        $user = $this->employeeUser();

        $this->actingAs($user)->post(route('hr.my-leaves.store'), [
            'leave_type_id' => $leaveType->id,
            'start_date' => '2026-10-01',
            'end_date' => '2026-10-02',
            'reason' => 'Family event',
        ])->assertRedirect(route('hr.my-leaves.index'));

        $this->assertDatabaseHas('easyattendance_leaves', [
            'employee_id' => $user->employee_id,
            'status' => 'pending',
        ]);
    }

    public function test_an_administrator_can_approve_a_leave_request_and_balance_reflects_it(): void
    {
        $leaveType = LeaveType::create(['name' => 'Annual', 'days_allowed_per_year' => 10]);
        $user = $this->employeeUser();

        $this->actingAs($user)->post(route('hr.my-leaves.store'), [
            'leave_type_id' => $leaveType->id,
            'start_date' => '2026-10-01',
            'end_date' => '2026-10-02',
            'reason' => 'Family event',
        ]);

        $leave = $user->employee->leaves()->first();
        $admin = User::factory()->create();

        $this->actingAs($admin)->post(route('hr.leaves.approve', $leave))
            ->assertRedirect();

        $this->assertSame('approved', $leave->fresh()->status);
        $balance = $leaveType->fresh()->balanceForEmployee($user->employee->fresh());
        $this->assertSame(2, $balance['used']);
        $this->assertSame(8, $balance['remaining']);
    }

    public function test_an_employee_cannot_view_the_admin_leave_list(): void
    {
        $user = $this->employeeUser();

        $this->actingAs($user)->get(route('hr.leaves.index'))->assertForbidden();
    }

    public function test_a_user_not_linked_to_an_employee_cannot_apply_for_leave(): void
    {
        $user = User::factory()->create();
        $user->syncRoles(['Employee']);

        $response = $this->actingAs($user)->post(route('hr.my-leaves.store'), [
            'start_date' => '2026-10-01',
            'end_date' => '2026-10-02',
        ]);

        $response->assertSessionHas('error');
        $this->assertDatabaseCount('easyattendance_leaves', 0);
    }
}
