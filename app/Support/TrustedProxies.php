<?php

namespace App\Support;

use InvalidArgumentException;

/**
 * Which proxies to trust for `X-Forwarded-*` headers.
 *
 * Trusting no proxy means an HTTPS request arriving through a load balancer looks
 * like plain HTTP: `isSecure()` is false, so HSTS is skipped, secure cookies are
 * not set, and generated URLs use `http`. Trusting `*` is worse — it lets any
 * client forge `X-Forwarded-For`, and therefore its own rate-limit identity and
 * any IP allow-list.
 *
 * Defaults to one hop, which matches an app behind a single nginx. Anything else
 * is configured with `TRUSTED_PROXIES`.
 *
 * A class rather than a function in `bootstrap/app.php`, because that file
 * `return`s a chained expression: anything declared after the return is
 * unreachable, and declaring it before risks a redeclaration when the file is
 * included more than once.
 */
final class TrustedProxies
{
    /**
     * @return list<string>
     */
    public static function resolve(): array
    {
        $configured = env('TRUSTED_PROXIES');

        if ($configured === null || $configured === '') {
            return ['127.0.0.1', '::1'];
        }

        $list = is_array($configured) ? $configured : array_map('trim', explode(',', (string) $configured));

        if (in_array('*', $list, true)) {
            throw new InvalidArgumentException(
                'TRUSTED_PROXIES=* is not supported: it lets any client forge X-Forwarded-For, '
                .'and therefore its own rate-limit identity.'
            );
        }

        return array_values(array_filter($list));
    }
}
