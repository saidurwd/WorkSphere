<?php

namespace App\Http\Controllers\Admin\System;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Laravel\Sanctum\PersonalAccessToken;

/**
 * API token management, reachable from a browser.
 *
 * The tokens themselves are created over the API, super-admin only, audited, and
 * returned exactly once. This screen does NOT create them. That asymmetry is
 * deliberate and worth stating: reading your own tokens and revoking them is
 * hygiene, and any account can do it; minting a new standing credential is an
 * escalation and stays on the audited path where the abilities and the expiry are
 * both constrained.
 *
 * Because the values are never stored, this screen can show metadata and nothing
 * more. A "token" column here would be a lie.
 */
class TokenController extends Controller
{
    public function index(): View
    {
        $this->authorize('system.tokens');

        return view('admin.system.tokens', [
            'tokens' => PersonalAccessToken::query()
                ->where('tokenable_type', \App\Models\User::class)
                ->latest('last_used_at')
                ->latest('id')
                ->paginate(30),
            'expiryMinutes' => (int) config('sanctum.expiration'),
            'grantedAbilities' => \App\Http\Requests\Api\StoreTokenRequest::ABILITIES,
        ]);
    }

    /**
     * Revoke a token.
     *
     * Scoped by tokenable so an administrator cannot revoke another account's
     * token by guessing an id — the screen shows an id, and an id is guessable.
     */
    public function destroy(Request $request, string $tokenId): RedirectResponse
    {
        $this->authorize('system.tokens');

        $deleted = PersonalAccessToken::query()
            ->whereKey($tokenId)
            ->where('tokenable_type', \App\Models\User::class)
            ->where('tokenable_id', $request->user()->id)
            ->delete();

        if ($deleted === 0) {
            return back()->with('error', 'That token was not found on your account.');
        }

        return back()->with('success', 'Token revoked.');
    }

    /**
     * Revoke every token except the one making the request.
     *
     * Excluding the current token is not a convenience: revoking it would end the
     * session the operator is standing in, and a browser session authenticated by
     * a token cannot renew itself.
     */
    public function destroyOthers(Request $request): RedirectResponse
    {
        $this->authorize('system.tokens');

        $current = $request->user()->currentAccessToken()?->getKey();

        $deleted = PersonalAccessToken::query()
            ->where('tokenable_type', \App\Models\User::class)
            ->where('tokenable_id', $request->user()->id)
            ->when($current !== null, fn ($query) => $query->whereKeyNot($current))
            ->delete();

        return back()->with('success', "{$deleted} ".($deleted === 1 ? 'token' : 'tokens').' revoked.');
    }
}
