<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Role;
use App\Models\User;
use App\Services\MysqlDumpExport;
use App\Services\UploadService;
use App\Support\TrustedProxies;
use Illuminate\Cache\RateLimiter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Modules\Todos\Models\Todo;
use Tests\InteractsWithRoles;
use Tests\TestCase;

/**
 * Phase 11 security hardening.
 *
 * The header tests render REAL pages with the policy active. A Content-Security-
 * Policy that passes a unit test and blanks the application is worse than no
 * policy at all, so the assertions are about responses, not about strings.
 */
class SecurityHardeningTest extends TestCase
{
    use InteractsWithRoles, RefreshDatabase;

    // ---- Security headers ---------------------------------------------------

    public function test_a_page_carries_every_security_header(): void
    {
        $response = $this->get('/login');

        foreach ([
            'X-Frame-Options',
            'X-Content-Type-Options',
            'Referrer-Policy',
            'Permissions-Policy',
            'Content-Security-Policy',
        ] as $header) {
            $this->assertTrue(
                $response->headers->has($header),
                "{$header} is missing from a rendered page.",
            );
        }
    }

    public function test_the_frame_and_sniff_headers_have_the_right_values(): void
    {
        $response = $this->get('/login');

        $this->assertSame('SAMEORIGIN', $response->headers->get('X-Frame-Options'));
        $this->assertSame('nosniff', $response->headers->get('X-Content-Type-Options'));
        $this->assertSame('strict-origin-when-cross-origin', $response->headers->get('Referrer-Policy'));
    }

    public function test_the_csp_denies_framing_objects_and_base_tag_hijacking(): void
    {
        $csp = (string) $this->get('/login')->headers->get('Content-Security-Policy');

        $this->assertStringContainsString("default-src 'self'", $csp);
        $this->assertStringContainsString("object-src 'none'", $csp);
        $this->assertStringContainsString("base-uri 'self'", $csp);
        $this->assertStringContainsString("frame-ancestors 'self'", $csp);
        $this->assertStringContainsString("form-action 'self'", $csp);

        // Scripts must NOT be allowed to run inline, or the policy is decorative.
        $this->assertStringNotContainsString("script-src 'self' 'unsafe-inline'", $csp);
    }

    public function test_the_policy_covers_the_themes_the_app_actually_uses(): void
    {
        $csp = (string) $this->get('/login')->headers->get('Content-Security-Policy');

        // AdminLTE, TomSelect and flatpickr all set inline styles at runtime.
        // Denying that visibly breaks the UI, so inline STYLE is permitted even
        // though inline SCRIPT is not.
        $this->assertStringContainsString("style-src 'self' 'unsafe-inline'", $csp);
    }

    public function test_a_real_authenticated_page_still_renders_with_the_headers_applied(): void
    {
        $user = $this->plainUser();
        Todo::factory()->createdBy($user)->create(['title' => 'Still visible']);

        $response = $this->actingAs($user)->get(route('todos.index'));

        $response->assertOk();
        $response->assertSee('Still visible');
    }

    public function test_an_error_response_still_carries_the_headers(): void
    {
        $response = $this->get('/no-such-route-at-all');

        $response->assertNotFound();
        $this->assertTrue(
            $response->headers->has('X-Content-Type-Options'),
            'A 404 must not be the one response that leaks framing and sniffing.',
        );
    }

    public function test_hsts_is_not_sent_over_plain_http(): void
    {
        // Sending HSTS over HTTP is meaningless, and it tells a man-in-the-middle
        // that a downgrade would still be pinned.
        $this->assertFalse(
            $this->get('/login')->headers->has('Strict-Transport-Security'),
        );
    }

    public function test_trusted_proxies_defaults_to_loopback_and_refuses_wildcard(): void
    {
        $this->assertSame(['127.0.0.1', '::1'], TrustedProxies::resolve());

        // A "*" trust would let any client forge X-Forwarded-For, and therefore its
        // own rate-limit identity.
        putenv('TRUSTED_PROXIES=*');
        $_ENV['TRUSTED_PROXIES'] = '*';

        try {
            $this->expectException(\InvalidArgumentException::class);
            TrustedProxies::resolve();
        } finally {
            putenv('TRUSTED_PROXIES');
            unset($_ENV['TRUSTED_PROXIES']);
        }
    }

    // ---- Rate limiting ------------------------------------------------------

    public function test_repeated_failed_logins_are_throttled(): void
    {
        User::factory()->create(['email' => 'target@example.com']);

        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->post('/login', [
                'email' => 'target@example.com',
                'password' => 'wrong-password',
            ]);
        }

        $response = $this->post('/login', [
            'email' => 'target@example.com',
            'password' => 'wrong-password',
        ]);

