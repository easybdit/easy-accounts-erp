<?php

namespace Tests\Feature\Sales;

use App\Actions\Sales\ConvertEstimateToInvoice;
use App\Mail\EstimateMail;
use App\Models\Accounting\Account;
use App\Models\Contacts\Customer;
use App\Models\Sales\Estimate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class EstimateEmailTest extends TestCase
{
    use RefreshDatabase;

    private function estimate(array $customerOverrides = []): Estimate
    {
        $customer = Customer::factory()->create($customerOverrides);
        $receivable = Account::factory()->create(['type' => 'asset']);
        $income = Account::factory()->create(['type' => 'income']);

        $estimate = Estimate::create([
            'estimate_number' => 'EST-0001',
            'customer_id' => $customer->id,
            'receivable_account_id' => $receivable->id,
            'estimate_date' => '2026-01-10',
            'status' => 'draft',
            'subtotal' => 200, 'discount_total' => 0, 'tax_total' => 0, 'total' => 200,
        ]);
        $estimate->items()->create([
            'account_id' => $income->id,
            'description' => 'Network setup',
            'quantity' => 2,
            'unit_price' => 100,
            'discount' => 0,
            'line_total' => 200,
        ]);

        return $estimate->fresh();
    }

    public function test_a_draft_estimate_can_be_emailed_and_moves_to_sent(): void
    {
        Mail::fake();
        $user = User::factory()->create();
        $estimate = $this->estimate(['email' => 'customer@example.com']);

        $this->actingAs($user)->post(route('sales.estimates.email', $estimate))->assertRedirect();

        Mail::assertSent(EstimateMail::class, function (EstimateMail $mail) use ($estimate) {
            return $mail->estimate->is($estimate) && $mail->hasTo('customer@example.com');
        });

        $estimate->refresh();
        $this->assertSame('sent', $estimate->status);
        $this->assertNotNull($estimate->last_emailed_at);
    }

    public function test_a_custom_recipient_email_overrides_the_customer_email(): void
    {
        Mail::fake();
        $user = User::factory()->create();
        $estimate = $this->estimate(['email' => 'customer@example.com']);

        $this->actingAs($user)->post(route('sales.estimates.email', $estimate), [
            'recipient_email' => 'billing@example.com',
        ])->assertRedirect();

        Mail::assertSent(EstimateMail::class, fn (EstimateMail $mail) => $mail->hasTo('billing@example.com'));
    }

    public function test_emailing_an_already_sent_estimate_does_not_change_its_status(): void
    {
        Mail::fake();
        $user = User::factory()->create();
        $estimate = $this->estimate(['email' => 'customer@example.com']);
        $estimate->update(['status' => 'accepted']);

        $this->actingAs($user)->post(route('sales.estimates.email', $estimate))->assertRedirect();

        $this->assertSame('accepted', $estimate->fresh()->status);
    }

    public function test_a_converted_estimate_cannot_be_emailed(): void
    {
        Mail::fake();
        $user = User::factory()->create();
        $estimate = $this->estimate(['email' => 'customer@example.com']);
        app(ConvertEstimateToInvoice::class)->handle($estimate, $user->id);

        $this->actingAs($user)->post(route('sales.estimates.email', $estimate))->assertSessionHas('error');

        Mail::assertNothingSent();
    }

    public function test_a_customer_with_no_email_and_no_override_cannot_be_emailed(): void
    {
        Mail::fake();
        $user = User::factory()->create();
        $estimate = $this->estimate(['email' => null]);

        $this->actingAs($user)->post(route('sales.estimates.email', $estimate))->assertSessionHas('error');

        Mail::assertNothingSent();
        $this->assertNull($estimate->fresh()->last_emailed_at);
    }

    public function test_the_mail_body_renders_without_error(): void
    {
        $estimate = $this->estimate();
        $estimate->load('items.account');

        $html = (new EstimateMail($estimate))->render();

        $this->assertStringContainsString($estimate->estimate_number, $html);
    }

    public function test_pdf_can_be_downloaded(): void
    {
        $user = User::factory()->create();
        $estimate = $this->estimate();

        $response = $this->actingAs($user)->get(route('sales.estimates.pdf', $estimate));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
    }
}
