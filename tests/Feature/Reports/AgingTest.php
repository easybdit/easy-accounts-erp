<?php

namespace Tests\Feature\Reports;

use App\Actions\Accounting\PostJournal;
use App\Actions\Purchases\MakePayment;
use App\Actions\Purchases\PostBill;
use App\Actions\Sales\PostInvoice;
use App\Models\Accounting\Account;
use App\Models\Contacts\Customer;
use App\Models\Contacts\Vendor;
use App\Models\Purchases\Bill;
use App\Models\Sales\Invoice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AgingTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_view_ar_aging(): void
    {
        $this->get(route('reports.ar-aging'))->assertRedirect(route('login'));
    }

    public function test_guest_cannot_view_ap_aging(): void
    {
        $this->get(route('reports.ap-aging'))->assertRedirect(route('login'));
    }

    public function test_overdue_invoice_lands_in_the_correct_bucket(): void
    {
        $user = User::factory()->create();
        $customer = Customer::factory()->create();
        $receivable = Account::factory()->create(['type' => 'asset']);
        $income = Account::factory()->create(['type' => 'income']);

        $invoice = Invoice::factory()->create([
            'customer_id' => $customer->id,
            'receivable_account_id' => $receivable->id,
            'invoice_date' => '2026-01-01',
            'due_date' => '2026-01-15',
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

        // 45 days after due_date (2026-01-15) falls in the 31-60 bucket.
        $response = $this->actingAs($user)->get(route('reports.ar-aging', ['as_of' => '2026-03-01']));
        $props = $response->viewData('page')['props'];

        $row = collect($props['rows'])->firstWhere('id', $customer->id);
        $this->assertSame('500.0000', $row['d31_60']);
        $this->assertSame('0.0000', $row['current']);
        $this->assertSame('500.0000', $row['total']);
    }

    public function test_fully_paid_invoice_does_not_appear_in_aging(): void
    {
        $user = User::factory()->create();
        $vendor = Vendor::factory()->create();
        $payable = Account::factory()->create(['type' => 'liability']);
        $expense = Account::factory()->create(['type' => 'expense']);

        $bill = Bill::factory()->create([
            'vendor_id' => $vendor->id,
            'payable_account_id' => $payable->id,
            'bill_date' => '2026-01-01',
            'status' => 'draft',
            'total' => 0,
        ]);
        $bill->items()->create([
            'account_id' => $expense->id,
            'description' => 'Line',
            'quantity' => 1,
            'unit_price' => 200,
            'discount' => 0,
            'line_total' => 200,
        ]);
        $bill = (new PostBill(new PostJournal))->handle($bill->fresh());

        $cash = Account::factory()->create(['type' => 'asset']);
        app(MakePayment::class)->handle([
            'vendor_id' => $vendor->id,
            'payment_account_id' => $cash->id,
            'payment_date' => '2026-01-10',
            'reference' => null,
            'method' => null,
            'amount' => 200,
            'notes' => null,
            'created_by' => null,
            'allocations' => [['bill_id' => $bill->id, 'amount' => 200]],
        ]);

        $response = $this->actingAs($user)->get(route('reports.ap-aging', ['as_of' => '2026-06-01']));
        $props = $response->viewData('page')['props'];

        $this->assertEmpty(collect($props['rows'])->where('id', $vendor->id));
    }
}