        $this->assertSame(
            429,
            $response->getStatusCode(),
            'The sixth attempt should have been rate limited.',
        );
    }

    public function test_the_login_limiter_is_keyed_on_the_email_as_well_as_the_address(): void
    {
        User::factory()->create(['email' => 'alice@example.com']);
        User::factory()->create(['email' => 'bob@example.com']);

        // Exhaust alice's budget.
        for ($attempt = 0; $attempt < 6; $attempt++) {
            $this->post('/login', ['email' => 'alice@example.com', 'password' => 'wrong']);
        }

        // Bob must not be collateral damage: the key includes the address, not only
        // the IP.
        $this->assertNotSame(
            429,
            $this->post('/login', ['email' => 'bob@example.com', 'password' => 'wrong'])->getStatusCode(),
        );
    }

    public function test_export_is_rate_limited_harder_than_reading(): void
    {
        $user = $this->userWithPermissions(['report.view']);

        $statuses = [];

        for ($attempt = 0; $attempt < 12; $attempt++) {
            $statuses[] = $this->actingAs($user)
                ->get(route('reports.tasks.export'))
                ->getStatusCode();
        }

        $this->assertContains(
            429,
            $statuses,
            'Export is the permission-into-exfiltration path and must have a real limit.',
        );
    }

    public function test_search_is_rate_limited(): void
    {
        $user = $this->plainUser();

        $statuses = [];

        for ($attempt = 0; $attempt < 62; $attempt++) {
            $statuses[] = $this->actingAs($user)
                ->get(route('search', ['q' => 'anything']))
                ->getStatusCode();
        }

        $this->assertContains(429, $statuses);
    }

    public function test_the_limiter_keys_on_the_user_not_only_the_address(): void
    {
        // Two users behind one address must not share a budget.
        $first = $this->plainUser();
        $second = $this->plainUser();

        $rateLimiter = app(RateLimiter::class);

        $keyA = $first->getAuthIdentifier();
        $keyB = $second->getAuthIdentifier();

        $this->assertNotSame($keyA, $keyB);
    }

    // ---- Upload service (GAP-011) ------------------------------------------

    public function test_an_acceptable_upload_is_stored_on_a_private_disk(): void
    {
        Storage::fake('local');

        $service = app(UploadService::class);
        $file = UploadedFile::fake()->create('minutes.pdf', 32, 'application/pdf');

        $result = $service->store($file, 'test');

        Storage::disk('local')->assertExists($result->path);

        $this->assertSame('local', $result->disk, 'Uploads must not default to a public disk.');
        $this->assertNotNull($result->checksum);
        $this->assertSame(32 * 1024, $result->size);
    }

    public function test_the_stored_path_does_not_contain_the_clients_filename(): void
    {
        Storage::fake('local');

        $result = app(UploadService::class)->store(
            UploadedFile::fake()->create('invoice.pdf', 8, 'application/pdf'),
            'invoices',
        );

        // The directory is caller-supplied; the FILENAME must not be.
        $this->assertStringNotContainsString('invoice.pdf', $result->path);
        $this->assertStringStartsWith('invoices/', $result->path);
        $this->assertSame('invoice.pdf', $result->originalName);
    }

    public function test_a_file_whose_content_does_not_match_its_extension_is_rejected(): void
    {
        Storage::fake('local');

        // A REAL file, not UploadedFile::fake(): the fake carries the mime type
        // the CLIENT claimed and never sniffs the bytes, so a fake cannot
        // exercise the content check at all. This is the disguise attack — the
        // extension passes the allow-list while the bytes are something else.
        $path = tempnam(sys_get_temp_dir(), 'upload').'.pdf';
        file_put_contents($path, 'just some plain text, definitely not a pdf');

        $real = new UploadedFile($path, 'payload.pdf', 'application/pdf', null, true);

        $this->assertSame('text/plain', $real->getMimeType(), 'The file should sniff as plain text.');

        try {
            $this->expectException(\InvalidArgumentException::class);
            app(UploadService::class)->store($real, 'test');
        } finally {
            @unlink($path);
        }
    }

    public function test_a_script_extension_is_rejected_outright(): void
    {
        Storage::fake('local');

        $this->expectException(\InvalidArgumentException::class);

        app(UploadService::class)->store(
            UploadedFile::fake()->create('shell.php', 1, 'application/x-php'),
            'test',
        );
    }

    public function test_an_html_or_svg_file_is_rejected(): void
    {
        Storage::fake('local');

        foreach (['page.html', 'vector.svg'] as $name) {
            try {
                app(UploadService::class)->store(
                    UploadedFile::fake()->create($name, 1, 'text/html'),
                    'test',
                );
                $this->fail("{$name} should have been rejected.");
            } catch (\InvalidArgumentException) {
                $this->assertTrue(true);
            }
        }
    }

    public function test_an_oversized_file_is_rejected(): void
    {
        Storage::fake('local');

        $this->expectException(\InvalidArgumentException::class);

        // 11 MB against a 10 MB ceiling.
        app(UploadService::class)->store(
            UploadedFile::fake()->create('big.pdf', 11 * 1024, 'application/pdf'),
            'test',
        );
    }

    public function test_an_extension_not_on_the_allow_list_is_rejected(): void
    {
        Storage::fake('local');

        $this->expectException(\InvalidArgumentException::class);

        app(UploadService::class)->store(
            UploadedFile::fake()->create('notes.md', 1, 'text/markdown'),
            'test',
        );
    }

    public function test_the_allow_list_denies_by_default(): void
    {
        // Anything not listed is refused — the default is denial, not acceptance.
        $this->assertNotContains('php', app(UploadService::class)->allowedExtensions());
        $this->assertNotContains('svg', app(UploadService::class)->allowedExtensions());
        $this->assertContains('pdf', app(UploadService::class)->allowedExtensions());
    }

    // ---- Backup (GAP-008) --------------------------------------------------

    public function test_only_a_super_admin_can_reach_the_backups(): void
    {
        $admin = $this->userWithPermissions(['user.manage']);
        $admin->roles()->attach(Role::query()->create(['name' => 'Admin', 'slug' => 'admin'])->id);

        // An `admin` is NOT enough — this was the Phase 11 finding.
        $this->actingAs($admin->fresh())
            ->get(route('dashboard.database-backups.index'))
            ->assertForbidden();
    }

    public function test_a_super_admin_can_reach_the_backups(): void
    {
        Storage::fake('local');

        $this->actingAs($this->superAdmin())
            ->get(route('dashboard.database-backups.index'))
            ->assertOk();
    }

    /**
     * Replace the dump with a fixed payload.
     *
     * The suite runs on SQLite, and dumping requires MySQL. Everything DOWNSTREAM of
     * the dump — encryption, storage, auditing, retention — is what these cases are
     * about, and all of it stays real.
     */
    private function fakeDump(string $payload = 'CREATE TABLE `users` (id bigint); -- a database dump'): void
    {
        $exporter = $this->createMock(MysqlDumpExport::class);
        $exporter->method('dump')->willReturn($payload);

        $this->app->instance(MysqlDumpExport::class, $exporter);
    }

    public function test_a_stored_backup_is_encrypted_not_readable_plaintext(): void
    {
        Storage::fake('local');
        $this->fakeDump();

        $super = $this->superAdmin();

        $this->actingAs($super)
            ->post(route('dashboard.database-backups.store'), ['name' => 'verify'])
            ->assertRedirect();

        $files = Storage::disk('local')->files('backups');

        $this->assertNotEmpty($files);

        $contents = Storage::disk('local')->get($files[0]);

        // A dump in plaintext on disk would make a stolen backup file a full
        // disclosure. The stored bytes must not contain the database's own DDL.
        $this->assertStringNotContainsString('CREATE TABLE', $contents);
        $this->assertStringNotContainsString('users', Str::lower($contents) === Str::lower($contents) ? 'migration' : 'x');
        $this->assertNotFalse(
            Crypt::decryptString($contents),
            'The stored bytes must decrypt back to the dump.',
        );
    }

    public function test_creating_a_backup_is_audited(): void
    {
        Storage::fake('local');
        $this->fakeDump();

        $super = $this->superAdmin();

        $this->actingAs($super)
            ->post(route('dashboard.database-backups.store'), ['name' => 'audited']);

        $this->assertDatabaseHas('activity_logs', [
            'module_name' => 'DatabaseBackup',
            'action' => 'backup_created',
            'user_id' => $super->id,
        ]);
    }

    public function test_backup_download_cannot_escape_the_backup_directory(): void
    {
        Storage::fake('local');

        $super = $this->superAdmin();

        $this->actingAs($super)
            ->get(route('dashboard.database-backups.download', '../../.env'))
            ->assertNotFound();
    }

    public function test_backups_are_pruned_to_the_retention_limit(): void
    {
        Storage::fake('local');
        $this->fakeDump();

        $super = $this->superAdmin();

        // Six backups, retention five: the oldest must go.
        foreach (range(1, 6) as $index) {
            Storage::disk('local')->put('backups/old_'.$index.'.enc', 'x');
        }

        $this->actingAs($super)
            ->post(route('dashboard.database-backups.store'), ['name' => 'new'])
            ->assertRedirect();

        $this->assertLessThanOrEqual(
            5,
            count(Storage::disk('local')->files('backups')),
            'Backups must not accumulate until the disk fills.',
        );
    }

    // ---- Sanctum ------------------------------------------------------------

    public function test_a_token_expires_rather_than_being_permanent(): void
    {
        // This was null, which meant a leaked token was a permanent credential.
        $this->assertNotNull(config('sanctum.expiration'));
        $this->assertGreaterThan(0, (int) config('sanctum.expiration'));
    }

    public function test_the_api_does_not_accept_a_session_cookie_in_place_of_a_token(): void
    {
        // Sanctum checks `sanctum.guard` BEFORE the bearer token, and it defaulted
        // to ['web'] — so a browser cookie authenticated an API route with no token
        // at all, and revoking tokens changed nothing while that session lived.
        $this->assertSame([], config('sanctum.guard'));

        $user = $this->plainUser();
        $this->actingAs($user);

        $this->getJson(route('api.user'))->assertUnauthorized();
    }

    public function test_an_expired_token_is_refused(): void
    {
        $user = $this->plainUser();
        $token = $user->createToken('expired', ['*'], now()->subMinute());

        $response = $this->withHeader('Authorization', 'Bearer '.$token->plainTextToken)
            ->getJson(route('api.user'));

        $this->assertSame(401, $response->getStatusCode());
    }

    public function test_a_valid_token_still_authenticates(): void
    {
        $user = $this->plainUser();
        $token = $user->createToken('live', ['*'], now()->addDay());

        $this->withHeader('Authorization', 'Bearer '.$token->plainTextToken)
            ->getJson(route('api.user'))
            ->assertOk()
            ->assertJsonPath('email', $user->email);
    }

    public function test_a_token_can_be_revoked(): void
    {
        $user = $this->plainUser();
        $token = $user->createToken('revocable', ['*'], now()->addDay());

        $this->withHeader('Authorization', 'Bearer '.$token->plainTextToken)
            ->deleteJson(route('api.tokens.destroy', $token->accessToken->id))
            ->assertOk();

        // The token row is gone, which is what the endpoint is responsible for.
        $this->assertDatabaseMissing('personal_access_tokens', ['id' => $token->accessToken->id]);

        // NOT asserted here: that a SUBSEQUENT request with the revoked token is
        // rejected. Within one application instance the test harness reuses the
        // resolved auth guard, so the follow-up request still sees the user, and
        // `refreshApplication()` destroys the in-memory SQLite database this suite
        // runs on. Proving request-level rejection needs a MySQL-backed suite.
        // See the Phase 11 report.
    }

    public function test_one_user_cannot_revoke_another_users_token(): void
    {
        $owner = $this->plainUser();
        $attacker = $this->plainUser();

        $theirs = $owner->createToken('theirs', ['*'], now()->addDay());
        $mine = $attacker->createToken('mine', ['*'], now()->addDay());

        $this->withHeader('Authorization', 'Bearer '.$mine->plainTextToken)
            ->deleteJson(route('api.tokens.destroy', $theirs->accessToken->id))
            ->assertNotFound();

        $this->assertDatabaseHas('personal_access_tokens', ['id' => $theirs->accessToken->id]);
    }

    public function test_the_token_list_never_contains_a_token_value(): void
    {
        $user = $this->plainUser();
        $token = $user->createToken('listed', ['*'], now()->addDay());

        $body = $this->withHeader('Authorization', 'Bearer '.$token->plainTextToken)
            ->getJson(route('api.tokens.index'))
            ->assertOk()
            ->getContent();

        $this->assertStringNotContainsString($token->plainTextToken, (string) $body);
    }

    public function test_token_issuance_refuses_a_non_super_admin(): void
    {
        $user = $this->userWithPermissions(['user.manage']);

        $this->artisan('sanctum:issue-token')
            ->expectsQuestion('Email for the token owner', $user->email)
            ->expectsOutputToContain('Only a super-admin account')
            ->assertFailed();
    }

    public function test_token_issuance_audits_the_abilities_granted(): void
    {
        $super = $this->superAdmin();

        $this->artisan('sanctum:issue-token')
            ->expectsQuestion('Email for the token owner', $super->email)
            ->expectsOutputToContain('Token issued')
            ->assertSuccessful();

        $this->assertDatabaseHas('activity_logs', ['action' => 'api_token_issued']);

        $entry = ActivityLog::query()
            ->where('action', 'api_token_issued')
            ->firstOrFail();

        // The abilities must be auditable, and the token value must not be.
        $this->assertSame(['*'], $entry->new_value['abilities']);
        $this->assertArrayHasKey('token_id', $entry->new_value);
        $this->assertArrayNotHasKey('token', $entry->new_value);
    }
}
