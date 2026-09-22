<?php

namespace Tests\Feature;

use App\Actions\Accounting\PostJournal;
use App\Actions\Purchases\PostBill;
use App\Actions\Sales\PostInvoice;
use App\Models\Accounting\Account;
use App\Models\Contacts\Customer;
use App\Models\Contacts\Vendor;
use App\Models\Inventory\Product;
use App\Models\Purchases\Bill;
use App\Models\Sales\Invoice;
use App\Models\Sales\Payment;
use App\Models\Sales\RecurringInvoice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_view_notifications(): void
    {
        $this->get(route('notifications.index'))->assertRedirect(route('login'));
    }

    public function test_an_overdue_unpaid_invoice_is_reported(): void
    {
        $user = User::factory()->create();
        $customer = Customer::factory()->create();
        $receivable = Account::factory()->create(['type' => 'asset']);
        $income = Account::factory()->create(['type' => 'income']);

        $invoice = Invoice::factory()->create([
            'customer_id' => $customer->id,
            'receivable_account_id' => $receivable->id,
            'invoice_date' => now()->subDays(10)->toDateString(),
            'due_date' => now()->subDay()->toDateString(),
            'status' => 'draft',
            'total' => 0,
        ]);
        $invoice->items()->create([
            'account_id' => $income->id,
            'description' => 'Line',
            'quantity' => 1,
            'unit_price' => 500,
            'discount' => 0,
            'line_total' => 500,
        ]);
        (new PostInvoice(new PostJournal))->handle($invoice->fresh());

        $response = $this->actingAs($user)->getJson(route('notifications.index'));

        $response->assertOk();
        $group = collect($response->json('groups'))->firstWhere('label', 'Overdue Invoices');
        $this->assertNotNull($group);
        $this->assertStringContainsString($invoice->invoice_number, $group['items'][0]['message']);
    }

    public function test_a_fully_paid_overdue_invoice_is_not_reported(): void
    {
        $user = User::factory()->create();
        $customer = Customer::factory()->create();
        $receivable = Account::factory()->create(['type' => 'asset']);
        $income = Account::factory()->create(['type' => 'income']);

        $invoice = Invoice::factory()->create([
            'customer_id' => $customer->id,
            'receivable_account_id' => $receivable->id,
            'invoice_date' => now()->subDays(10)->toDateString(),
            'due_date' => now()->subDay()->toDateString(),
            'status' => 'draft',
            'total' => 0,
        ]);
        $invoice->items()->create([
            'account_id' => $income->id,
            'description' => 'Line',
            'quantity' => 1,
            'unit_price' => 500,
            'discount' => 0,
            'line_total' => 500,
        ]);
        $invoice = (new PostInvoice(new PostJournal))->handle($invoice->fresh());
        $deposit = Account::factory()->create(['type' => 'asset']);
        $payment = Payment::create([
            'payment_number' => 'PMT-TEST-1',
            'customer_id' => $customer->id,
            'deposit_account_id' => $deposit->id,
            'payment_date' => now()->toDateString(),
            'amount' => 500,
        ]);
        $payment->allocations()->create(['invoice_id' => $invoice->id, 'amount' => 500]);

        $response = $this->actingAs($user)->getJson(route('notifications.index'));

        $this->assertNull(collect($response->json('groups'))->firstWhere('label', 'Overdue Invoices'));
    }

    public function test_an_overdue_unpaid_bill_is_reported(): void
    {
        $user = User::factory()->create();
        $vendor = Vendor::factory()->create();
        $payable = Account::factory()->create(['type' => 'liability']);
        $expense = Account::factory()->create(['type' => 'expense']);

        $bill = Bill::factory()->create([
            'vendor_id' => $vendor->id,
            'payable_account_id' => $payable->id,
            'bill_date' => now()->subDays(10)->toDateString(),
            'due_date' => now()->subDay()->toDateString(),
            'status' => 'draft',
            'total' => 0,
        ]);
        $bill->items()->create([
            'account_id' => $expense->id,
            'description' => 'Line',
            'quantity' => 1,
            'unit_price' => 300,
            'discount' => 0,
            'line_total' => 300,
        ]);
        (new PostBill(new PostJournal))->handle($bill->fresh());

        $response = $this->actingAs($user)->getJson(route('notifications.index'));

        $group = collect($response->json('groups'))->firstWhere('label', 'Overdue Bills');
        $this->assertNotNull($group);
        $this->assertStringContainsString($bill->bill_number, $group['items'][0]['message']);
    }

    public function test_a_low_stock_product_is_reported(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->inventoryTracked()->create(['low_stock_threshold' => 5]);
        $product->stockMovements()->create(['date' => now()->toDateString(), 'quantity' => 3, 'reason' => 'opening']);

        $response = $this->actingAs($user)->getJson(route('notifications.index'));

        $group = collect($response->json('groups'))->firstWhere('label', 'Low Stock');
        $this->assertNotNull($group);
        $this->assertStringContainsString($product->name, $group['items'][0]['message']);
    }

    public function test_a_draft_invoice_generated_from_a_recurring_template_is_reported_for_review(): void
    {
        $user = User::factory()->create();
        $customer = Customer::factory()->create();
        $receivable = Account::factory()->create(['type' => 'asset']);
        $template = RecurringInvoice::create([
            'name' => 'Monthly Hosting',
            'customer_id' => $customer->id,
            'receivable_account_id' => $receivable->id,
            'is_active' => true,
        ]);
        $invoice = Invoice::factory()->create([
            'customer_id' => $customer->id,
            'recurring_invoice_id' => $template->id,
            'receivable_account_id' => $receivable->id,
            'status' => 'draft',
        ]);

        $response = $this->actingAs($user)->getJson(route('notifications.index'));

        $group = collect($response->json('groups'))->firstWhere('label', 'Auto-Generated Invoices Awaiting Review');
        $this->assertNotNull($group);
        $this->assertStringContainsString($invoice->invoice_number, $group['items'][0]['message']);
    }

    public function test_a_manually_created_draft_invoice_is_not_reported_as_auto_generated(): void
    {
        $user = User::factory()->create();
        Invoice::factory()->create(['status' => 'draft']);

        $response = $this->actingAs($user)->getJson(route('notifications.index'));

        $this->assertNull(collect($response->json('groups'))->firstWhere('label', 'Auto-Generated Invoices Awaiting Review'));
    }

    public function test_a_user_without_bills_permission_does_not_see_overdue_bills(): void
    {
        $user = User::factory()->create();
        $user->syncRoles(['Sales']);
        $vendor = Vendor::factory()->create();
        $payable = Account::factory()->create(['type' => 'liability']);
        $expense = Account::factory()->create(['type' => 'expense']);

        $bill = Bill::factory()->create([
            'vendor_id' => $vendor->id,
            'payable_account_id' => $payable->id,
            'bill_date' => now()->subDays(10)->toDateString(),
            'due_date' => now()->subDay()->toDateString(),
            'status' => 'draft',
            'total' => 0,
        ]);
        $bill->items()->create([
            'account_id' => $expense->id,
            'description' => 'Line',
            'quantity' => 1,
            'unit_price' => 300,
            'discount' => 0,
            'line_total' => 300,
        ]);
        (new PostBill(new PostJournal))->handle($bill->fresh());

        $response = $this->actingAs($user)->getJson(route('notifications.index'));

        $this->assertNull(collect($response->json('groups'))->firstWhere('label', 'Overdue Bills'));
    }
}
