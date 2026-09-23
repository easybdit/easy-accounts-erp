<?php

namespace Tests\Feature\Banking;

use App\Actions\Banking\MakeBankDeposit;
use App\Actions\Sales\PostInvoice;
use App\Actions\Sales\ReceivePayment;
use App\Actions\Sales\SaveInvoiceDraft;
use App\Models\Accounting\Account;
use App\Models\Contacts\Customer;
use App\Models\Sales\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class BankDepositTest extends TestCase
{
    use RefreshDatabase;

    private function receivePayment(Account $undepositedFunds, float $amount, string $date = '2026-03-01'): Payment
    {
        $customer = Customer::factory()->create();
        $receivable = Account::factory()->create(['type' => 'asset']);
        $income = Account::factory()->create(['type' => 'income']);

        $invoice = app(SaveInvoiceDraft::class)->handle([
            'customer_id' => $customer->id,
            'receivable_account_id' => $receivable->id,
            'invoice_date' => $date,
            'due_date' => null,
            'created_by' => null,
            'items' => [
                ['account_id' => $income->id, 'description' => 'Service', 'quantity' => 1, 'unit_price' => $amount, 'discount' => 0],
            ],
        ]);
        app(PostInvoice::class)->handle($invoice);

        return app(ReceivePayment::class)->handle([
            'customer_id' => $customer->id,
            'deposit_account_id' => $undepositedFunds->id,
            'payment_date' => $date,
            'reference' => null,
            'method' => 'cash',
            'amount' => $amount,
            'notes' => null,
            'created_by' => null,
            'allocations' => [
                ['invoice_id' => $invoice->id, 'amount' => $amount],
            ],
        ]);
    }

    public function test_guest_cannot_view_deposits(): void
    {
        $this->get(route('banking.deposits.index'))->assertRedirect(route('login'));
    }

    public function test_batching_two_payments_posts_one_journal_debiting_bank_and_crediting_undeposited_funds(): void
    {
        $undepositedFunds = Account::factory()->create(['type' => 'asset', 'is_undeposited_funds' => true]);
        $bank = Account::factory()->create(['type' => 'asset', 'is_bank_account' => true]);
        $paymentA = $this->receivePayment($undepositedFunds, 100);
        $paymentB = $this->receivePayment($undepositedFunds, 250);

        $deposit = app(MakeBankDeposit::class)->handle([
            'bank_account_id' => $bank->id,
            'deposit_date' => '2026-03-05',
            'reference' => null,
            'notes' => null,
            'created_by' => null,
            'payment_ids' => [$paymentA->id, $paymentB->id],
        ]);

        $this->assertSame('350.0000', (string) $deposit->amount);
        $this->assertTrue($deposit->journal->isBalanced());

        $bankLine = $deposit->journal->entries->firstWhere('account_id', $bank->id);
        $this->assertSame('350.0000', $bankLine->debit);

        $undepositedLine = $deposit->journal->entries->firstWhere('account_id', $undepositedFunds->id);
        $this->assertSame('350.0000', $undepositedLine->credit);

        $this->assertSame($deposit->id, $paymentA->fresh()->bank_deposit_id);
        $this->assertSame($deposit->id, $paymentB->fresh()->bank_deposit_id);
    }

    public function test_a_payment_already_deposited_cannot_be_deposited_again(): void
    {
        $undepositedFunds = Account::factory()->create(['type' => 'asset', 'is_undeposited_funds' => true]);
        $bank = Account::factory()->create(['type' => 'asset', 'is_bank_account' => true]);
        $payment = $this->receivePayment($undepositedFunds, 100);

        app(MakeBankDeposit::class)->handle([
            'bank_account_id' => $bank->id, 'deposit_date' => '2026-03-05',
            'reference' => null, 'notes' => null, 'created_by' => null,
            'payment_ids' => [$payment->id],
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('already been deposited');

        app(MakeBankDeposit::class)->handle([
            'bank_account_id' => $bank->id, 'deposit_date' => '2026-03-06',
            'reference' => null, 'notes' => null, 'created_by' => null,
            'payment_ids' => [$payment->id],
        ]);
    }

    public function test_a_payment_not_received_into_undeposited_funds_is_rejected(): void
    {
        $undepositedFunds = Account::factory()->create(['type' => 'asset', 'is_undeposited_funds' => true]);
        $bank = Account::factory()->create(['type' => 'asset', 'is_bank_account' => true]);
        // Received straight into the bank account, bypassing Undeposited Funds.
        $payment = $this->receivePayment($bank, 100);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('not received into Undeposited Funds');

        app(MakeBankDeposit::class)->handle([
            'bank_account_id' => $bank->id, 'deposit_date' => '2026-03-05',
            'reference' => null, 'notes' => null, 'created_by' => null,
            'payment_ids' => [$payment->id],
        ]);
    }

    public function test_the_full_http_flow_records_a_deposit_and_removes_payments_from_the_undeposited_list(): void
    {
        $user = User::factory()->create();
        $undepositedFunds = Account::factory()->create(['type' => 'asset', 'is_undeposited_funds' => true]);
        $bank = Account::factory()->create(['type' => 'asset', 'is_bank_account' => true]);
        $payment = $this->receivePayment($undepositedFunds, 500);

        $indexBefore = $this->actingAs($user)->get(route('banking.deposits.index'));
        $indexBefore->assertInertia(fn ($page) => $page->has('undepositedPayments', 1));

        $response = $this->actingAs($user)->post(route('banking.deposits.store'), [
            'bank_account_id' => $bank->id,
            'deposit_date' => '2026-03-10',
            'payment_ids' => [$payment->id],
        ]);

        $response->assertRedirect();
        $this->assertDatabaseCount('bank_deposits', 1);

        $indexAfter = $this->actingAs($user)->get(route('banking.deposits.index'));
        $indexAfter->assertInertia(fn ($page) => $page->has('undepositedPayments', 0));
    }

    public function test_a_non_bank_account_is_rejected_as_the_deposit_target(): void
    {
        $user = User::factory()->create();
        $undepositedFunds = Account::factory()->create(['type' => 'asset', 'is_undeposited_funds' => true]);
        $notABank = Account::factory()->create(['type' => 'asset', 'is_bank_account' => false]);
        $payment = $this->receivePayment($undepositedFunds, 100);

        $response = $this->actingAs($user)->post(route('banking.deposits.store'), [
            'bank_account_id' => $notABank->id,
            'deposit_date' => '2026-03-10',
            'payment_ids' => [$payment->id],
        ]);

        $response->assertSessionHasErrors('bank_account_id');
        $this->assertDatabaseCount('bank_deposits', 0);
    }
}
