<?php

namespace Tests\Feature\Purchases;

use App\Models\Accounting\Account;
use App\Models\Contacts\Vendor;
use App\Models\Purchases\Bill;
use App\Models\Purchases\RecurringBill;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RecurringBillTest extends TestCase
{
    use RefreshDatabase;

    private function payload(array $overrides = []): array
    {
        $vendor = Vendor::factory()->create();
        $payable = Account::factory()->create(['type' => 'liability']);
        $expense = Account::factory()->create(['type' => 'expense']);

        return array_merge([
            'name' => 'Monthly Datacenter Bandwidth',
            'vendor_id' => $vendor->id,
            'payable_account_id' => $payable->id,
            'items' => [
                ['account_id' => $expense->id, 'description' => 'Bandwidth fee', 'quantity' => 1, 'unit_price' => 500, 'discount' => 0],
            ],
        ], $overrides);
    }

    public function test_guest_cannot_view_recurring_bills(): void
    {
        $this->get(route('purchases.recurring-bills.index'))->assertRedirect(route('login'));
    }

    public function test_a_template_can_be_created(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('purchases.recurring-bills.store'), $this->payload())
            ->assertRedirect(route('purchases.recurring-bills.index'));

        $this->assertDatabaseHas('recurring_bills', ['name' => 'Monthly Datacenter Bandwidth']);
        $this->assertDatabaseCount('recurring_bill_items', 1);
    }

    public function test_a_template_can_be_updated_and_its_items_are_replaced(): void
    {
        $user = User::factory()->create();
        $expense = Account::factory()->create(['type' => 'expense']);

        $this->actingAs($user)->post(route('purchases.recurring-bills.store'), $this->payload());
        $template = RecurringBill::first();

        $this->actingAs($user)->put(route('purchases.recurring-bills.update', $template), $this->payload([
            'name' => 'Monthly Datacenter Bandwidth (Updated)',
            'vendor_id' => $template->vendor_id,
            'payable_account_id' => $template->payable_account_id,
            'items' => [
                ['account_id' => $expense->id, 'description' => 'Updated line', 'quantity' => 2, 'unit_price' => 250, 'discount' => 0],
            ],
        ]))->assertRedirect(route('purchases.recurring-bills.index'));

        $this->assertDatabaseHas('recurring_bills', ['id' => $template->id, 'name' => 'Monthly Datacenter Bandwidth (Updated)']);
        $this->assertDatabaseCount('recurring_bill_items', 1);
        $this->assertDatabaseHas('recurring_bill_items', ['description' => 'Updated line', 'quantity' => 2]);
    }

    public function test_generate_now_creates_a_draft_bill_with_the_templates_items(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('purchases.recurring-bills.store'), $this->payload([
            'items' => [
                ['account_id' => Account::factory()->create(['type' => 'expense'])->id, 'description' => 'Line A', 'quantity' => 2, 'unit_price' => 100, 'discount' => 0],
                ['account_id' => Account::factory()->create(['type' => 'expense'])->id, 'description' => 'Line B', 'quantity' => 1, 'unit_price' => 50, 'discount' => 10],
            ],
        ]));
        $template = RecurringBill::first();

        $response = $this->actingAs($user)->post(route('purchases.recurring-bills.generate', $template));

        $this->assertDatabaseCount('bills', 1);
        $bill = Bill::first();

        $response->assertRedirect(route('purchases.bills.edit', $bill));
        $this->assertSame('draft', $bill->status);
        $this->assertSame($template->vendor_id, $bill->vendor_id);
        $this->assertSame($template->id, $bill->recurring_bill_id);
        $this->assertSame(now()->toDateString(), $bill->bill_date->toDateString());
        // subtotal: (2*100) + (1*50) = 250; discount: 10; total: 240.
        $this->assertSame('240.0000', (string) $bill->total);
        $this->assertDatabaseCount('bill_items', 2);
    }

    public function test_generating_twice_creates_two_separate_draft_bills(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->post(route('purchases.recurring-bills.store'), $this->payload());
        $template = RecurringBill::first();

        $this->actingAs($user)->post(route('purchases.recurring-bills.generate', $template));
        $this->actingAs($user)->post(route('purchases.recurring-bills.generate', $template));

        $this->assertDatabaseCount('bills', 2);
        $this->assertSame(2, Bill::distinct('bill_number')->count('bill_number'));
    }

    public function test_deleting_a_template_does_not_affect_bills_already_generated_from_it(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->post(route('purchases.recurring-bills.store'), $this->payload());
        $template = RecurringBill::first();
        $this->actingAs($user)->post(route('purchases.recurring-bills.generate', $template));

        $this->actingAs($user)->delete(route('purchases.recurring-bills.destroy', $template))
            ->assertRedirect(route('purchases.recurring-bills.index'));

        $this->assertDatabaseMissing('recurring_bills', ['id' => $template->id]);
        $this->assertDatabaseCount('bills', 1);
    }

    public function test_viewer_role_cannot_generate_a_bill_from_a_template(): void
    {
        $user = User::factory()->create();
        $user->syncRoles(['Viewer']);
        $admin = User::factory()->create();
        $this->actingAs($admin)->post(route('purchases.recurring-bills.store'), $this->payload());
        $template = RecurringBill::first();

        $this->actingAs($user)->post(route('purchases.recurring-bills.generate', $template))->assertForbidden();

        $this->assertDatabaseCount('bills', 0);
    }

    public function test_a_template_with_no_next_generation_date_stays_manual_only(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->post(route('purchases.recurring-bills.store'), $this->payload());

        $this->artisan('bills:generate-recurring')->assertSuccessful();

        $this->assertDatabaseCount('bills', 0);
    }

    public function test_the_scheduled_command_generates_a_draft_and_advances_the_next_generation_date_by_one_month(): void
    {
        $user = User::factory()->create();
        $dueDate = now()->toDateString();
        $this->actingAs($user)->post(route('purchases.recurring-bills.store'), $this->payload([
            'next_generation_date' => $dueDate,
        ]));
        $template = RecurringBill::first();

        $this->artisan('bills:generate-recurring')->assertSuccessful();

        $this->assertDatabaseCount('bills', 1);
        $bill = Bill::first();
        $this->assertSame('draft', $bill->status);
        $this->assertSame($template->id, $bill->recurring_bill_id);

        $this->assertSame(now()->addMonthNoOverflow()->toDateString(), $template->fresh()->next_generation_date->toDateString());

        // Running it again the same day does not generate a second bill —
        // the due date has already moved a month into the future.
        $this->artisan('bills:generate-recurring')->assertSuccessful();
        $this->assertDatabaseCount('bills', 1);
    }

    public function test_an_inactive_template_is_not_auto_generated(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->post(route('purchases.recurring-bills.store'), $this->payload([
            'next_generation_date' => now()->toDateString(),
            'is_active' => false,
        ]));

        $this->artisan('bills:generate-recurring')->assertSuccessful();

        $this->assertDatabaseCount('bills', 0);
    }

    public function test_a_future_next_generation_date_is_not_generated_early(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->post(route('purchases.recurring-bills.store'), $this->payload([
            'next_generation_date' => now()->addMonth()->toDateString(),
        ]));

        $this->artisan('bills:generate-recurring')->assertSuccessful();

        $this->assertDatabaseCount('bills', 0);
    }

    public function test_a_template_defaults_to_monthly_when_no_frequency_is_given(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->post(route('purchases.recurring-bills.store'), $this->payload());

        $this->assertSame('monthly', RecurringBill::first()->frequency);
    }

    public function test_a_yearly_template_advances_by_one_year_not_one_month(): void
    {
        $user = User::factory()->create();
        $dueDate = now()->toDateString();
        $this->actingAs($user)->post(route('purchases.recurring-bills.store'), $this->payload([
            'next_generation_date' => $dueDate,
            'frequency' => 'yearly',
        ]));
        $template = RecurringBill::first();

        $this->artisan('bills:generate-recurring')->assertSuccessful();

        $this->assertSame(now()->addYearNoOverflow()->toDateString(), $template->fresh()->next_generation_date->toDateString());
    }
}
