<?php

namespace App\Http;

/**
 * The API's error vocabulary — TODO-MODULE-SPECIFICATION.md §9.2.
 *
 * An HTTP status alone is not a contract: a client cannot branch on 422 without
 * knowing whether the body is a validation failure, a state-machine rejection or
 * a serialisation problem. Every error this API returns carries a stable `code`
 * string, and these are the only values a client should ever match on. The
 * status code remains the coarse signal; `code` is the precise one.
 *
 * Deliberately absent: any string taken from an exception message. Messages are
 * for humans and change; codes are for machines and do not.
 */
final class ApiErrorCode
{
    /** Input failed validation, or the state machine refused the transition. */
    public const ValidationFailed = 'validation_failed';

    /** No such record — or none the caller may see, which is indistinguishable by design. */
    public const NotFound = 'not_found';

    /** The path exists but not for this verb. */
    public const MethodNotAllowed = 'method_not_allowed';

    /** Authenticated, but not permitted. */
    public const Forbidden = 'forbidden';

    /** No credential, or a credential that has expired or been revoked. */
    public const Unauthenticated = 'unauthenticated';

    /** The rate limiter rejected the request. */
    public const RateLimited = 'rate_limited';

    /** Anything unexpected. The message is generic on purpose — see ApiExceptionRenderer. */
    public const ServerError = 'server_error';

    /**
     * The HTTP status paired with each code.
     *
     * @var array<string, int>
     */
    private const STATUS = [
        self::ValidationFailed => 422,
        self::NotFound => 404,
        self::MethodNotAllowed => 405,
        self::Forbidden => 403,
        self::Unauthenticated => 401,
        self::RateLimited => 429,
        self::ServerError => 500,
    ];

    public static function status(string $code): int
    {
        return self::STATUS[$code] ?? 500;
    }

    /**
     * The full code list, for the OpenAPI document and for the test that asserts
     * the two never drift.
     *
     * @return list<string>
     */
    public static function all(): array
    {
        return array_keys(self::STATUS);
    }

    /**
     * The non-500 message shown when nothing more specific applies.
     *
     * A generic 500 body is not a usability problem worth solving with a leak:
     * the client gets a correlation-free failure and the operator gets the
     * exception in the log.
     */
    public static function defaultMessage(string $code): string
    {
        return match ($code) {
            self::ValidationFailed => 'The request was rejected by validation.',
            self::NotFound => 'Not found.',
            self::MethodNotAllowed => 'That verb is not supported on this endpoint.',
            self::Forbidden => 'This action is unauthorized.',
            self::Unauthenticated => 'Unauthenticated.',
            self::RateLimited => 'Too many requests.',
            default => 'Something went wrong.',
        };
    }
}
