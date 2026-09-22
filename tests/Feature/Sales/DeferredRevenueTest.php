<?php

namespace Tests\Feature\Sales;

use App\Actions\Sales\RecognizeRevenue;
use App\Models\Accounting\Account;
use App\Models\Contacts\Customer;
use App\Models\Sales\Invoice;
use App\Models\Sales\RevenueRecognitionSchedule;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class DeferredRevenueTest extends TestCase
{
    use RefreshDatabase;

    private function payload(array $overrides = []): array
    {
        $customer = Customer::factory()->create();
        $receivable = Account::factory()->create(['type' => 'asset']);
        $income = Account::factory()->create(['type' => 'income']);
        $deferred = Account::factory()->create(['type' => 'liability']);

        return array_merge([
            'customer_id' => $customer->id,
            'receivable_account_id' => $receivable->id,
            'invoice_date' => '2026-01-15',
            'items' => [
                [
                    'account_id' => $income->id,
                    'description' => '12-month hosting plan',
                    'quantity' => 1,
                    'unit_price' => 1200,
                    'discount' => 0,
                    'is_deferred' => true,
                    'deferred_months' => 12,
                    'deferred_revenue_account_id' => $deferred->id,
                ],
            ],
        ], $overrides);
    }

    public function test_a_deferred_line_requires_months_and_a_deferred_account(): void
    {
        $user = User::factory()->create();
        $customer = Customer::factory()->create();
        $receivable = Account::factory()->create(['type' => 'asset']);
        $income = Account::factory()->create(['type' => 'income']);

        $response = $this->actingAs($user)->post(route('sales.invoices.store'), [
            'customer_id' => $customer->id,
            'receivable_account_id' => $receivable->id,
            'invoice_date' => '2026-01-15',
            'items' => [
                ['account_id' => $income->id, 'description' => 'X', 'quantity' => 1, 'unit_price' => 1200, 'discount' => 0, 'is_deferred' => true],
            ],
        ]);

        $response->assertSessionHasErrors(['items.0.deferred_months', 'items.0.deferred_revenue_account_id']);
    }

    public function test_the_deferred_revenue_account_must_be_a_liability_account(): void
    {
        $user = User::factory()->create();
        $customer = Customer::factory()->create();
        $receivable = Account::factory()->create(['type' => 'asset']);
        $income = Account::factory()->create(['type' => 'income']);
        $notLiability = Account::factory()->create(['type' => 'asset']);

        $response = $this->actingAs($user)->post(route('sales.invoices.store'), [
            'customer_id' => $customer->id,
            'receivable_account_id' => $receivable->id,
            'invoice_date' => '2026-01-15',
            'items' => [
                ['account_id' => $income->id, 'description' => 'X', 'quantity' => 1, 'unit_price' => 1200, 'discount' => 0, 'is_deferred' => true, 'deferred_months' => 12, 'deferred_revenue_account_id' => $notLiability->id],
            ],
        ]);

        $response->assertSessionHasErrors('items.0.deferred_revenue_account_id');
    }

    public function test_posting_a_deferred_invoice_credits_the_deferred_account_not_income_and_creates_a_schedule(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->post(route('sales.invoices.store'), $this->payload());
        $invoice = Invoice::first();

        $this->actingAs($user)->post(route('sales.invoices.post', $invoice));

        $invoice->refresh();
        $this->assertSame('posted', $invoice->status);
        $this->assertTrue($invoice->journal->isBalanced());

        $item = $invoice->items->first();
        $deferredLine = $invoice->journal->entries->firstWhere('account_id', $item->deferred_revenue_account_id);
        $this->assertSame('1200.0000', $deferredLine->credit);

        $incomeLine = $invoice->journal->entries->firstWhere('account_id', $item->account_id);
        $this->assertNull($incomeLine);

        $schedule = RevenueRecognitionSchedule::first();
        $this->assertNotNull($schedule);
        $this->assertSame('1200.0000', (string) $schedule->total_amount);
        $this->assertSame(12, $schedule->months_total);
        $this->assertSame(0, $schedule->months_recognized);
        $this->assertSame('active', $schedule->status);
        $this->assertSame('2026-01-01', $schedule->next_period_date->toDateString());
    }

    public function test_recognizing_a_period_debits_deferred_and_credits_income(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->post(route('sales.invoices.store'), $this->payload());
        $invoice = Invoice::first();
        $this->actingAs($user)->post(route('sales.invoices.post', $invoice));
        $schedule = RevenueRecognitionSchedule::first();

        $this->actingAs($user)->post(route('sales.revenue-recognition.recognize', $schedule))->assertRedirect();

        $schedule->refresh();
        $this->assertSame(1, $schedule->months_recognized);
        $this->assertSame('100.0000', $schedule->recognizedTotal());
        $this->assertSame('2026-02-01', $schedule->next_period_date->toDateString());

        $entry = $schedule->entries()->first();
        $this->assertTrue($entry->journal->isBalanced());

        $deferredLine = $entry->journal->entries->firstWhere('account_id', $schedule->deferred_revenue_account_id);
        $this->assertSame('100.0000', $deferredLine->debit);

        $incomeLine = $entry->journal->entries->firstWhere('account_id', $schedule->income_account_id);
        $this->assertSame('100.0000', $incomeLine->credit);
    }

    public function test_the_final_period_absorbs_the_rounding_remainder(): void
    {
        $user = User::factory()->create();
        // 1000 / 3 = 333.3333/mo, 3 x 333.3333 = 999.9999 — short by 0.0001
        // without the final-period cap.
        $this->actingAs($user)->post(route('sales.invoices.store'), $this->payload([
            'items' => [[
                'account_id' => Account::factory()->create(['type' => 'income'])->id,
                'description' => 'Quarterly service',
                'quantity' => 1,
                'unit_price' => 1000,
                'discount' => 0,
                'is_deferred' => true,
                'deferred_months' => 3,
                'deferred_revenue_account_id' => Account::factory()->create(['type' => 'liability'])->id,
            ]],
        ]));
        $invoice = Invoice::first();
        $this->actingAs($user)->post(route('sales.invoices.post', $invoice));
        $schedule = RevenueRecognitionSchedule::first();

        app(RecognizeRevenue::class)->handle($schedule->fresh());
        app(RecognizeRevenue::class)->handle($schedule->fresh());
        app(RecognizeRevenue::class)->handle($schedule->fresh());

        $schedule->refresh();
        $this->assertSame('1000.0000', $schedule->recognizedTotal());
        $this->assertSame('completed', $schedule->status);
        $this->assertNull($schedule->next_period_date);

        $this->expectException(RuntimeException::class);
        app(RecognizeRevenue::class)->handle($schedule);
    }

    public function test_the_scheduled_command_recognizes_every_due_schedule(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->post(route('sales.invoices.store'), $this->payload());
        $invoice = Invoice::first();
        $this->actingAs($user)->post(route('sales.invoices.post', $invoice));
        $schedule = RevenueRecognitionSchedule::first();
        // Backdate so it's due "today" regardless of when this test runs.
        $schedule->update(['next_period_date' => now()->toDateString()]);

        $this->artisan('revenue:recognize')->assertSuccessful();

        $this->assertSame(1, $schedule->fresh()->months_recognized);

        // Running it again immediately does not recognize a second period
        // — the due date has already moved a month into the future.
        $this->artisan('revenue:recognize')->assertSuccessful();
        $this->assertSame(1, $schedule->fresh()->months_recognized);
    }

    public function test_a_non_deferred_invoice_creates_no_schedule(): void
    {
        $user = User::factory()->create();
        $customer = Customer::factory()->create();
        $receivable = Account::factory()->create(['type' => 'asset']);
        $income = Account::factory()->create(['type' => 'income']);

        $this->actingAs($user)->post(route('sales.invoices.store'), [
            'customer_id' => $customer->id,
            'receivable_account_id' => $receivable->id,
            'invoice_date' => '2026-01-15',
            'items' => [
                ['account_id' => $income->id, 'description' => 'Normal sale', 'quantity' => 1, 'unit_price' => 500, 'discount' => 0],
            ],
        ]);
        $invoice = Invoice::first();
        $this->actingAs($user)->post(route('sales.invoices.post', $invoice));

        $this->assertDatabaseCount('revenue_recognition_schedules', 0);

        $incomeLine = $invoice->fresh()->journal->entries->firstWhere('account_id', $income->id);
        $this->assertSame('500.0000', $incomeLine->credit);
    }
}
