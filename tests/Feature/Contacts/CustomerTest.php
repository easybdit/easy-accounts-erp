<?php

namespace Tests\Feature\Contacts;

use App\Actions\Accounting\PostJournal;
use App\Models\Accounting\Account;
use App\Models\Contacts\Customer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_view_customers(): void
    {
        $this->get(route('customers.index'))->assertRedirect(route('login'));
    }

    public function test_customer_can_be_created(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('customers.store'), [
            'name' => 'Acme Traders',
            'email' => 'billing@acme.example',
            'opening_balance' => 500,
        ])->assertRedirect(route('customers.index'));

        $this->assertDatabaseHas('customers', ['name' => 'Acme Traders', 'opening_balance' => 500]);
    }

    public function test_name_is_required(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('customers.store'), [
            'email' => 'no-name@example.com',
        ])->assertSessionHasErrors('name');
    }

    public function test_customer_can_be_updated(): void
    {
        $customer = Customer::factory()->create(['name' => 'Old Name']);
        $user = User::factory()->create();

        $this->actingAs($user)->put(route('customers.update', $customer), [
            'name' => 'New Name',
            'opening_balance' => $customer->opening_balance,
        ])->assertRedirect(route('customers.index'));

        $this->assertDatabaseHas('customers', ['id' => $customer->id, 'name' => 'New Name']);
    }

    public function test_customer_without_transactions_can_be_deleted(): void
    {
        $customer = Customer::factory()->create();
        $user = User::factory()->create();

        $this->actingAs($user)->delete(route('customers.destroy', $customer))
            ->assertRedirect(route('customers.index'));

        $this->assertDatabaseMissing('customers', ['id' => $customer->id]);
    }

    public function test_customer_with_transaction_history_cannot_be_deleted(): void
    {
        $user = User::factory()->create();
        $customer = Customer::factory()->create(['opening_balance' => 0]);
        $receivable = Account::factory()->create(['type' => 'asset']);
        $revenue = Account::factory()->create(['type' => 'income']);

        (new PostJournal)->handle([
            'date' => '2026-01-01',
            'reference' => null,
            'description' => null,
            'created_by' => null,
            'lines' => [
                ['account_id' => $receivable->id, 'customer_id' => $customer->id, 'debit' => 100, 'credit' => 0],
                ['account_id' => $revenue->id, 'debit' => 0, 'credit' => 100],
            ],
        ]);

        $this->actingAs($user)->delete(route('customers.destroy', $customer))
            ->assertSessionHasErrors('customer');

        $this->assertDatabaseHas('customers', ['id' => $customer->id]);
    }

    public function test_current_balance_reflects_opening_balance_plus_tagged_entries(): void
    {
        $user = User::factory()->create();
        $customer = Customer::factory()->create(['opening_balance' => 1000]);
        $receivable = Account::factory()->create(['type' => 'asset']);
        $revenue = Account::factory()->create(['type' => 'income']);

        (new PostJournal)->handle([
            'date' => '2026-01-01',
            'reference' => null,
            'description' => null,
            'created_by' => null,
            'lines' => [
                ['account_id' => $receivable->id, 'customer_id' => $customer->id, 'debit' => 250, 'credit' => 0],
                ['account_id' => $revenue->id, 'debit' => 0, 'credit' => 250],
            ],
        ]);

        $this->assertSame('1250.0000', $customer->fresh()->currentBalance());

        $response = $this->actingAs($user)->get(route('customers.show', $customer));
        $response->assertOk();
        $this->assertSame('1250.0000', $response->viewData('page')['props']['currentBalance']);
    }
}
