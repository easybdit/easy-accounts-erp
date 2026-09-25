<?php

namespace Tests\Feature\HR;

use App\Actions\Payroll\PostSalarySlip;
use App\Models\Accounting\Account;
use App\Models\HR\PayrollComponent;
use App\Models\HR\PayrollPosting;
use Easybdit\LaravelEasyAttendance\Models\Employee;
use Easybdit\LaravelEasyAttendance\Models\SalarySlip;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class PostSalarySlipTest extends TestCase
{
    use RefreshDatabase;

    private function buildSlip(array $attributes = []): SalarySlip
    {
        $employee = Employee::create([
            'employee_code' => 'EMP-001',
            'name' => 'Jane Doe',
            'basic_salary' => 30000,
            'allowances' => ['house_rent' => 5000],
            'status' => 'active',
        ]);

        return SalarySlip::create(array_merge([
            'employee_id' => $employee->id,
            'year' => 2026,
            'month' => 9,
            'basic_salary' => 30000,
            'allowances' => ['house_rent' => 5000],
            'deduction_amount' => 1000,
            'overtime_amount' => 0,
            'special_pay_amount' => 0,
            'net_salary' => 34000,
        ], $attributes));
    }

    private function mapComponent(string $key, string $type, ?Account $account = null): PayrollComponent
    {
        return PayrollComponent::create([
            'name' => $key,
            'type' => $type,
            'account_id' => ($account ?? Account::factory()->create(['type' => $type === 'earning' ? 'expense' : 'liability']))->id,
            'source_component_key' => $key,
            'is_active' => true,
        ]);
    }

    public function test_posting_a_fully_mapped_slip_produces_a_balanced_journal(): void
    {
        $this->mapComponent('basic_salary', 'earning');
        $this->mapComponent('house_rent', 'earning');
        $this->mapComponent('deduction_amount', 'deduction');
        $this->mapComponent('net_salary', 'deduction');

        $slip = $this->buildSlip();

        $posting = app(PostSalarySlip::class)->handle($slip);

        $journal = $posting->journal()->with('entries')->first();

        $this->assertTrue($journal->isBalanced());
        $this->assertSame('35000.0000', $journal->totalDebit());
        $this->assertSame('35000.0000', $journal->totalCredit());
        $this->assertSame('EMP-001', $posting->employee_code);
        $this->assertSame('34000.0000', (string) $posting->net_amount);
    }

    public function test_posting_the_same_slip_twice_is_blocked(): void
    {
        $this->mapComponent('basic_salary', 'earning');
        $this->mapComponent('house_rent', 'earning');
        $this->mapComponent('deduction_amount', 'deduction');
        $this->mapComponent('net_salary', 'deduction');

        $slip = $this->buildSlip();

        app(PostSalarySlip::class)->handle($slip);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('already been posted');

        app(PostSalarySlip::class)->handle($slip);
    }

    public function test_posting_fails_clearly_when_a_component_is_unmapped(): void
    {
        // basic_salary mapped, but the "house_rent" allowance is not.
        $this->mapComponent('basic_salary', 'earning');

        $slip = $this->buildSlip();

        try {
            app(PostSalarySlip::class)->handle($slip);
            $this->fail('Expected a RuntimeException for the unmapped "house_rent" component.');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('house_rent', $e->getMessage());
        }

        $this->assertSame(0, PayrollPosting::count());
    }
}
