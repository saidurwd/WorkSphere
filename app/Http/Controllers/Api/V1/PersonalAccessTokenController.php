<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreTokenRequest;
use App\Models\User;
use App\Services\ActivityLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;

/**
 * A token holder's own tokens — listing, issuing and revoking.
 *
 * There was no way to revoke a token: Sanctum can delete one, but nothing in
 * this application exposed it. A credential that cannot be withdrawn is a
 * credential with an infinite life, which is the opposite of what issuing an
 * expiring token is for.
 *
 * Every row returned is the caller's own. The `whereBelongsTo` scope is the
 * whole authorisation: a token id from another account returns 404, not somebody
 * else's token.
 *
 * `store` is the one action here that is not about the caller's own tokens: it
 * mints a NEW credential, so it is super-admin only and audited. That asymmetry is
 * deliberate — listing and revoking your own tokens is hygiene, while creating one
 * is an escalation to the authority the account already holds.
 */
class PersonalAccessTokenController extends Controller
{
    public function __construct(private readonly ActivityLogger $activity) {}

    /**
     * Mint a token for the caller.
     *
     * The plaintext is returned exactly once, in this response, and never again —
     * Sanctum stores only its hash. Everything about the grant is audited: which
     * abilities, until when, for whom.
     */
    public function store(StoreTokenRequest $request): JsonResponse
    {
        $user = $this->actor($request);

        // Checked by ROLE, not by permission. Granting this via a permission would
        // make token issuance as broad as whatever holds that permission, and it is
        // the single act that turns a login into standing programmatic access.
        Gate::forUser($user)->authorize('super-admin-only');

        $expiresAt = $request->expiresAt();
        $abilities = $request->abilities();

        $token = $user->createToken($request->string('name')->toString(), $abilities, $expiresAt);

        // The token VALUE is never recorded. The abilities and the expiry are,
        // because "a token with `*` exists" and "a read-only token exists" are very
        // different acts and an audit trail that cannot tell them apart is not one.
        $this->activity->record(
            User::class,
            $user,
            'api_token_issued',
            null,
            [
                'token_id' => $token->accessToken->id,
                'abilities' => $abilities,
                'expires_at' => $expiresAt->toIso8601String(),
            ],
            $user->id,
        );

        return response()->json([
            'data' => [
                'id' => (int) $token->accessToken->id,
                'name' => $token->accessToken->name,
                'abilities' => $abilities,
                'expires_at' => $expiresAt->toIso8601String(),
                // The only time this value is ever transmitted. It is not
                // recoverable afterwards, which is why the response says so.
                'token' => $token->plainTextToken,
            ],
        ], Response::HTTP_CREATED);
    }

    public function index(Request $request): JsonResponse
    {
        $tokens = $request->user()->tokens()
            ->latest('id')
            ->get(['id', 'name', 'abilities', 'last_used_at', 'expires_at', 'created_at']);

        return response()->json([
            'data' => $tokens->map(fn ($token): array => [
                'id' => $token->id,
                'name' => $token->name,
                'abilities' => $token->abilities,
                'last_used_at' => $token->last_used_at,
                'expires_at' => $token->expires_at,
                'created_at' => $token->created_at,
                // The token value is deliberately absent: this response is the one
                // place a leaked list would hand out working credentials, and the
                // plaintext is not recoverable from here anyway.
                'current' => $request->bearerToken() !== null
                    && $this->isCurrent($request, (string) $token->id),
            ]),
        ]);
    }

    public function destroy(Request $request, string $tokenId): JsonResponse
    {
        $token = $request->user()->tokens()->whereKey($tokenId)->first();

        if ($token === null) {
            return response()->json(['message' => 'Not found.'], 404);
        }

        $name = $token->name;

        // The token VALUE is never recorded — only which token, for whom.
        $this->activity->record(
            'PersonalAccessToken',
            null,
            'revoked',
            ['name' => $name],
            null,
            $request->user()->id,
        );

        $token->delete();

        return response()->json(['message' => Str::limit($name, 40).' revoked.']);
    }

    /**
     * The authenticated user. Every route here is behind `auth:sanctum`, so this
     * cannot be null in practice — but the type is `?User` and letting a null
     * through would surface much later as a property access on nothing.
     */
    protected function actor(Request $request): User
    {
        $user = $request->user();

        abort_if($user === null, 401);

        return $user;
    }

    /**
     * Whether this is the token making the request.
     *
     * Compares the hash of the bearer token against the row, so the current token
     * is distinguishable without ever holding the plaintext of another.
     */
    protected function isCurrent(Request $request, string $tokenId): bool
    {
        $bearer = $request->bearerToken();

        if ($bearer === null) {
            return false;
        }

        [$id, $plain] = array_pad(explode('|', $bearer, 2), 2, null);

        return $id === $tokenId
            && hash_equals(
                hash('sha256', (string) $plain),
                $request->user()->tokens()->whereKey($tokenId)->value('token'),
            );
    }
}
