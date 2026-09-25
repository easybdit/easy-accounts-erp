<?php

namespace Tests\Feature\HR;

use App\Models\User;
use Easybdit\LaravelEasyAttendance\Models\Employee;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceCorrectionTest extends TestCase
{
    use RefreshDatabase;

    private function employeeUser(): array
    {
        $employee = Employee::create(['employee_code' => 'EMP-700', 'name' => 'Correction Subject', 'basic_salary' => 20000, 'status' => 'active']);
        $user = User::factory()->create(['employee_id' => $employee->id]);
        $user->syncRoles(['Employee']);

        return [$user, $employee];
    }

    public function test_an_employee_can_request_a_correction_for_a_missing_punch(): void
    {
        [$user] = $this->employeeUser();

        $this->actingAs($user)->post(route('hr.my-attendance-corrections.store'), [
            'date' => '2026-08-05',
            'requested_in' => '09:00',
            'requested_out' => '18:00',
            'reason' => 'Forgot to punch in, device was down.',
        ])->assertRedirect(route('hr.my-attendance-corrections.index'));

        $this->assertDatabaseHas('easyattendance_corrections', ['status' => 'pending', 'reason' => 'Forgot to punch in, device was down.']);
    }

    public function test_approving_a_correction_creates_real_attendance_punches(): void
    {
        [$user, $employee] = $this->employeeUser();
        $admin = User::factory()->create();

        $this->actingAs($user)->post(route('hr.my-attendance-corrections.store'), [
            'date' => '2026-08-05',
            'requested_in' => '09:00',
            'requested_out' => '18:00',
            'reason' => 'Missed punch.',
        ]);

        $correction = $employee->attendanceCorrections()->first();

        $this->actingAs($admin)->post(route('hr.attendance-corrections.approve', $correction))->assertRedirect();

        $this->assertSame('approved', $correction->fresh()->status);
        $this->assertSame(2, $employee->attendances()->whereDate('time', '2026-08-05')->count());
        $this->assertSame(1, $employee->attendances()->where('type', 'check_in')->whereDate('time', '2026-08-05')->count());
        $this->assertSame(1, $employee->attendances()->where('type', 'check_out')->whereDate('time', '2026-08-05')->count());
    }

    public function test_rejecting_a_correction_creates_no_punches(): void
    {
        [$user, $employee] = $this->employeeUser();
        $admin = User::factory()->create();

        $this->actingAs($user)->post(route('hr.my-attendance-corrections.store'), [
            'date' => '2026-08-05', 'requested_in' => '09:00', 'reason' => 'Missed punch.',
        ]);
        $correction = $employee->attendanceCorrections()->first();

        $this->actingAs($admin)->post(route('hr.attendance-corrections.reject', $correction))->assertRedirect();

        $this->assertSame('rejected', $correction->fresh()->status);
        $this->assertSame(0, $employee->attendances()->count());
    }

    public function test_a_role_without_employees_view_cannot_see_all_corrections(): void
    {
        $user = User::factory()->create();
        $user->syncRoles(['Sales']);

        $this->actingAs($user)->get(route('hr.attendance-corrections.index'))->assertForbidden();
    }
}
