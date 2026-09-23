<?php

namespace Tests\Feature\Purchases;

use App\Actions\Accounting\PostJournal;
use App\Actions\Purchases\MakePayment;
use App\Actions\Purchases\PostBill;
use App\Models\Accounting\Account;
use App\Models\Contacts\Vendor;
use App\Models\Purchases\Bill;
use App\Models\Purchases\VendorPayment;
use App\Models\Tax\WithholdingTaxRate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class VendorPaymentTest extends TestCase
{
    use RefreshDatabase;

    private function postedBill(array $overrides = []): Bill
    {
        $vendor = $overrides['vendor'] ?? Vendor::factory()->create();
        $payable = Account::factory()->create(['type' => 'liability']);
        $expense = Account::factory()->create(['type' => 'expense']);

        $bill = Bill::factory()->create([
            'vendor_id' => $vendor->id,
            'payable_account_id' => $payable->id,
            'status' => 'draft',
            'total' => 0,
        ]);
        $bill->items()->create([
            'account_id' => $expense->id,
            'description' => 'Line',
            'quantity' => 1,
            'unit_price' => $overrides['total'] ?? 1000,
            'discount' => 0,
            'line_total' => $overrides['total'] ?? 1000,
        ]);

        return (new PostBill(new PostJournal))->handle($bill->fresh());
    }

    public function test_guest_cannot_view_vendor_payments(): void
    {
        $this->get(route('purchases.vendor-payments.index'))->assertRedirect(route('login'));
    }

    public function test_full_payment_marks_bill_fully_paid(): void
    {
        $user = User::factory()->create();
        $bill = $this->postedBill(['total' => 1000]);
        $cash = Account::factory()->create(['type' => 'asset']);

        $response = $this->actingAs($user)->post(route('purchases.vendor-payments.store'), [
            'vendor_id' => $bill->vendor_id,
            'payment_account_id' => $cash->id,
            'payment_date' => '2026-01-20',
            'amount' => 1000,
            'allocations' => [
                ['bill_id' => $bill->id, 'amount' => 1000],
            ],
        ]);

        $response->assertRedirect();
        $this->assertTrue($bill->fresh()->isFullyPaid());
    }

    public function test_partial_payment_reduces_amount_due(): void
    {
        $user = User::factory()->create();
        $bill = $this->postedBill(['total' => 1000]);
        $cash = Account::factory()->create(['type' => 'asset']);

        $this->actingAs($user)->post(route('purchases.vendor-payments.store'), [
            'vendor_id' => $bill->vendor_id,
            'payment_account_id' => $cash->id,
            'payment_date' => '2026-01-20',
            'amount' => 400,
            'allocations' => [
                ['bill_id' => $bill->id, 'amount' => 400],
            ],
        ]);

        $this->assertSame('600.0000', $bill->fresh()->amountDue());
    }

    public function test_allocated_total_must_equal_payment_amount(): void
    {
        $user = User::factory()->create();
        $bill = $this->postedBill(['total' => 1000]);
        $cash = Account::factory()->create(['type' => 'asset']);

        $response = $this->actingAs($user)->post(route('purchases.vendor-payments.store'), [
            'vendor_id' => $bill->vendor_id,
            'payment_account_id' => $cash->id,
            'payment_date' => '2026-01-20',
            'amount' => 500,
            'allocations' => [
                ['bill_id' => $bill->id, 'amount' => 400],
            ],
        ]);

        $response->assertSessionHasErrors('allocations');
        $this->assertDatabaseCount('vendor_payments', 0);
    }

    public function test_cannot_allocate_to_another_vendors_bill(): void
    {
        $user = User::factory()->create();
        $bill = $this->postedBill(['total' => 1000]);
        $otherVendor = Vendor::factory()->create();
        $cash = Account::factory()->create(['type' => 'asset']);

        $response = $this->actingAs($user)->post(route('purchases.vendor-payments.store'), [
            'vendor_id' => $otherVendor->id,
            'payment_account_id' => $cash->id,
            'payment_date' => '2026-01-20',
            'amount' => 1000,
            'allocations' => [
                ['bill_id' => $bill->id, 'amount' => 1000],
            ],
        ]);

        $response->assertSessionHasErrors('allocations.0.bill_id');
    }

    public function test_payment_account_must_be_an_asset_account(): void
    {
        $user = User::factory()->create();
        $bill = $this->postedBill(['total' => 1000]);
        $expenseAsPayment = Account::factory()->create(['type' => 'expense']);

        $response = $this->actingAs($user)->post(route('purchases.vendor-payments.store'), [
            'vendor_id' => $bill->vendor_id,
            'payment_account_id' => $expenseAsPayment->id,
            'payment_date' => '2026-01-20',
            'amount' => 1000,
            'allocations' => [
                ['bill_id' => $bill->id, 'amount' => 1000],
            ],
        ]);

        $response->assertSessionHasErrors('payment_account_id');
    }

    public function test_posting_creates_a_balanced_journal_debiting_the_bill_payable_account(): void
    {
        $user = User::factory()->create();
        $bill = $this->postedBill(['total' => 1000]);
        $cash = Account::factory()->create(['type' => 'asset']);

        $this->actingAs($user)->post(route('purchases.vendor-payments.store'), [
            'vendor_id' => $bill->vendor_id,
            'payment_account_id' => $cash->id,
            'payment_date' => '2026-01-20',
            'amount' => 1000,
            'allocations' => [
                ['bill_id' => $bill->id, 'amount' => 1000],
            ],
        ]);

        $payment = VendorPayment::first();
        $this->assertTrue($payment->journal->isBalanced());
        $this->assertSame('1000.0000', $payment->journal->totalCredit());
    }

    public function test_the_show_page_renders(): void
    {
        $user = User::factory()->create();
        $bill = $this->postedBill(['total' => 1000]);
        $cash = Account::factory()->create(['type' => 'asset']);

        $this->actingAs($user)->post(route('purchases.vendor-payments.store'), [
            'vendor_id' => $bill->vendor_id,
            'payment_account_id' => $cash->id,
            'payment_date' => '2026-01-20',
            'amount' => 1000,
            'allocations' => [
                ['bill_id' => $bill->id, 'amount' => 1000],
            ],
        ]);

        $payment = VendorPayment::first();

        $this->actingAs($user)->get(route('purchases.vendor-payments.show', $payment))->assertOk();
    }

    public function test_amount_paid_is_formatted_with_four_decimals(): void
    {
        $user = User::factory()->create();
        $bill = $this->postedBill(['total' => 1000]);
        $cash = Account::factory()->create(['type' => 'asset']);

        $this->actingAs($user)->post(route('purchases.vendor-payments.store'), [
            'vendor_id' => $bill->vendor_id,
            'payment_account_id' => $cash->id,
            'payment_date' => '2026-01-20',
            'amount' => 400,
            'allocations' => [['bill_id' => $bill->id, 'amount' => 400]],
        ]);

        // Regression: amountPaid() must not return a raw, unnormalized SQL
        // SUM() result (e.g. "400" instead of "400.0000").
        $this->assertSame('400.0000', $bill->fresh()->amountPaid());
    }

    public function test_action_rejects_mismatched_allocation_total_before_writing_anything(): void
    {
        $bill = $this->postedBill(['total' => 1000]);
        $cash = Account::factory()->create(['type' => 'asset']);

        $this->expectException(RuntimeException::class);

        try {
            app(MakePayment::class)->handle([
                'vendor_id' => $bill->vendor_id,
                'payment_account_id' => $cash->id,
                'payment_date' => '2026-01-20',
                'reference' => null,
                'method' => null,
                'amount' => 1000,
                'notes' => null,
                'created_by' => null,
                'allocations' => [['bill_id' => $bill->id, 'amount' => 999]],
            ]);
        } finally {
            $this->assertDatabaseCount('vendor_payments', 0);
        }
    }

    public function test_withholding_tax_reduces_cash_paid_while_still_clearing_the_bill_in_full(): void
    {
        $user = User::factory()->create();
        $bill = $this->postedBill(['total' => 1000]);
        $cash = Account::factory()->create(['type' => 'asset']);
        $tdsLiability = Account::factory()->create(['type' => 'liability']);
        $tds = WithholdingTaxRate::factory()->create(['rate' => 10, 'liability_account_id' => $tdsLiability->id]);

        $response = $this->actingAs($user)->post(route('purchases.vendor-payments.store'), [
            'vendor_id' => $bill->vendor_id,
            'payment_account_id' => $cash->id,
            'payment_date' => '2026-01-20',
            'amount' => 1000,
            'withholding_tax_rate_id' => $tds->id,
            'allocations' => [
                ['bill_id' => $bill->id, 'amount' => 1000],
            ],
        ]);

        $response->assertRedirect();

        // The bill is fully settled even though less cash actually left —
        // the vendor is credited for the full 1000, 100 of it just went to
        // the TDS liability instead of their pocket.
        $this->assertTrue($bill->fresh()->isFullyPaid());

        $payment = VendorPayment::first();
        $this->assertSame('100.0000', (string) $payment->withholding_tax_amount);
        $this->assertSame('900.0000', $payment->netCashPaid());

        $journal = $payment->journal;
        $this->assertTrue($journal->isBalanced());
        $this->assertSame('1000.0000', $journal->totalDebit());

        $cashLine = $journal->entries->firstWhere('account_id', $cash->id);
        $this->assertSame('900.0000', $cashLine->credit);

        $tdsLine = $journal->entries->firstWhere('account_id', $tdsLiability->id);
        $this->assertSame('100.0000', $tdsLine->credit);

        $payableLine = $journal->entries->firstWhere('account_id', $bill->payable_account_id);
        $this->assertSame('1000.0000', $payableLine->debit);
    }

    public function test_a_payment_with_no_withholding_rate_behaves_exactly_as_before(): void
    {
        $user = User::factory()->create();
        $bill = $this->postedBill(['total' => 1000]);
        $cash = Account::factory()->create(['type' => 'asset']);

        $this->actingAs($user)->post(route('purchases.vendor-payments.store'), [
            'vendor_id' => $bill->vendor_id,
            'payment_account_id' => $cash->id,
            'payment_date' => '2026-01-20',
            'amount' => 1000,
            'allocations' => [
                ['bill_id' => $bill->id, 'amount' => 1000],
            ],
        ]);

        $payment = VendorPayment::first();
        $this->assertSame('0.0000', (string) $payment->withholding_tax_amount);
        $this->assertNull($payment->withholding_tax_rate_id);
        $this->assertSame('1000.0000', $payment->netCashPaid());

        $cashLine = $payment->journal->entries->firstWhere('account_id', $cash->id);
        $this->assertSame('1000.0000', $cashLine->credit);
    }

    public function test_an_invalid_withholding_rate_id_is_rejected_by_validation(): void
    {
        $user = User::factory()->create();
        $bill = $this->postedBill(['total' => 1000]);
        $cash = Account::factory()->create(['type' => 'asset']);

        $response = $this->actingAs($user)->post(route('purchases.vendor-payments.store'), [
            'vendor_id' => $bill->vendor_id,
            'payment_account_id' => $cash->id,
            'payment_date' => '2026-01-20',
            'amount' => 1000,
            'withholding_tax_rate_id' => 999999,
            'allocations' => [
                ['bill_id' => $bill->id, 'amount' => 1000],
            ],
        ]);

        $response->assertSessionHasErrors('withholding_tax_rate_id');
        $this->assertDatabaseCount('vendor_payments', 0);
    }
}
