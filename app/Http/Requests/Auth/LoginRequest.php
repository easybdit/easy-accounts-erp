<?php

namespace App\Http\Requests\Auth;

use App\Models\Accounting\AccountingSettings;
use App\Models\User;
use App\Support\MathCaptcha;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ];
    }

    /**
     * Attempt to authenticate the request's credentials.
     *
     * Order matters: the IP whitelist (if enabled) is checked before
     * anything else — a request from an unlisted IP is rejected outright,
     * without touching the rate limiter, captcha, or credentials, so it
     * can't be used to probe any of those. Then the per-IP+email rate
     * limit (fast, in-memory), then the persistent per-account lock
     * (Section 90's brute-force control — survives across IPs and app
     * restarts, unlike the rate limiter), then the math captcha, and only
     * then the actual credentials. Every outcome is recorded to the 'auth'
     * activity log (Security → Login History) with the requesting IP and
     * user agent.
     *
     * @throws ValidationException
     */
    public function authenticate(): void
    {
        $settings = AccountingSettings::current();

        if (! $settings->ipIsWhitelisted($this->ip())) {
            $this->logAuthEvent('login_blocked_ip', null, "Login blocked — IP not whitelisted (attempted email: {$this->string('email')})");

            throw ValidationException::withMessages([
                'email' => 'Access from your network is not permitted. Contact an administrator if you believe this is a mistake.',
            ]);
        }

        $this->ensureIsNotRateLimited();

        $user = User::whereRaw('lower(email) = ?', [Str::lower($this->string('email'))])->first();

        $this->ensureAccountIsNotLocked($user);

        if ($settings->login_captcha_enabled && ! MathCaptcha::check($this->input('captcha_answer'))) {
            RateLimiter::hit($this->throttleKey());
            $this->logAuthEvent('login_failed', $user, "Failed login attempt for {$this->string('email')} (wrong security check answer)", ['reason' => 'bad_captcha']);

            throw ValidationException::withMessages([
                'captcha_answer' => 'That answer is not correct.',
            ]);
        }

        if (! Auth::attempt($this->only('email', 'password'), $this->boolean('remember'))) {
            RateLimiter::hit($this->throttleKey());
            $this->recordFailedAttempt($user, $settings);
            $this->logAuthEvent('login_failed', $user, "Failed login attempt for {$this->string('email')}", ['reason' => 'bad_credentials']);

            throw ValidationException::withMessages([
                'email' => trans('auth.failed'),
            ]);
        }

        RateLimiter::clear($this->throttleKey());

        if ($user && ($user->failed_login_attempts > 0 || $user->locked_until)) {
            $user->forceFill(['failed_login_attempts' => 0, 'locked_until' => null])->save();
        }

        $this->logAuthEvent('login', $user, "Successful login for \"{$user->name}\"");
    }

    /**
     * @throws ValidationException
     */
    private function ensureAccountIsNotLocked(?User $user): void
    {
        if (! $user?->locked_until || $user->locked_until->isPast()) {
            return;
        }

        $minutes = max(1, (int) now()->diffInMinutes($user->locked_until));

        $this->logAuthEvent('login_blocked_locked', $user, "Login blocked — account locked for \"{$user->name}\"");

        throw ValidationException::withMessages([
            'email' => "Too many failed attempts. This account is locked for another {$minutes} minute(s).",
        ]);
    }

    private function recordFailedAttempt(?User $user, AccountingSettings $settings): void
    {
        if (! $user) {
            return;
        }

        $attempts = $user->failed_login_attempts + 1;

        $user->forceFill([
            'failed_login_attempts' => $attempts,
            'locked_until' => $attempts >= $settings->login_max_attempts
                ? now()->addMinutes($settings->login_lockout_minutes)
                : $user->locked_until,
        ])->save();
    }

    /**
     * Records one entry in the 'auth' activity log (kept separate from the
     * default log_name so Security → Login History can show a focused
     * login/logout trail instead of mixing in every model change).
     */
    private function logAuthEvent(string $event, ?User $user, string $description, array $properties = []): void
    {
        activity('auth')
            ->causedBy($user)
            ->withProperties([
                'ip' => $this->ip(),
                'user_agent' => $this->userAgent(),
                ...$properties,
            ])
            ->event($event)
            ->log($description);
    }

    /**
     * Ensure the login request is not rate limited.
     *
     * @throws ValidationException
     */
    public function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout($this));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'email' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    /**
     * Get the rate limiting throttle key for the request.
     */
    public function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->string('email')).'|'.$this->ip());
    }
}
