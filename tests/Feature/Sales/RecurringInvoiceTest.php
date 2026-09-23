<?php

namespace Tests\Feature\Sales;

use App\Models\Accounting\Account;
use App\Models\Contacts\Customer;
use App\Models\Sales\Invoice;
use App\Models\Sales\RecurringInvoice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class RecurringInvoiceTest extends TestCase
{
    use RefreshDatabase;

    private function payload(array $overrides = []): array
    {
        $customer = Customer::factory()->create();
        $receivable = Account::factory()->create(['type' => 'asset']);
        $income = Account::factory()->create(['type' => 'income']);

        return array_merge([
            'name' => 'Monthly Retainer',
            'customer_id' => $customer->id,
            'receivable_account_id' => $receivable->id,
            'items' => [
                ['account_id' => $income->id, 'description' => 'Retainer fee', 'quantity' => 1, 'unit_price' => 500, 'discount' => 0],
            ],
        ], $overrides);
    }

    public function test_guest_cannot_view_recurring_invoices(): void
    {
        $this->get(route('sales.recurring-invoices.index'))->assertRedirect(route('login'));
    }

    public function test_a_template_can_be_created(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('sales.recurring-invoices.store'), $this->payload())
            ->assertRedirect(route('sales.recurring-invoices.index'));

        $this->assertDatabaseHas('recurring_invoices', ['name' => 'Monthly Retainer']);
        $this->assertDatabaseCount('recurring_invoice_items', 1);
    }

    public function test_a_template_can_be_updated_and_its_items_are_replaced(): void
    {
        $user = User::factory()->create();
        $income = Account::factory()->create(['type' => 'income']);

        $this->actingAs($user)->post(route('sales.recurring-invoices.store'), $this->payload());
        $template = RecurringInvoice::first();

        $this->actingAs($user)->put(route('sales.recurring-invoices.update', $template), $this->payload([
            'name' => 'Monthly Retainer (Updated)',
            'customer_id' => $template->customer_id,
            'receivable_account_id' => $template->receivable_account_id,
            'items' => [
                ['account_id' => $income->id, 'description' => 'Updated line', 'quantity' => 2, 'unit_price' => 250, 'discount' => 0],
            ],
        ]))->assertRedirect(route('sales.recurring-invoices.index'));

        $this->assertDatabaseHas('recurring_invoices', ['id' => $template->id, 'name' => 'Monthly Retainer (Updated)']);
        $this->assertDatabaseCount('recurring_invoice_items', 1);
        $this->assertDatabaseHas('recurring_invoice_items', ['description' => 'Updated line', 'quantity' => 2]);
    }

    public function test_generate_now_creates_a_draft_invoice_with_the_templates_items(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('sales.recurring-invoices.store'), $this->payload([
            'items' => [
                ['account_id' => Account::factory()->create(['type' => 'income'])->id, 'description' => 'Line A', 'quantity' => 2, 'unit_price' => 100, 'discount' => 0],
                ['account_id' => Account::factory()->create(['type' => 'income'])->id, 'description' => 'Line B', 'quantity' => 1, 'unit_price' => 50, 'discount' => 10],
            ],
        ]));
        $template = RecurringInvoice::first();

        $response = $this->actingAs($user)->post(route('sales.recurring-invoices.generate', $template));

        $this->assertDatabaseCount('invoices', 1);
        $invoice = Invoice::first();

        $response->assertRedirect(route('sales.invoices.edit', $invoice));
        $this->assertSame('draft', $invoice->status);
        $this->assertSame($template->customer_id, $invoice->customer_id);
        $this->assertSame(now()->toDateString(), $invoice->invoice_date->toDateString());
        // subtotal: (2*100) + (1*50) = 250; discount: 10; total: 240.
        $this->assertSame('240.0000', (string) $invoice->total);
        $this->assertDatabaseCount('invoice_items', 2);
    }

    public function test_generating_twice_creates_two_separate_draft_invoices(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->post(route('sales.recurring-invoices.store'), $this->payload());
        $template = RecurringInvoice::first();

        $this->actingAs($user)->post(route('sales.recurring-invoices.generate', $template));
        $this->actingAs($user)->post(route('sales.recurring-invoices.generate', $template));

        $this->assertDatabaseCount('invoices', 2);
        $this->assertSame(2, Invoice::distinct('invoice_number')->count('invoice_number'));
    }

    public function test_deleting_a_template_does_not_affect_invoices_already_generated_from_it(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->post(route('sales.recurring-invoices.store'), $this->payload());
        $template = RecurringInvoice::first();
        $this->actingAs($user)->post(route('sales.recurring-invoices.generate', $template));

        $this->actingAs($user)->delete(route('sales.recurring-invoices.destroy', $template))
            ->assertRedirect(route('sales.recurring-invoices.index'));

        $this->assertDatabaseMissing('recurring_invoices', ['id' => $template->id]);
        $this->assertDatabaseCount('invoices', 1);
    }

    public function test_viewer_role_cannot_generate_an_invoice_from_a_template(): void
    {
        $user = User::factory()->create();
        $user->syncRoles(['Viewer']);
        $admin = User::factory()->create();
        $this->actingAs($admin)->post(route('sales.recurring-invoices.store'), $this->payload());
        $template = RecurringInvoice::first();

        $this->actingAs($user)->post(route('sales.recurring-invoices.generate', $template))->assertForbidden();

        $this->assertDatabaseCount('invoices', 0);
    }

    public function test_a_template_with_no_next_generation_date_stays_manual_only(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->post(route('sales.recurring-invoices.store'), $this->payload());

        $this->artisan('invoices:generate-recurring')->assertSuccessful();

        $this->assertDatabaseCount('invoices', 0);
    }

    public function test_the_scheduled_command_generates_a_draft_and_advances_the_next_generation_date_by_one_month(): void
    {
        $user = User::factory()->create();
        $dueDate = now()->toDateString();
        $this->actingAs($user)->post(route('sales.recurring-invoices.store'), $this->payload([
            'next_generation_date' => $dueDate,
        ]));
        $template = RecurringInvoice::first();

        $this->artisan('invoices:generate-recurring')->assertSuccessful();

        $this->assertDatabaseCount('invoices', 1);
        $invoice = Invoice::first();
        $this->assertSame('draft', $invoice->status);
        $this->assertSame($template->id, $invoice->recurring_invoice_id);

        $this->assertSame(now()->addMonthNoOverflow()->toDateString(), $template->fresh()->next_generation_date->toDateString());

        // Running it again the same day does not generate a second invoice
        // — the due date has already moved a month into the future.
        $this->artisan('invoices:generate-recurring')->assertSuccessful();
        $this->assertDatabaseCount('invoices', 1);
    }

    public function test_an_inactive_template_is_not_auto_generated(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->post(route('sales.recurring-invoices.store'), $this->payload([
            'next_generation_date' => now()->toDateString(),
            'is_active' => false,
        ]));

        $this->artisan('invoices:generate-recurring')->assertSuccessful();

        $this->assertDatabaseCount('invoices', 0);
    }

    public function test_a_future_next_generation_date_is_not_generated_early(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->post(route('sales.recurring-invoices.store'), $this->payload([
            'next_generation_date' => now()->addMonth()->toDateString(),
        ]));

        $this->artisan('invoices:generate-recurring')->assertSuccessful();

        $this->assertDatabaseCount('invoices', 0);
    }

    public function test_a_template_defaults_to_monthly_when_no_frequency_is_given(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->post(route('sales.recurring-invoices.store'), $this->payload());

        $this->assertSame('monthly', RecurringInvoice::first()->frequency);
    }

    #[DataProvider('frequencyProvider')]
    public function test_the_scheduled_command_advances_the_next_generation_date_by_the_templates_frequency(string $frequency): void
    {
        $user = User::factory()->create();
        $dueDate = now()->toDateString();
        $this->actingAs($user)->post(route('sales.recurring-invoices.store'), $this->payload([
            'next_generation_date' => $dueDate,
            'frequency' => $frequency,
        ]));
        $template = RecurringInvoice::first();

        $this->artisan('invoices:generate-recurring')->assertSuccessful();

        $expected = match ($frequency) {
            'weekly' => now()->addWeek()->toDateString(),
            'monthly' => now()->addMonthNoOverflow()->toDateString(),
            'quarterly' => now()->addMonthsNoOverflow(3)->toDateString(),
            'yearly' => now()->addYearNoOverflow()->toDateString(),
        };
        $this->assertSame($expected, $template->fresh()->next_generation_date->toDateString());
    }

    public static function frequencyProvider(): array
    {
        return [
            'weekly' => ['weekly'],
            'monthly' => ['monthly'],
            'quarterly' => ['quarterly'],
            'yearly' => ['yearly'],
        ];
    }
}
