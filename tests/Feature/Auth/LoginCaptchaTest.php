<?php

namespace Tests\Feature\Auth;

use App\Models\Accounting\AccountingSettings;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoginCaptchaTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_login_page_exposes_a_captcha_question_when_enabled(): void
    {
        AccountingSettings::current()->update(['login_captcha_enabled' => true]);

        $this->get('/login')->assertInertia(fn ($page) => $page
            ->where('captchaEnabled', true)
            ->has('captchaQuestion')
        );
    }

    public function test_login_is_rejected_without_a_captcha_answer(): void
    {
        AccountingSettings::current()->update(['login_captcha_enabled' => true]);
        $user = User::factory()->create();

        $this->post('/login', ['email' => $user->email, 'password' => 'password'])
            ->assertSessionHasErrors('captcha_answer');

        $this->assertGuest();
    }

    public function test_login_is_rejected_with_a_wrong_captcha_answer(): void
    {
        AccountingSettings::current()->update(['login_captcha_enabled' => true]);
        $user = User::factory()->create();
        session(['math_captcha_answer' => 7]);

        $this->post('/login', ['email' => $user->email, 'password' => 'password', 'captcha_answer' => 999])
            ->assertSessionHasErrors('captcha_answer');

        $this->assertGuest();
    }

    public function test_login_succeeds_with_the_correct_captcha_answer(): void
    {
        AccountingSettings::current()->update(['login_captcha_enabled' => true]);
        $user = User::factory()->create();
        session(['math_captcha_answer' => 7]);

        $this->post('/login', ['email' => $user->email, 'password' => 'password', 'captcha_answer' => 7])
            ->assertRedirect(route('dashboard', absolute: false));

        $this->assertAuthenticated();
    }

    public function test_a_captcha_answer_cannot_be_reused_after_it_was_already_checked(): void
    {
        AccountingSettings::current()->update(['login_captcha_enabled' => true]);
        $user = User::factory()->create();
        session(['math_captcha_answer' => 7]);

        // First submission is wrong on purpose — it still consumes the challenge.
        $this->post('/login', ['email' => $user->email, 'password' => 'wrong', 'captcha_answer' => 1]);

        // Resubmitting the *correct* answer to the now-consumed challenge must fail too.
        $this->post('/login', ['email' => $user->email, 'password' => 'password', 'captcha_answer' => 7])
            ->assertSessionHasErrors('captcha_answer');

        $this->assertGuest();
    }

    public function test_captcha_is_skipped_entirely_when_disabled_in_settings(): void
    {
        AccountingSettings::current()->update(['login_captcha_enabled' => false]);
        $user = User::factory()->create();

        $this->post('/login', ['email' => $user->email, 'password' => 'password'])
            ->assertRedirect(route('dashboard', absolute: false));

        $this->assertAuthenticated();
    }
}
