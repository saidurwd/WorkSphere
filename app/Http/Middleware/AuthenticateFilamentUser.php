<?php

namespace App\Http\Middleware;

use Closure;
use Filament\Http\Middleware\Authenticate;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Filament's authentication, preceded by the application's account-status check.
 *
 * Filament's own check would answer a deactivated account with a bare 403 while the
 * session stays signed in. Running {@see EnsureAccountIsActive} first signs that
 * session out and sends the person to sign-in with the reason, as every other page
 * of the application does.
 */
class AuthenticateFilamentUser extends Authenticate
{
    public function handle($request, Closure $next, ...$guards): Response
    {
        return app(EnsureAccountIsActive::class)->handle(
            $request,
            fn (Request $request): Response => parent::handle($request, $next, ...$guards),
        );
    }
}
