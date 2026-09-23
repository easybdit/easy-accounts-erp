<?php

namespace Tests\Feature\Tax;

use App\Models\Accounting\Account;
use App\Models\Contacts\Vendor;
use App\Models\Tax\WithholdingTaxRate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WithholdingTaxRateTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_view_withholding_tax_rates(): void
    {
        $this->get(route('tax.withholding-rates.index'))->assertRedirect(route('login'));
    }

    public function test_a_rate_can_be_created(): void
    {
        $user = User::factory()->create();
        $liability = Account::factory()->create(['type' => 'liability']);

        $this->actingAs($user)->post(route('tax.withholding-rates.store'), [
            'name' => 'TDS 10% — Professional Fee',
            'rate' => 10,
            'liability_account_id' => $liability->id,
        ])->assertRedirect(route('tax.withholding-rates.index'));

        $this->assertDatabaseHas('withholding_tax_rates', ['name' => 'TDS 10% — Professional Fee', 'rate' => 10]);
    }

    public function test_the_liability_account_must_be_a_liability_account(): void
    {
        $user = User::factory()->create();
        $assetAsLiability = Account::factory()->create(['type' => 'asset']);

        $response = $this->actingAs($user)->post(route('tax.withholding-rates.store'), [
            'name' => 'Bad Rate',
            'rate' => 10,
            'liability_account_id' => $assetAsLiability->id,
        ]);

        $response->assertSessionHasErrors('liability_account_id');
    }

    public function test_name_must_be_unique(): void
    {
        WithholdingTaxRate::factory()->create(['name' => 'TDS 10%']);
        $user = User::factory()->create();
        $liability = Account::factory()->create(['type' => 'liability']);

        $response = $this->actingAs($user)->post(route('tax.withholding-rates.store'), [
            'name' => 'TDS 10%',
            'rate' => 10,
            'liability_account_id' => $liability->id,
        ]);

        $response->assertSessionHasErrors('name');
    }

    public function test_a_rate_used_on_a_vendor_payment_cannot_be_deleted(): void
    {
        $user = User::factory()->create();
        $rate = WithholdingTaxRate::factory()->create();
        $rate->vendorPayments()->create([
            'payment_number' => 'VPAY-2026-0001',
            'vendor_id' => Vendor::factory()->create()->id,
            'payment_account_id' => Account::factory()->create(['type' => 'asset'])->id,
            'payment_date' => now(),
            'amount' => 100,
            'withholding_tax_amount' => 10,
        ]);

        $response = $this->actingAs($user)->delete(route('tax.withholding-rates.destroy', $rate));

        $response->assertSessionHas('error');
        $this->assertDatabaseHas('withholding_tax_rates', ['id' => $rate->id]);
    }

    public function test_calculate_computes_the_withheld_amount(): void
    {
        $rate = WithholdingTaxRate::factory()->create(['rate' => 10]);

        $this->assertSame('50.0000', $rate->calculate('500'));
        $this->assertSame('7.5000', $rate->calculate('75'));
    }
}
