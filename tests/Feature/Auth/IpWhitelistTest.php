<?php

namespace Tests\Feature\Auth;

use App\Models\Accounting\AccountingSettings;
use App\Models\Security\IpWhitelistEntry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IpWhitelistTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Captcha is a separate concern (see LoginCaptchaTest).
        AccountingSettings::current()->update(['login_captcha_enabled' => false]);
    }

    public function test_login_is_unaffected_when_the_whitelist_is_disabled(): void
    {
        $user = User::factory()->create();

        $this->post('/login', ['email' => $user->email, 'password' => 'password'])
            ->assertRedirect(route('dashboard', absolute: false));

        $this->assertAuthenticated();
    }

    public function test_login_is_unaffected_when_the_whitelist_is_enabled_but_empty(): void
    {
        // Fails open on an empty list — see AccountingSettings::ipIsWhitelisted().
        AccountingSettings::current()->update(['ip_whitelist_enabled' => true]);
        $user = User::factory()->create();

        $this->post('/login', ['email' => $user->email, 'password' => 'password'])
            ->assertRedirect(route('dashboard', absolute: false));

        $this->assertAuthenticated();
    }

    public function test_login_from_a_non_whitelisted_ip_is_blocked(): void
    {
        AccountingSettings::current()->update(['ip_whitelist_enabled' => true]);
        IpWhitelistEntry::create(['ip_address' => '203.0.113.7']);
        $user = User::factory()->create();

        // The test client's default IP (127.0.0.1) is not in the list.
        $this->post('/login', ['email' => $user->email, 'password' => 'password'])
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_login_from_an_exact_whitelisted_ip_succeeds(): void
    {
        AccountingSettings::current()->update(['ip_whitelist_enabled' => true]);
        IpWhitelistEntry::create(['ip_address' => '127.0.0.1']);
        $user = User::factory()->create();

        $this->post('/login', ['email' => $user->email, 'password' => 'password'])
            ->assertRedirect(route('dashboard', absolute: false));

        $this->assertAuthenticated();
    }

    public function test_login_from_an_ip_within_a_whitelisted_cidr_range_succeeds(): void
    {
        AccountingSettings::current()->update(['ip_whitelist_enabled' => true]);
        IpWhitelistEntry::create(['ip_address' => '127.0.0.0/24']);
        $user = User::factory()->create();

        $this->post('/login', ['email' => $user->email, 'password' => 'password'])
            ->assertRedirect(route('dashboard', absolute: false));

        $this->assertAuthenticated();
    }

    public function test_login_from_outside_a_whitelisted_cidr_range_is_blocked(): void
    {
        AccountingSettings::current()->update(['ip_whitelist_enabled' => true]);
        IpWhitelistEntry::create(['ip_address' => '203.0.113.0/24']);
        $user = User::factory()->create();

        $this->post('/login', ['email' => $user->email, 'password' => 'password'])
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    // --- Managing entries ------------------------------------------------

    private function accountant(): User
    {
        $user = User::factory()->create();
        $user->syncRoles(['Accountant']);

        return $user;
    }

    public function test_a_user_without_settings_permission_cannot_add_an_entry(): void
    {
        $user = User::factory()->create();
        $user->syncRoles(['Sales']);

        $this->actingAs($user)->post(route('security.ip-whitelist.store'), [
            'ip_address' => '203.0.113.7',
        ])->assertForbidden();

        $this->assertDatabaseCount('ip_whitelist_entries', 0);
    }

    public function test_an_accountant_can_add_an_entry(): void
    {
        $this->actingAs($this->accountant())->post(route('security.ip-whitelist.store'), [
            'ip_address' => '203.0.113.7',
            'label' => 'Head office',
        ])->assertRedirect();

        $this->assertDatabaseHas('ip_whitelist_entries', ['ip_address' => '203.0.113.7', 'label' => 'Head office']);
    }

    public function test_a_malformed_ip_is_rejected(): void
    {
        $this->actingAs($this->accountant())->post(route('security.ip-whitelist.store'), [
            'ip_address' => 'not-an-ip',
        ])->assertSessionHasErrors('ip_address');
    }

    public function test_a_cidr_suffix_above_32_is_rejected(): void
    {
        $this->actingAs($this->accountant())->post(route('security.ip-whitelist.store'), [
            'ip_address' => '203.0.113.0/40',
        ])->assertSessionHasErrors('ip_address');
    }

    public function test_a_duplicate_ip_is_rejected(): void
    {
        IpWhitelistEntry::create(['ip_address' => '203.0.113.7']);

        $this->actingAs($this->accountant())->post(route('security.ip-whitelist.store'), [
            'ip_address' => '203.0.113.7',
        ])->assertSessionHasErrors('ip_address');
    }

    public function test_enabling_the_whitelist_without_any_entries_is_rejected(): void
    {
        $this->actingAs($this->accountant())->put(route('accounting.settings.ip-whitelist-enabled.update'), [
            'ip_whitelist_enabled' => true,
        ])->assertSessionHas('error');

        $this->assertFalse(AccountingSettings::current()->fresh()->ip_whitelist_enabled);
    }

    public function test_enabling_the_whitelist_with_an_entry_succeeds(): void
    {
        IpWhitelistEntry::create(['ip_address' => '203.0.113.7']);

        $this->actingAs($this->accountant())->put(route('accounting.settings.ip-whitelist-enabled.update'), [
            'ip_whitelist_enabled' => true,
        ])->assertRedirect();

        $this->assertTrue(AccountingSettings::current()->fresh()->ip_whitelist_enabled);
    }

    public function test_an_accountant_can_remove_an_entry(): void
    {
        $entry = IpWhitelistEntry::create(['ip_address' => '203.0.113.7']);

        $this->actingAs($this->accountant())->delete(route('security.ip-whitelist.destroy', $entry))
            ->assertRedirect();

        $this->assertDatabaseCount('ip_whitelist_entries', 0);
    }

    public function test_the_entry_matching_the_current_ip_cannot_be_removed_while_enforced(): void
    {
        AccountingSettings::current()->update(['ip_whitelist_enabled' => true]);
        $entry = IpWhitelistEntry::create(['ip_address' => '127.0.0.1']);

        $this->actingAs($this->accountant())->delete(route('security.ip-whitelist.destroy', $entry))
            ->assertSessionHas('error');

        $this->assertDatabaseCount('ip_whitelist_entries', 1);
    }
}
