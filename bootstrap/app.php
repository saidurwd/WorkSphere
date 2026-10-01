<?php

use App\Console\WorkSphereSchedule;
use App\Http\Middleware\EnsureAccountIsActive;
use App\Http\Middleware\EnsureUserIsAdmin;
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
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'admin' => EnsureUserIsAdmin::class,
            'account.active' => EnsureAccountIsActive::class,
        ]);

        // Appended to the `web` group rather than applied per route: an account
        // disabled mid-session must lose access on its next request, not at its
        // next login. The alias above remains for the recovery routes that must
        // still be reachable by a deactivated user.
        $middleware->web(append: [
            EnsureAccountIsActive::class,
        ]);

        // GAP-041: an idle session is a credential left on an unlocked machine.
        // Every authenticated request re-touches the session, so this is the
        // natural place to age it out.
        $middleware->authenticateSessions();

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
    })->create();
