<?php

namespace App\Rules;

use Closure;

/**
 * Password policy — GAP-041.
 *
 * Checks composition, length and obviousness. It is a rule rather than
 * configuration because the requirements are not all expressible as a regex list,
 * and because a policy that can be weakened silently in an environment file is
 * not a policy.
 */
class PasswordPolicy
{
    /**
     * Minimum accepted length. Eight is the floor; twelve is what is recommended.
     */
    public const MIN_LENGTH = 12;

    /**
     * Strings that pass every other check and are still worthless.
     *
     * @var list<string>
     */
    private const COMMON = [
        'password', 'passw0rd', '12345678', '123456789', '1234567890',
        'qwertyui', 'letmein1', 'welcome1', 'admin123', 'iloveyou',
        'password1', 'password123', 'adminadmin', 'changeme', 'secret123',
    ];

    /**
     * @param  Closure(string): void  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value)) {
            $fail('The :attribute must be a string.');

            return;
        }

        if (mb_strlen($value) < self::MIN_LENGTH) {
            $fail('The :attribute must be at least '.self::MIN_LENGTH.' characters.');

            return;
        }

        if (! preg_match('/[a-z]/', $value)) {
            $fail('The :attribute must contain a lower-case letter.');

            return;
        }

        if (! preg_match('/[A-Z]/', $value)) {
            $fail('The :attribute must contain an upper-case letter.');

            return;
        }

        if (! preg_match('/\d/', $value)) {
            $fail('The :attribute must contain a digit.');

            return;
        }

        if (in_array(mb_strtolower($value), self::COMMON, true)) {
            $fail('The :attribute is too common.');

            return;
        }

        // A run of one repeated character passes every composition check.
        if (preg_match('/^(.)\1+$/u', $value)) {
            $fail('The :attribute must not be a single repeated character.');
        }
    }
}
