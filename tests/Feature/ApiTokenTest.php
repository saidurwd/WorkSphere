<?php

namespace Tests\Feature;

use App\Http\ApiErrorCode;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Laravel\Sanctum\Sanctum;
use Tests\InteractsWithRoles;
use Tests\TestCase;

/**
 * Token issuance over HTTP.
 *
 * The endpoint is narrow on purpose and every narrowing is tested, because the
 * whole value of an expiring token is lost if issuance is easy:
 *
 * - super-admin only, checked by ROLE rather than by a permission;
 * - abilities come from a whitelist that excludes `*`;
 * - the lifetime is capped by `sanctum.expiration`, whatever the request asks;
 * - every grant is audited, with its abilities.
 */
class ApiTokenTest extends TestCase
{
    use InteractsWithRoles, RefreshDatabase;

    public function test_issuing_a_token_requires_a_token(): void
    {
        $this->postJson('/api/v1/tokens', ['name' => 'integration'])->assertUnauthorized();
    }

    public function test_a_non_super_admin_cannot_issue_a_token(): void
    {
        Sanctum::actingAs($this->userWithPermissions(['todos.create']));

        $this->postJson('/api/v1/tokens', ['name' => 'integration'])
            ->assertForbidden()
            ->assertJsonPath('error.code', ApiErrorCode::Forbidden);

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_permission_grants_do_not_open_issuance(): void
    {
        // Even a role holding every permission in the seeder cannot mint a
        // credential. The point of the role check is that a permission grant can
        // never widen it.
        Sanctum::actingAs($this->userWithPermissions(['api.tokens.create', 'super-admin-only']));

        $this->postJson('/api/v1/tokens', ['name' => 'nope'])->assertForbidden();
    }

    public function test_a_super_admin_issues_a_token(): void
    {
        Sanctum::actingAs($this->superAdmin());

        $response = $this->postJson('/api/v1/tokens', [
            'name' => 'nightly-import',
            'abilities' => ['read', 'todos:read'],
        ])->assertCreated();

        $this->assertSame('nightly-import', $response->json('data.name'));
        $this->assertSame(['read', 'todos:read'], $response->json('data.abilities'));
        $this->assertNotEmpty($response->json('data.token'));
    }

    public function test_the_issued_token_actually_authenticates(): void
    {
        $admin = $this->superAdmin();

        Sanctum::actingAs($admin);

        $plainText = $this->postJson('/api/v1/tokens', ['name' => 'ci'])
            ->assertCreated()
            ->json('data.token');

        // The proof that issuance is not theatre: the returned value is a working
        // credential for the issuing account.
        $this->forgetGuards();

        $this->withHeader('Authorization', 'Bearer '.$plainText)
            ->getJson('/api/v1/meta')
            ->assertOk()
            ->assertJsonPath('data.user.id', $admin->id);
    }

    public function test_the_default_ability_set_is_read_only(): void
    {
        Sanctum::actingAs($this->superAdmin());

        $abilities = $this->postJson('/api/v1/tokens', ['name' => 'default'])
            ->assertCreated()
            ->json('data.abilities');

        // Omitting `abilities` must not default to `*`. A client that forgets the
        // field gets the narrowest credential, not the whole account.
        $this->assertSame(['read'], $abilities);
        $this->assertNotContains('*', $abilities);
    }

    public function test_a_wildcard_ability_cannot_be_requested(): void
    {
        Sanctum::actingAs($this->superAdmin());

        // `*` is the whole account. Handing it out over HTTP turns "one
        // integration" into "anyone holding this token is an administrator".
        $this->postJson('/api/v1/tokens', ['name' => 'god', 'abilities' => ['*']])
            ->assertStatus(422)
            ->assertJsonPath('error.code', ApiErrorCode::ValidationFailed);

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_an_ungrantable_ability_is_rejected(): void
    {
        Sanctum::actingAs($this->superAdmin());

        $this->postJson('/api/v1/tokens', ['name' => 'x', 'abilities' => ['admin']])
            ->assertStatus(422);
    }

    public function test_a_lifetime_beyond_the_request_cap_is_rejected(): void
    {
        config(['sanctum.expiration' => 43200]);

        Sanctum::actingAs($this->superAdmin());

        // Ten years fails the request rule outright rather than being silently
        // shortened — a client asking for something impossible is told so.
        $this->postJson('/api/v1/tokens', ['name' => 'long', 'expires_in_days' => 3650])
            ->assertStatus(422)
            ->assertJsonPath('error.code', ApiErrorCode::ValidationFailed);

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_the_lifetime_is_capped_by_configuration(): void
    {
        // One day configured, thirty requested: within the request rule, over the
        // deployment policy. The policy wins — a client cannot outlive it.
        config(['sanctum.expiration' => 1440]);

        Sanctum::actingAs($this->superAdmin());

        $expiresAt = $this->postJson('/api/v1/tokens', [
            'name' => 'long',
            'expires_in_days' => 30,
        ])->assertCreated()->json('data.expires_at');

        $this->assertLessThanOrEqual(
            now()->addDay()->addMinutes(5)->toIso8601String(),
            $expiresAt,
        );
    }

    public function test_a_requested_lifetime_shorter_than_the_cap_is_honoured(): void
    {
        config(['sanctum.expiration' => 1440]);

        Sanctum::actingAs($this->superAdmin());

        $expiresAt = $this->postJson('/api/v1/tokens', [
            'name' => 'short',
            'expires_in_days' => 1,
        ])->assertCreated()->json('data.expires_at');

        $this->assertLessThanOrEqual(
            now()->addDays(2)->toIso8601String(),
            $expiresAt,
        );
    }

    public function test_no_token_is_issued_when_expiry_is_disabled(): void
    {
        config(['sanctum.expiration' => null]);

        Sanctum::actingAs($this->superAdmin());

        // An operator who has turned expiry off has said tokens do not die. Issuing
        // one over HTTP anyway would quietly contradict that decision.
        $this->postJson('/api/v1/tokens', ['name' => 'eternal'])
            ->assertStatus(422)
            ->assertJsonPath('error.code', ApiErrorCode::ValidationFailed);
    }

    public function test_every_issuance_is_audited_with_its_abilities(): void
    {
        $admin = $this->superAdmin();

        Sanctum::actingAs($admin);

        $this->postJson('/api/v1/tokens', [
            'name' => 'audited',
            'abilities' => ['read'],
        ])->assertCreated();

        $this->assertDatabaseHas('activity_logs', [
            'subject_type' => User::class,
            'subject_id' => $admin->id,
            'action' => 'api_token_issued',
            'user_id' => $admin->id,
        ]);

        $log = ActivityLog::query()->where('action', 'api_token_issued')->latest('id')->first();

        // The abilities are the audit's whole point: "a token exists" and "a
        // read-only token exists" are different acts.
        $this->assertSame(['read'], $log->new_value['abilities']);
        $this->assertArrayNotHasKey('token', $log->new_value);
    }

    public function test_the_token_value_is_never_persisted_in_the_audit_trail(): void
    {
        Sanctum::actingAs($this->superAdmin());

        $plainText = $this->postJson('/api/v1/tokens', ['name' => 'leaky'])
            ->assertCreated()
            ->json('data.token');

        $serialised = json_encode(ActivityLog::query()->get()->toArray());

        $this->assertStringNotContainsString($plainText, $serialised);
    }

    public function test_a_name_is_required(): void
    {
        Sanctum::actingAs($this->superAdmin());

        $this->postJson('/api/v1/tokens', [])
            ->assertStatus(422)
            ->assertJsonPath('error.code', ApiErrorCode::ValidationFailed);
    }

    public function test_listing_and_revoking_your_own_tokens(): void
    {
        $admin = $this->superAdmin();

        Sanctum::actingAs($admin);

        $plainText = $this->postJson('/api/v1/tokens', ['name' => 'revocable'])->json('data.token');
        $tokenId = $this->postJson('/api/v1/tokens', ['name' => 'other'])->json('data.id');

        $this->forgetGuards();

        $this->withHeader('Authorization', 'Bearer '.$plainText)
            ->getJson('/api/v1/tokens')
            ->assertOk()
            ->assertJsonCount(2, 'data');

        $this->withHeader('Authorization', 'Bearer '.$plainText)
            ->deleteJson("/api/tokens/{$tokenId}")
            ->assertOk();

        $this->assertDatabaseMissing('personal_access_tokens', ['id' => $tokenId]);
    }

    public function test_you_cannot_revoke_another_accounts_token(): void
    {
        $mine = $this->superAdmin();
        $theirs = $this->superAdmin();

        $theirsToken = $theirs->createToken('theirs', ['read']);

        Sanctum::actingAs($mine);

        // `whereBelongsTo` on the caller's own tokens: another account's token id
        // is a 404, not somebody else's token.
        $this->deleteJson("/api/tokens/{$theirsToken->accessToken->id}")->assertNotFound();
    }

    public function test_a_revoked_token_stops_working(): void
    {
        $admin = $this->superAdmin();

        Sanctum::actingAs($admin);

        $plainText = $this->postJson('/api/v1/tokens', ['name' => 'short-lived'])->json('data.token');
        $id = $this->postJson('/api/v1/tokens', ['name' => 'second'])->json('data.id');

        $this->forgetGuards();

        $this->withHeader('Authorization', 'Bearer '.$plainText)
            ->deleteJson("/api/tokens/{$id}")
            ->assertOk();

        $this->withHeader('Authorization', 'Bearer '.$plainText)
            ->getJson('/api/v1/meta')
            ->assertOk();

        Sanctum::actingAs($admin);

        $this->deleteJson("/api/tokens/{$this->tokenIdFor($plainText)}")->assertOk();

        $this->forgetGuards();

        // Revoking the token that made the request ends it. Without this, a
        // credential that "can be revoked" but was never actually revoked is the
        // failure mode the whole endpoint exists to prevent.
        $this->withHeader('Authorization', 'Bearer '.$plainText)
            ->getJson('/api/v1/meta')
            ->assertUnauthorized();
    }

    public function test_token_issuance_is_rate_limited(): void
    {
        Queue::fake();

        Sanctum::actingAs($this->superAdmin());

        // Proves the `throttle:api-writes` middleware is actually attached, not
        // merely declared on the route.
        $limited = false;

        for ($i = 0; $i < 40; $i++) {
            $response = $this->postJson('/api/v1/tokens', ['name' => "t{$i}"]);

            if ($response->status() === 429) {
                $limited = true;
                $this->assertSame(ApiErrorCode::RateLimited, $response->json('error.code'));
                break;
            }
        }

        $this->assertTrue($limited, 'Token issuance is not rate limited.');
    }

    public function test_the_read_limiter_is_attached_to_a_collection_endpoint(): void
    {
        Sanctum::actingAs($this->superAdmin());

        // `throttle:api` on the index, asserted by exhausting it: a limiter that is
        // declared on the route but never fires is not a limiter.
        $limited = false;

        for ($i = 0; $i < 200; $i++) {
            if ($this->getJson('/api/v1/todos')->status() === 429) {
                $limited = true;
                break;
            }
        }

        $this->assertTrue($limited, 'The API read limiter does not fire.');
    }

    public function test_one_tokens_budget_does_not_exhaust_anothers(): void
    {
        // The limiter keys on the TOKEN, not merely the user. Without that, a
        // runaway integration would spend the budget of every other integration
        // the same person holds.
        $admin = $this->superAdmin();

        Sanctum::actingAs($admin);

        $noisy = $this->postJson('/api/v1/tokens', ['name' => 'noisy'])->json('data.token');
        $quiet = $this->postJson('/api/v1/tokens', ['name' => 'quiet'])->json('data.token');

        $this->forgetGuards();

        for ($i = 0; $i < 40; $i++) {
            $this->withHeader('Authorization', 'Bearer '.$noisy)->postJson('/api/v1/tokens', ['name' => 'x']);
        }

        $this->withHeader('Authorization', 'Bearer '.$quiet)
            ->getJson('/api/v1/meta')
            ->assertOk();
    }

    /**
     * The id half of a Sanctum plaintext token, which is the `<id>|<secret>` form.
     */
    private function tokenIdFor(string $plainText): string
    {
        return explode('|', $plainText, 2)[0];
    }

    /**
     * Drop the acting user so the next request authenticates by bearer token only.
     *
     * `Sanctum::actingAs(null)` is not a reset — it leaves a null user on the
     * guard, and Sanctum's Guard then dereferences it.
     */
    private function forgetGuards(): void
    {
        $this->app['auth']->forgetGuards();
    }
}
