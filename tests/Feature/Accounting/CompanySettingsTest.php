<?php

namespace Tests\Feature\Accounting;

use App\Models\Accounting\AccountingSettings;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class CompanySettingsTest extends TestCase
{
    use RefreshDatabase;

    private function accountant(): User
    {
        $user = User::factory()->create();
        $user->syncRoles(['Accountant']);

        return $user;
    }

    // --- Email (SMTP) --------------------------------------------------

    public function test_a_user_without_settings_permission_cannot_update_mail_settings(): void
    {
        $user = User::factory()->create();
        $user->syncRoles(['Sales']);

        $this->actingAs($user)->put(route('accounting.settings.mail.update'), [
            'mail_host' => 'smtp.example.com',
            'mail_port' => 587,
            'mail_from_address' => 'noreply@example.com',
        ])->assertForbidden();
    }

    public function test_an_accountant_can_save_mail_settings(): void
    {
        $this->actingAs($this->accountant())->put(route('accounting.settings.mail.update'), [
            'mail_host' => 'smtp.example.com',
            'mail_port' => 2525,
            'mail_username' => 'mailer@example.com',
            'mail_password' => 'super-secret',
            'mail_encryption' => 'tls',
            'mail_from_address' => 'noreply@example.com',
            'mail_from_name' => 'EasyAccountsERP',
        ])->assertRedirect();

        $settings = AccountingSettings::current()->fresh();
        $this->assertSame('smtp.example.com', $settings->mail_host);
        $this->assertSame('super-secret', $settings->mail_password);
        $this->assertTrue($settings->mailIsConfigured());
    }

    public function test_leaving_the_mail_password_blank_keeps_the_existing_one(): void
    {
        $admin = $this->accountant();
        AccountingSettings::current()->update([
            'mail_host' => 'smtp.example.com', 'mail_port' => 587,
            'mail_from_address' => 'noreply@example.com', 'mail_password' => 'original-secret',
        ]);

        $this->actingAs($admin)->put(route('accounting.settings.mail.update'), [
            'mail_host' => 'smtp.example.com',
            'mail_port' => 587,
            'mail_from_address' => 'noreply@example.com',
        ]);

        $this->assertSame('original-secret', AccountingSettings::current()->fresh()->mail_password);
    }

    public function test_the_mail_password_is_never_sent_back_to_the_browser(): void
    {
        AccountingSettings::current()->update([
            'mail_host' => 'smtp.example.com', 'mail_port' => 587,
            'mail_from_address' => 'noreply@example.com', 'mail_password' => 'super-secret',
        ]);

        $this->actingAs($this->accountant())->get(route('accounting.settings.edit'))
            ->assertInertia(fn ($page) => $page
                ->where('settings.mail_password', null)
                ->where('settings.mail_password_set', true)
            );
    }

    public function test_sending_a_test_email_requires_mail_to_already_be_configured(): void
    {
        Mail::fake();

        $this->actingAs($this->accountant())->post(route('accounting.settings.mail.test'), [
            'test_email' => 'someone@example.com',
        ])->assertSessionHas('error');

        Mail::assertNothingSent();
    }

    public function test_sending_a_test_email_when_configured(): void
    {
        Mail::fake();
        AccountingSettings::current()->update([
            'mail_host' => 'smtp.example.com', 'mail_port' => 587, 'mail_from_address' => 'noreply@example.com',
        ]);

        $this->actingAs($this->accountant())->post(route('accounting.settings.mail.test'), [
            'test_email' => 'someone@example.com',
        ])->assertSessionHas('success');
    }

    // --- Payment gateway (SSLCommerz) -----------------------------------

    public function test_a_user_without_settings_permission_cannot_update_payment_gateway_settings(): void
    {
        $user = User::factory()->create();
        $user->syncRoles(['Sales']);

        $this->actingAs($user)->put(route('accounting.settings.payment-gateway.update'), [
            'sslcommerz_enabled' => true,
            'sslcommerz_store_id' => 'store123',
        ])->assertForbidden();
    }

    public function test_an_accountant_can_enable_and_configure_the_payment_gateway(): void
    {
        $this->actingAs($this->accountant())->put(route('accounting.settings.payment-gateway.update'), [
            'sslcommerz_enabled' => true,
            'sslcommerz_store_id' => 'store123',
            'sslcommerz_store_password' => 'gateway-secret',
            'sslcommerz_sandbox' => true,
            'sslcommerz_currency' => 'BDT',
        ])->assertRedirect();

        $settings = AccountingSettings::current()->fresh();
        $this->assertTrue($settings->sslcommerz_enabled);
        $this->assertSame('store123', $settings->sslcommerz_store_id);
        $this->assertSame('gateway-secret', $settings->sslcommerz_store_password);
        $this->assertTrue($settings->sslcommerzIsConfigured());
    }

    public function test_leaving_the_gateway_password_blank_keeps_the_existing_one(): void
    {
        AccountingSettings::current()->update([
            'sslcommerz_enabled' => true, 'sslcommerz_store_id' => 'store123',
            'sslcommerz_store_password' => 'original-secret',
        ]);

        $this->actingAs($this->accountant())->put(route('accounting.settings.payment-gateway.update'), [
            'sslcommerz_enabled' => true,
            'sslcommerz_store_id' => 'store123',
        ]);

        $this->assertSame('original-secret', AccountingSettings::current()->fresh()->sslcommerz_store_password);
    }

    public function test_the_gateway_password_is_never_sent_back_to_the_browser(): void
    {
        AccountingSettings::current()->update([
            'sslcommerz_enabled' => true, 'sslcommerz_store_id' => 'store123',
            'sslcommerz_store_password' => 'super-secret',
        ]);

        $this->actingAs($this->accountant())->get(route('accounting.settings.edit'))
            ->assertInertia(fn ($page) => $page
                ->where('settings.sslcommerz_store_password', null)
                ->where('settings.sslcommerz_store_password_set', true)
            );
    }

    // --- Login security ---------------------------------------------------

    public function test_a_user_without_settings_permission_cannot_update_login_security_settings(): void
    {
        $user = User::factory()->create();
        $user->syncRoles(['Sales']);

        $this->actingAs($user)->put(route('accounting.settings.login-security.update'), [
            'login_max_attempts' => 10,
            'login_lockout_minutes' => 30,
            'login_captcha_enabled' => false,
        ])->assertForbidden();
    }

    public function test_an_accountant_can_update_login_security_settings(): void
    {
        $this->actingAs($this->accountant())->put(route('accounting.settings.login-security.update'), [
            'login_max_attempts' => 10,
            'login_lockout_minutes' => 30,
            'login_captcha_enabled' => false,
        ])->assertRedirect();

        $settings = AccountingSettings::current()->fresh();
        $this->assertSame(10, $settings->login_max_attempts);
        $this->assertSame(30, $settings->login_lockout_minutes);
        $this->assertFalse($settings->login_captcha_enabled);
    }

    public function test_login_max_attempts_must_be_at_least_three(): void
    {
        $this->actingAs($this->accountant())->put(route('accounting.settings.login-security.update'), [
            'login_max_attempts' => 1,
            'login_lockout_minutes' => 15,
        ])->assertSessionHasErrors('login_max_attempts');
    }
}
