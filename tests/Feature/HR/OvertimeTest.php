<?php

namespace Tests\Feature\HR;

use App\Models\User;
use Easybdit\LaravelEasyAttendance\Models\Employee;
use Easybdit\LaravelEasyAttendance\Models\OvertimeRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OvertimeTest extends TestCase
{
    use RefreshDatabase;

    private function pendingRecord(): OvertimeRecord
    {
        $employee = Employee::create([
            'employee_code' => 'EMP-200',
            'name' => 'Karim Hasan',
            'basic_salary' => 25000,
            'status' => 'active',
        ]);

        return OvertimeRecord::create([
            'employee_id' => $employee->id,
            'date' => '2026-09-20',
            'ot_hours' => 2,
            'ot_rate' => 150,
            'ot_amount' => 300,
            'source' => 'auto',
            'status' => 'pending',
        ]);
    }

    public function test_an_administrator_can_approve_an_overtime_record(): void
    {
        $record = $this->pendingRecord();
        $admin = User::factory()->create();

        $this->actingAs($admin)->post(route('hr.overtime.approve', $record))->assertRedirect();

        $this->assertSame('approved', $record->fresh()->status);
        $this->assertSame($admin->id, $record->fresh()->approved_by);
    }

    public function test_an_administrator_can_reject_an_overtime_record(): void
    {
        $record = $this->pendingRecord();
        $admin = User::factory()->create();

        $this->actingAs($admin)->post(route('hr.overtime.reject', $record))->assertRedirect();

        $this->assertSame('rejected', $record->fresh()->status);
    }

    public function test_a_role_without_overtime_manage_cannot_view_the_list(): void
    {
        $user = User::factory()->create();
        $user->syncRoles(['Viewer']);

        $this->actingAs($user)->get(route('hr.overtime.index'))->assertForbidden();
    }
}
