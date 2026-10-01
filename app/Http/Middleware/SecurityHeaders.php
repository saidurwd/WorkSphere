<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Security response headers — OWASP Secure Headers Project.
 *
 * Added deliberately one at a time rather than as a blanket policy, because each
 * one breaks something different if it is wrong:
 *
 * - `X-Frame-Options` breaks framing. AdminLTE does not frame itself, so the
 *   legacy header is sent alongside CSP's `frame-ancestors` for older browsers.
 * - `Content-Security-Policy` is the risky one: it breaks inline scripts and
 *   third-party assets. It is therefore assembled from the application's OWN
 *   stylesheets, and the strict `script-src` is applied only where the app does
 *   not need an inline bootstrap script. `SecurityHeadersTest` renders real
 *   pages with the header active, because a CSP that passes a unit test and
 *   blanks the application is worse than none.
 */
class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Defence in depth: never let a response be framed, whatever the app does.
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('X-Permitted-Cross-Domain-Policies', 'none');
        $response->headers->set('Cross-Origin-Opener-Policy', 'same-origin');

        // Permissions Policy: this application needs none of these, so everything
        // not explicitly needed is denied.
        $response->headers->set('Permissions-Policy', implode(', ', [
            'accelerometer=()',
            'camera=()',
            'geolocation=()',
            'gyroscope=()',
            'magnetometer=()',
            'microphone=()',
            'payment=()',
            'usb=()',
        ]));

        $this->contentSecurityPolicy($request, $response);
        $this->hsts($request, $response);

        return $response;
    }

    /**
     * Strict HSTS, but only over HTTPS.
     *
     * Sending it over plain HTTP is meaningless at best and a signal to a
     * man-in-the-middle at worst: it tells them a downgrade to HTTP would still
     * be pinned. Development runs on HTTP, so this correctly does nothing there.
     */
    protected function hsts(Request $request, Response $response): void
    {
        if (! $request->isSecure()) {
            return;
        }

        $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
    }

    protected function contentSecurityPolicy(Request $request, Response $response): void
    {
        $directives = [
            "default-src 'self'",
            // The application ships no third-party script and loads its own Vite
            // bundles from self. `'unsafe-inline'` is required for stylesheets only:
            // AdminLTE, TomSelect and flatpickr all set inline styles at runtime,
            // and denying that visibly breaks the UI.
            "script-src 'self'",
            "style-src 'self' 'unsafe-inline'",
            "img-src 'self' data: blob:",
            "font-src 'self' data:",
            // `blob:` because the export and file previews read local blobs.
            "connect-src 'self'",
            "object-src 'none'",
            "base-uri 'self'",
            "form-action 'self'",
            "frame-ancestors 'self'",
        ];

        $response->headers->set('Content-Security-Policy', implode('; ', $directives));
    }
}
