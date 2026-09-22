<?php

namespace Tests\Feature\Tax;

use App\Models\Accounting\Account;
use App\Models\Tax\TaxRate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaxRateTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_view_tax_rates(): void
    {
        $this->get(route('tax.rates.index'))->assertRedirect(route('login'));
    }

    public function test_tax_rate_can_be_created(): void
    {
        $user = User::factory()->create();
        $liability = Account::factory()->create(['type' => 'liability']);

        $this->actingAs($user)->post(route('tax.rates.store'), [
            'name' => 'VAT 15%',
            'rate' => 15,
            'tax_account_id' => $liability->id,
        ])->assertRedirect(route('tax.rates.index'));

        $this->assertDatabaseHas('tax_rates', ['name' => 'VAT 15%', 'rate' => 15]);
    }

    public function test_tax_account_must_be_a_liability_account(): void
    {
        $user = User::factory()->create();
        $assetAsTax = Account::factory()->create(['type' => 'asset']);

        $response = $this->actingAs($user)->post(route('tax.rates.store'), [
            'name' => 'Bad Tax',
            'rate' => 10,
            'tax_account_id' => $assetAsTax->id,
        ]);

        $response->assertSessionHasErrors('tax_account_id');
    }

    public function test_name_must_be_unique(): void
    {
        TaxRate::factory()->create(['name' => 'VAT 15%']);
        $user = User::factory()->create();
        $liability = Account::factory()->create(['type' => 'liability']);

        $response = $this->actingAs($user)->post(route('tax.rates.store'), [
            'name' => 'VAT 15%',
            'rate' => 15,
            'tax_account_id' => $liability->id,
        ]);

        $response->assertSessionHasErrors('name');
    }

    public function test_calculate_computes_exclusive_tax(): void
    {
        $rate = TaxRate::factory()->create(['rate' => 15]);

        $this->assertSame('15.0000', $rate->calculate('100'));
        $this->assertSame('7.5000', $rate->calculate('50'));
    }
}
