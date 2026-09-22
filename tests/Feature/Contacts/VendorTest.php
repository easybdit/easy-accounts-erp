<?php

namespace Tests\Feature\Contacts;

use App\Actions\Accounting\PostJournal;
use App\Models\Accounting\Account;
use App\Models\Contacts\Customer;
use App\Models\Contacts\Vendor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VendorTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_view_vendors(): void
    {
        $this->get(route('vendors.index'))->assertRedirect(route('login'));
    }

    public function test_vendor_can_be_created(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('vendors.store'), [
            'name' => 'Global Supplies Co',
            'email' => 'sales@globalsupplies.example',
            'opening_balance' => 300,
        ])->assertRedirect(route('vendors.index'));

        $this->assertDatabaseHas('vendors', ['name' => 'Global Supplies Co', 'opening_balance' => 300]);
    }

    public function test_name_is_required(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('vendors.store'), [
            'email' => 'no-name@example.com',
        ])->assertSessionHasErrors('name');
    }

    public function test_vendor_without_transactions_can_be_deleted(): void
    {
        $vendor = Vendor::factory()->create();
        $user = User::factory()->create();

        $this->actingAs($user)->delete(route('vendors.destroy', $vendor))
            ->assertRedirect(route('vendors.index'));

        $this->assertDatabaseMissing('vendors', ['id' => $vendor->id]);
    }

    public function test_vendor_with_transaction_history_cannot_be_deleted(): void
    {
        $user = User::factory()->create();
        $vendor = Vendor::factory()->create(['opening_balance' => 0]);
        $expense = Account::factory()->create(['type' => 'expense']);
        $payable = Account::factory()->create(['type' => 'liability']);

        (new PostJournal)->handle([
            'date' => '2026-01-01',
            'reference' => null,
            'description' => null,
            'created_by' => null,
            'lines' => [
                ['account_id' => $expense->id, 'debit' => 100, 'credit' => 0],
                ['account_id' => $payable->id, 'vendor_id' => $vendor->id, 'debit' => 0, 'credit' => 100],
            ],
        ]);

        $this->actingAs($user)->delete(route('vendors.destroy', $vendor))
            ->assertSessionHasErrors('vendor');

        $this->assertDatabaseHas('vendors', ['id' => $vendor->id]);
    }

    public function test_current_balance_reflects_opening_balance_plus_tagged_entries(): void
    {
        $vendor = Vendor::factory()->create(['opening_balance' => 500]);
        $expense = Account::factory()->create(['type' => 'expense']);
        $payable = Account::factory()->create(['type' => 'liability']);

        (new PostJournal)->handle([
            'date' => '2026-01-01',
            'reference' => null,
            'description' => null,
            'created_by' => null,
            'lines' => [
                ['account_id' => $expense->id, 'debit' => 150, 'credit' => 0],
                ['account_id' => $payable->id, 'vendor_id' => $vendor->id, 'debit' => 0, 'credit' => 150],
            ],
        ]);

        $this->assertSame('650.0000', $vendor->fresh()->currentBalance());
    }

    public function test_a_line_cannot_be_tagged_to_both_a_customer_and_a_vendor(): void
    {
        $user = User::factory()->create();
        $customer = Customer::factory()->create();
        $vendor = Vendor::factory()->create();
        $a = Account::factory()->create(['type' => 'asset']);
        $b = Account::factory()->create(['type' => 'income']);

        $response = $this->actingAs($user)->post(route('accounting.journals.store'), [
            'date' => '2026-01-01',
            'lines' => [
                ['account_id' => $a->id, 'customer_id' => $customer->id, 'vendor_id' => $vendor->id, 'debit' => 100, 'credit' => 0],
                ['account_id' => $b->id, 'debit' => 0, 'credit' => 100],
            ],
        ]);

        $response->assertSessionHasErrors('lines.0');
    }
}
