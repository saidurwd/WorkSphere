<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * `POST /api/v1/tokens`.
 *
 * Two constraints that exist because issuance is itself a privilege escalation
 * path, not because the fields are awkward:
 *
 * - **`abilities` is a whitelist, and `*` is not on it by default.** A token with
 *   `*` is the whole account. Handing that out over HTTP turns "one integration"
 *   into "anyone who finds the token is an administrator", and there is no way to
 *   narrow it later. A caller who genuinely needs full access uses the CLI
 *   command, which is deliberately harder to reach than an API call.
 * - **`expires_in_days` is capped.** `config('sanctum.expiration')` is a ceiling,
 *   not a suggestion: a client cannot ask for a token that outlives the policy by
 *   passing a larger number.
 */
class StoreTokenRequest extends FormRequest
{
    /** The longest lifetime a client may request, regardless of configuration. */
    public const MAX_DAYS = 90;

    /**
     * The ability set a client may be granted over HTTP.
     *
     * Read-only and work-item writes. Anything that administers accounts, roles or
     * the database backup is deliberately absent.
     *
     * @var list<string>
     */
    public const ABILITIES = [
        'read',
        'todos:read',
        'todos:write',
        'tasks:read',
        'meetings:read',
        'obligations:read',
    ];

    public function authorize(): bool
    {
        // Role, not permission: a permission grant could widen this to any role
        // somebody later attaches it to. Checked again in the controller through
        // the `super-admin-only` gate; returning true here only stops the request
        // duplicating the check.
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'abilities' => ['sometimes', 'array', 'min:1'],
            'abilities.*' => ['required', 'string', Rule::in(self::ABILITIES)],
            'expires_in_days' => ['sometimes', 'integer', 'min:1', 'max:'.self::MAX_DAYS],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'abilities.*.in' => 'That ability cannot be granted over the API. Grantable: '.implode(', ', self::ABILITIES).'.',
        ];
    }

    /**
     * @return list<string>
     */
    public function abilities(): array
    {
        return $this->validated('abilities') ?: ['read'];
    }

    /**
     * The requested lifetime in MINUTES, clamped to the configured ceiling.
     *
     * Sanctum's `expiration` config is in minutes; the request speaks in days
     * because that is what a human reads. Converting here rather than at each call
     * site keeps the unit mistake from happening twice.
     */
    public function expiresAt(): ?Carbon
    {
        $configured = (int) config('sanctum.expiration');

        if ($configured <= 0) {
            // No configured ceiling means the operator has deliberately disabled
            // expiry. Refuse rather than issue an eternal credential over HTTP.
            throw ValidationException::withMessages([
                'expires_in_days' => 'Token expiry is not configured on this deployment.',
            ]);
        }

        $requestedDays = (int) $this->input('expires_in_days', 0);

        $minutes = $requestedDays > 0
            ? min($requestedDays * 1440, $configured)
            : $configured;

        return now()->addMinutes($minutes);
    }
}
