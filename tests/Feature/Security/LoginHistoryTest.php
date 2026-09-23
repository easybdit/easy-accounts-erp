<?php

namespace Tests\Feature\Security;

use App\Models\Accounting\AccountingSettings;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoginHistoryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        AccountingSettings::current()->update(['login_captcha_enabled' => false]);
    }

    public function test_guest_cannot_view_login_history(): void
    {
        $this->get(route('security.login-history.index'))->assertRedirect(route('login'));
    }

    public function test_a_user_without_audit_permission_cannot_view_login_history(): void
    {
        $user = User::factory()->create();
        $user->syncRoles(['Sales']);

        $this->actingAs($user)->get(route('security.login-history.index'))->assertForbidden();
    }

    public function test_a_successful_login_is_recorded_with_ip_and_user_agent(): void
    {
        $user = User::factory()->create();

        $this->withHeader('User-Agent', 'TestBrowser/1.0')
            ->post('/login', ['email' => $user->email, 'password' => 'password']);

        $this->assertDatabaseHas('activity_log', [
            'log_name' => 'auth',
            'event' => 'login',
            'causer_id' => $user->id,
        ]);
    }

    public function test_a_failed_login_is_recorded(): void
    {
        $user = User::factory()->create();

        $this->post('/login', ['email' => $user->email, 'password' => 'wrong-password']);

        $this->assertDatabaseHas('activity_log', [
            'log_name' => 'auth',
            'event' => 'login_failed',
        ]);
    }

    public function test_a_logout_is_recorded(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/logout');

        $this->assertDatabaseHas('activity_log', [
            'log_name' => 'auth',
            'event' => 'logout',
            'causer_id' => $user->id,
        ]);
    }

    public function test_an_auditor_can_view_and_filter_login_history(): void
    {
        $admin = User::factory()->create();
        $admin->syncRoles(['Administrator']);
        $target = User::factory()->create();

        $this->post('/login', ['email' => $target->email, 'password' => 'password']);
        $this->post('/logout');

        $this->actingAs($admin)->get(route('security.login-history.index', ['event' => 'login']))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Security/LoginHistory/Index')
                ->where('entries.data.0.event', 'login')
            );
    }
}
