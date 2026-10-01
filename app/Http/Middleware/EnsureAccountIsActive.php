<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Enforces account status on every authenticated request — GAP-041.
 *
 * `users.status` exists and Phase 2 added it to `$fillable`, but nothing read it:
 * a deactivated or suspended account could sign in and keep working until its
 * session expired. This checks on every request, so a user disabled mid-session
 * loses access immediately rather than at their next login.
 *
 * Deliberately NOT applied to the routes that let a user recover their own
 * account (login, password reset, profile) — locking somebody out of the very
 * pages that would tell them why is the opposite of the intent.
 */
class EnsureAccountIsActive
{
    /** @var list<string> Statuses that may not sign in or hold a session. */
    private array $blocked = ['inactive', 'suspended', 'disabled'];

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null) {
            return $next($request);
        }

        if (! $this->isBlocked($user)) {
            return $next($request);
        }

        // Only sign out when the session is already authenticated; otherwise a
        // guest hitting a guarded page would be bounced through a logout.
        if (Auth::check()) {
            Auth::logout();

            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        return redirect()
            ->route('login')
            ->withErrors(['email' => 'This account is not active. Please contact an administrator.']);
    }

    private function isBlocked(object $user): bool
    {
        $status = $user->status ?? null;

        return $status !== null && in_array(strtolower((string) $status), $this->blocked, true);
    }
}
