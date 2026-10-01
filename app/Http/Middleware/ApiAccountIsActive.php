<?php

namespace App\Http\Middleware;

use App\Http\ApiErrorCode;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Account-status enforcement for API requests.
 *
 * The same rule as `EnsureAccountIsActive`, and the same reason — a deactivated
 * or suspended account must lose access immediately, not at its next login. It is
 * a separate class because the web version *redirects*, which for a JSON client is
 * an unreadable answer, and because the recovery paths differ: an API client whose
 * account was blocked needs a 403 it can branch on, not a login page.
 *
 * Applied to the `api` group rather than the `web` group, since `/api/*` requests
 * never enter the `web` group at all — which means, before this existed, a blocked
 * account kept full programmatic access for as long as its token lived.
 */
class ApiAccountIsActive
{
    /** @var list<string> Statuses that may not hold a session or a token. */
    private array $blocked = ['inactive', 'suspended', 'disabled'];

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null || ! $this->isBlocked($user)) {
            return $next($request);
        }

        return response()->json([
            'error' => [
                'code' => ApiErrorCode::Forbidden,
                'message' => 'This account is not active.',
            ],
        ], ApiErrorCode::status(ApiErrorCode::Forbidden));
    }

    private function isBlocked(object $user): bool
    {
        $status = $user->status ?? null;

        return $status !== null && in_array(strtolower((string) $status), $this->blocked, true);
    }
}
