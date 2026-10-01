<?php

use App\Console\WorkSphereSchedule;
use App\Exceptions\ApiExceptionRenderer;
use App\Http\Middleware\ApiAccountIsActive;
use App\Http\Middleware\EnsureAccountIsActive;
use App\Http\Middleware\EnsureUserIsAdmin;
use App\Http\Middleware\SecurityHeaders;
use App\Providers\RateLimitServiceProvider;
use App\Support\TrustedProxies;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withProviders([
        RateLimitServiceProvider::class,
    ])
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'admin' => EnsureUserIsAdmin::class,
            'account.active' => EnsureAccountIsActive::class,
        ]);

        // Security headers on EVERY response, including errors, redirects and the
        // 404 produced by an unmatched route — those never enter the `web` group,
        // and a response that leaks framing or content sniffing should not be
        // exempt simply because it is an error page.
        $middleware->append(SecurityHeaders::class);

        // Appended to the `web` group rather than applied per route: an account
        // disabled mid-session must lose access on its next request, not at its
        // next login. The alias above remains for the recovery routes that must
        // still be reachable by a deactivated user.
        $middleware->web(append: [
            EnsureAccountIsActive::class,
        ]);

        // The same rule applies to the API, and more sharply: a bearer token
        // outlives any account-status change, so an account disabled today would
        // otherwise keep full programmatic access until its token expired. The
        // middleware redirects, which is right for a browser and wrong for JSON,
        // so the API group gets its own JSON-aware version rather than this one.
        $middleware->api(prepend: [
            ApiAccountIsActive::class,
        ]);

        // GAP-041: an idle session is a credential left on an unlocked machine.
        // Every authenticated request re-touches the session, so this is the
        // natural place to age it out.
        $middleware->authenticateSessions();

        // Phase 11 item 8. Trusting no proxies by default means an HTTPS request
        // arriving through a load balancer looks like plain HTTP, so
        // `isSecure()` is false: HSTS is skipped, secure cookies are not set, and
        // generated URLs use http. In this deployment the app sits behind nginx on
        // the same host, so one hop is trusted. Override with TRUSTED_PROXIES for
        // anything else — "*" is never correct and is refused below.
        $middleware->trustProxies(
            at: TrustedProxies::resolve(),
            headers: Request::HEADER_X_FORWARDED_FOR
                | Request::HEADER_X_FORWARDED_HOST
                | Request::HEADER_X_FORWARDED_PORT
                | Request::HEADER_X_FORWARDED_PROTO,
        );

        $middleware->redirectGuestsTo(fn () => route('login'));
    })
    ->withSchedule(function (Schedule $schedule) {
        // Defined in a class rather than inline so it can be asserted without the
        // container — see App\Console\WorkSphereSchedule for why.
        (new WorkSphereSchedule($schedule))->register();
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );

        // One renderer for every API failure, so a client can rely on the shape
        // instead of handling four different error bodies. It returns null for
        // anything that is not an `api/*` route, so the web layer keeps its HTML
        // redirects and flash messages unchanged.
        //
        // The argument order is `($e, $request)` and the first parameter is typed
        // `Throwable`: Laravel dispatches a render callback by matching the FIRST
        // parameter's type against the exception, so `(Request, Throwable)` would
        // silently never fire.
        $exceptions->render(function (Throwable $e, Request $request) {
            return (new ApiExceptionRenderer)->render($request, $e);
        });
    })
    ->create();
