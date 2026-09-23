<?php

namespace Tests\Feature\Auth;

use App\Models\Accounting\AccountingSettings;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoginLockoutTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Captcha is a separate concern (see LoginCaptchaTest) — disabled
        // here so these tests exercise the credential/lockout path only.
        AccountingSettings::current()->update([
            'login_captcha_enabled' => false,
            'login_max_attempts' => 3,
            'login_lockout_minutes' => 15,
        ]);
    }

    private function attemptWrongPassword(User $user): void
    {
        $this->post('/login', ['email' => $user->email, 'password' => 'wrong-password']);
    }

    public function test_failed_attempts_are_counted_on_the_account(): void
    {
        $user = User::factory()->create();

        $this->attemptWrongPassword($user);

        $this->assertSame(1, $user->fresh()->failed_login_attempts);
        $this->assertNull($user->fresh()->locked_until);
    }

    public function test_the_account_locks_after_reaching_the_configured_max_attempts(): void
    {
        $user = User::factory()->create();

        $this->attemptWrongPassword($user);
        $this->attemptWrongPassword($user);
        $this->attemptWrongPassword($user);

        $fresh = $user->fresh();
        $this->assertSame(3, $fresh->failed_login_attempts);
        $this->assertNotNull($fresh->locked_until);
        $this->assertTrue($fresh->locked_until->isFuture());
    }

    public function test_a_locked_account_is_rejected_even_with_the_correct_password(): void
    {
        $user = User::factory()->create();
        $user->forceFill(['locked_until' => now()->addMinutes(10)])->save();

        $this->post('/login', ['email' => $user->email, 'password' => 'password'])
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_a_lock_that_has_already_expired_no_longer_blocks_login(): void
    {
        $user = User::factory()->create();
        $user->forceFill(['locked_until' => now()->subMinute()])->save();

        $this->post('/login', ['email' => $user->email, 'password' => 'password'])
            ->assertRedirect(route('dashboard', absolute: false));

        $this->assertAuthenticated();
    }

    public function test_a_successful_login_resets_failed_attempts_and_any_lock(): void
    {
        $user = User::factory()->create(['failed_login_attempts' => 2]);

        $this->post('/login', ['email' => $user->email, 'password' => 'password']);

        $fresh = $user->fresh();
        $this->assertSame(0, $fresh->failed_login_attempts);
        $this->assertNull($fresh->locked_until);
    }

    public function test_an_administrator_can_unlock_an_account_immediately(): void
    {
        $admin = User::factory()->create();
        $admin->syncRoles(['Administrator']);
        $target = User::factory()->create([
            'failed_login_attempts' => 3,
            'locked_until' => now()->addMinutes(15),
        ]);

        $this->actingAs($admin)->post(route('security.users.unlock', $target))
            ->assertRedirect();

        $fresh = $target->fresh();
        $this->assertSame(0, $fresh->failed_login_attempts);
        $this->assertNull($fresh->locked_until);
    }

    public function test_a_user_without_users_manage_permission_cannot_unlock_an_account(): void
    {
        $user = User::factory()->create();
        $user->syncRoles(['Sales']);
        $target = User::factory()->create(['locked_until' => now()->addMinutes(15)]);

        $this->actingAs($user)->post(route('security.users.unlock', $target))
            ->assertForbidden();

        $this->assertNotNull($target->fresh()->locked_until);
    }
}
