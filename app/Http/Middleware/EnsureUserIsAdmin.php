<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsAdmin
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        $adminRoles = config('authorization.admin_roles', ['admin', 'super-admin']);

        if (! $user || ! array_intersect($adminRoles, $user->roleSlugs())) {
            abort(403, 'You do not have access to this area.');
        }

        return $next($request);
    }
}
