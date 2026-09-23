<?php

namespace Tests\Feature\Reports;

use App\Actions\Purchases\MakePayment;
use App\Actions\Purchases\PostBill;
use App\Actions\Purchases\SaveBillDraft;
use App\Models\Accounting\Account;
use App\Models\Contacts\Vendor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VendorStatementTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_blocked(): void
    {
        $this->get(route('reports.vendor-statement'))->assertRedirect(route('login'));
    }

    public function test_with_no_vendor_selected_it_renders_without_a_statement(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('reports.vendor-statement'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page->where('statement', null));
    }

    public function test_it_shows_opening_balance_bill_and_payment_with_a_running_balance(): void
    {
        $user = User::factory()->create();
        $vendor = Vendor::factory()->create(['opening_balance' => 200]);
        $payable = Account::factory()->create(['type' => 'liability']);
        $expense = Account::factory()->create(['type' => 'expense']);
        $cash = Account::factory()->create(['type' => 'asset']);

        $bill = app(SaveBillDraft::class)->handle([
            'vendor_id' => $vendor->id,
            'payable_account_id' => $payable->id,
            'bill_date' => '2026-03-10',
            'due_date' => null,
            'created_by' => $user->id,
            'items' => [
                ['account_id' => $expense->id, 'description' => 'Bandwidth', 'quantity' => 1, 'unit_price' => 500, 'discount' => 0],
            ],
        ]);
        app(PostBill::class)->handle($bill);

        app(MakePayment::class)->handle([
            'vendor_id' => $vendor->id,
            'payment_account_id' => $cash->id,
            'payment_date' => '2026-03-15',
            'reference' => null,
            'method' => null,
            'amount' => 300,
            'notes' => null,
            'created_by' => $user->id,
            'allocations' => [
                ['bill_id' => $bill->id, 'amount' => 300],
            ],
        ]);

        $response = $this->actingAs($user)->get(route('reports.vendor-statement', [
            'vendor_id' => $vendor->id,
            'from' => '2026-03-01',
            'to' => '2026-03-31',
        ]));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->where('statement.starting_balance', '200.0000')
            ->where('statement.entries.0.running_balance', '700.0000')
            ->where('statement.entries.1.running_balance', '400.0000')
            ->where('statement.ending_balance', '400.0000')
            ->has('statement.entries', 2));
    }

    public function test_transactions_before_the_from_date_are_folded_into_the_opening_balance(): void
    {
        $user = User::factory()->create();
        $vendor = Vendor::factory()->create(['opening_balance' => 0]);
        $payable = Account::factory()->create(['type' => 'liability']);
        $expense = Account::factory()->create(['type' => 'expense']);

        $bill = app(SaveBillDraft::class)->handle([
            'vendor_id' => $vendor->id,
            'payable_account_id' => $payable->id,
            'bill_date' => '2026-01-05',
            'due_date' => null,
            'created_by' => $user->id,
            'items' => [
                ['account_id' => $expense->id, 'description' => 'Old bill', 'quantity' => 1, 'unit_price' => 150, 'discount' => 0],
            ],
        ]);
        app(PostBill::class)->handle($bill);

        $response = $this->actingAs($user)->get(route('reports.vendor-statement', [
            'vendor_id' => $vendor->id,
            'from' => '2026-02-01',
            'to' => '2026-02-28',
        ]));

        $response->assertInertia(fn ($page) => $page
            ->where('statement.starting_balance', '150.0000')
            ->where('statement.ending_balance', '150.0000')
            ->has('statement.entries', 0));
    }

    public function test_pdf_can_be_downloaded(): void
    {
        $user = User::factory()->create();
        $vendor = Vendor::factory()->create();

        $response = $this->actingAs($user)->get(route('reports.vendor-statement.pdf', ['vendor_id' => $vendor->id]));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
    }
}
