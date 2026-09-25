<?php

namespace Tests\Feature\HR;

use App\Models\User;
use Easybdit\LaravelEasyAttendance\Models\Employee;
use Easybdit\LaravelEasyAttendance\Models\SalarySlip;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MyPayslipTest extends TestCase
{
    use RefreshDatabase;

    private function employeeWithSlip(string $code): array
    {
        $employee = Employee::create([
            'employee_code' => $code,
            'name' => 'Test Employee',
            'basic_salary' => 30000,
            'allowances' => ['house_rent' => 5000],
            'status' => 'active',
        ]);

        $slip = SalarySlip::create([
            'employee_id' => $employee->id,
            'year' => 2026,
            'month' => 9,
            'basic_salary' => 30000,
            'allowances' => ['house_rent' => 5000],
            'net_salary' => 35000,
        ]);

        $user = User::factory()->create(['employee_id' => $employee->id]);
        $user->syncRoles(['Employee']);

        return [$user, $slip];
    }

    public function test_an_employee_can_download_their_own_payslip(): void
    {
        [$user, $slip] = $this->employeeWithSlip('EMP-300');

        $response = $this->actingAs($user)->get(route('hr.my-payslips.pdf', $slip));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
    }

    public function test_an_employee_cannot_download_another_employees_payslip(): void
    {
        [, $slip] = $this->employeeWithSlip('EMP-301');
        [$otherUser] = $this->employeeWithSlip('EMP-302');

        $this->actingAs($otherUser)->get(route('hr.my-payslips.pdf', $slip))->assertForbidden();
    }

    public function test_a_user_not_linked_to_an_employee_cannot_download_any_payslip(): void
    {
        [, $slip] = $this->employeeWithSlip('EMP-303');
        $unlinkedUser = User::factory()->create();
        $unlinkedUser->syncRoles(['Employee']);

        $this->actingAs($unlinkedUser)->get(route('hr.my-payslips.pdf', $slip))->assertForbidden();
    }
}
