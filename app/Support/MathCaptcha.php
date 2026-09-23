<?php

namespace App\Support;

use Illuminate\Support\Facades\Session;

/**
 * A dependency-free arithmetic captcha for the login form (Section 90: no
 * third-party captcha service, no API key to manage, works offline). The
 * expected answer lives only in the session — never in the response the
 * browser sees — so it can't be read off the page and submitted back.
 */
class MathCaptcha
{
    private const SESSION_KEY = 'math_captcha_answer';

    /**
     * Generates a new challenge, stores its answer in the session, and
     * returns the human-readable question to display.
     */
    public static function generate(): string
    {
        $a = random_int(1, 20);
        $b = random_int(1, 20);
        $operator = random_int(0, 1) === 0 ? '+' : '-';

        // Never let subtraction go negative — keeps the question friendly
        // for a non-technical user typing a login form on a phone.
        if ($operator === '-' && $b > $a) {
            [$a, $b] = [$b, $a];
        }

        $answer = $operator === '+' ? $a + $b : $a - $b;

        Session::put(self::SESSION_KEY, $answer);

        return "{$a} {$operator} {$b}";
    }

    /**
     * Checks a submitted answer against the session's stored value, then
     * clears it — a captcha challenge is single-use whether it passes or
     * fails, so a stale answer can never be replayed.
     */
    public static function check(mixed $submitted): bool
    {
        $expected = Session::pull(self::SESSION_KEY);

        if ($expected === null || $submitted === null || $submitted === '') {
            return false;
        }

        return is_numeric($submitted) && (int) $submitted === $expected;
    }
}
