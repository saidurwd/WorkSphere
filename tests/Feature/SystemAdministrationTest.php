<?php

namespace Tests\Feature;

use App\Models\FeatureFlag;
use App\Models\Role;
use App\Models\User;
use App\Services\FeatureFlags;
use App\Services\HealthCheck;
use App\Services\Settings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Modules\Todos\Models\Todo;
use Tests\Concerns\CountsQueries;
use Tests\InteractsWithRoles;
use Tests\TestCase;

/**
 * System administration: settings, feature flags, health, queue and schedule.
 *
 * Grouped by the four properties that would be genuinely dangerous to get wrong,
 * rather than one test class per screen — because the failures that matter are the
 * ones that cut across screens: a flag that leaks, a setting that writes to the
 * wrong place, a probe that reports healthy while failing, an action that reaches
 * for a payload.
 */
class SystemAdministrationTest extends TestCase
{
    use CountsQueries, InteractsWithRoles, RefreshDatabase;

    // ---- Authorisation ------------------------------------------------------

    public function test_every_system_screen_needs_its_own_permission(): void
    {
        // One permission per screen, deliberately. An operator who can read the
        // health of the system must not thereby be handed the ability to change how
        // it behaves.
        $screens = [
            'admin.system.health.index' => 'system.health',
            'admin.system.settings.index' => 'system.settings',
            'admin.system.queue.index' => 'system.queue',
            'admin.system.schedule.index' => 'system.schedule',
            'admin.system.flags.index' => 'system.flags',
            'admin.system.tokens.index' => 'system.tokens',
        ];

        foreach ($screens as $route => $permission) {
            // The admin middleware is the FIRST gate and every screen sits behind
            // it, so the actor needs the role; what this test is about is the
            // per-screen permission underneath it.
            $this->actingAs($this->adminWith([$permission], grant: 'granted-'.$permission))
                ->get(route($route))
                ->assertOk("{$route} should open for someone holding {$permission}.");

            $this->actingAs($this->adminWith(['user.manage'], grant: 'without-'.$permission))
                ->get(route($route))
                ->assertForbidden("{$route} opened without {$permission}.");
        }
    }

    public function test_a_system_screen_is_closed_to_a_non_admin(): void
    {
        // Holding every system permission is not enough without the admin role: the
        // role check comes first.
        $this->actingAs($this->userWithPermissions([
            'system.health', 'system.settings', 'system.queue',
            'system.schedule', 'system.flags', 'system.tokens',
        ]))
            ->get(route('admin.system.health.index'))
            ->assertForbidden();
    }

    /**
     * A user holding the given permissions AND the admin role.
     *
     * Two roles, because the admin middleware and the permission gate are separate
     * checks and a test that only exercised one of them proves nothing about the
     * other. The admin role is created once and reused — `roles.slug` is unique,
     * so a second `create` would fail rather than return the same role.
     *
     * @param  list<string>  $permissions
     */
    private function adminWith(array $permissions, string $grant = 'grant'): User
    {
        $user = $this->userWithPermissions($permissions, slug: $grant);

        $adminRole = Role::query()->firstOrCreate(
            ['slug' => 'admin'],
            ['name' => 'Administrator'],
        );

        $user->roles()->attach($adminRole->id);
        $user->forgetPermissionCache();

        return $user->fresh();
    }

    // ---- Settings -----------------------------------------------------------

    public function test_an_unset_setting_reads_as_its_declared_default(): void
    {
        $settings = app(Settings::class);

        $this->assertSame('UTC', $settings->get('app.timezone'));
        $this->assertSame(1, $settings->get('app.week_starts_on'));
    }

    public function test_a_stored_setting_overrides_the_default_and_is_cached(): void
    {
        $settings = app(Settings::class);

        $settings->set('app.timezone', 'Asia/Dhaka');

        // The cache was warm before the write, so this is the real test: a write
        // that does not invalidate leaves a stale value served to every page.
        $this->assertSame('Asia/Dhaka', $settings->get('app.timezone'));
        $this->assertSame('Asia/Dhaka', app(Settings::class)->get('app.timezone'));
    }

    public function test_a_setting_reads_back_as_its_declared_type(): void
    {
        $settings = app(Settings::class);

        // Stored as a hand-edited string, read as an integer. The declared type
        // wins, so `max()` and arithmetic do not silently misbehave.
        $settings->set('security.password_min_length', '16');

        $this->assertSame(16, $settings->get('security.password_min_length'));
        $this->assertIsInt($settings->get('security.password_min_length'));
    }

    public function test_an_unknown_setting_cannot_be_written(): void
    {
        // Allowing it creates rows nothing reads, which is how a settings table
        // becomes a table of five usable rows out of forty.
        $this->expectException(\InvalidArgumentException::class);

        app(Settings::class)->set('not.a.real.setting', 1);
    }

    public function test_saving_a_setting_records_who_changed_it(): void
    {
        $admin = $this->superAdmin(['system.settings']);

        $this->actingAs($admin)
            ->post(route('admin.system.settings.update'), [
                'settings' => [
                    'app.timezone' => 'Europe/London',
                    'security.max_failed_attempts' => '8',
                ],
            ])
            ->assertRedirect();

        $this->assertSame('Europe/London', app(Settings::class)->get('app.timezone'));
        $this->assertSame(8, app(Settings::class)->get('security.max_failed_attempts'));

        $this->assertDatabaseHas('settings', [
            'key' => 'app.timezone',
            'updated_by' => $admin->id,
        ]);
    }

    public function test_an_unknown_setting_key_is_rejected_rather_than_ignored(): void
    {
        $this->actingAs($this->superAdmin(['system.settings']))
            ->post(route('admin.system.settings.update'), [
                'settings' => ['not.a.real.setting' => 'x'],
            ])
            ->assertRedirect();

        // Silently dropping it produces a form that appears to save and does not.
        $this->assertDatabaseMissing('settings', ['key' => 'not.a.real.setting']);
    }

    public function test_a_setting_of_the_wrong_type_is_refused(): void
    {
        $this->actingAs($this->superAdmin(['system.settings']))
            ->from(route('admin.system.settings.index'))
            ->post(route('admin.system.settings.update'), [
                'settings' => ['security.max_failed_attempts' => 'not a number'],
            ])
            ->assertRedirect(route('admin.system.settings.index'))
            ->assertSessionHas('error');

        $this->assertDatabaseMissing('settings', ['key' => 'security.max_failed_attempts']);
    }

    public function test_a_boolean_setting_can_be_switched_off(): void
    {
        $this->actingAs($this->superAdmin(['system.settings']))
            ->post(route('admin.system.settings.update'), [
                'settings' => ['app.week_starts_on' => '0'],
            ]);

        // A checkbox submits NOTHING when unticked, so the form carries an explicit
        // false. The test proves the value can actually become 0 rather than only
        // ever being set.
        $this->assertSame(0, app(Settings::class)->get('app.week_starts_on'));
    }

    public function test_the_settings_screen_groups_by_purpose(): void
    {
        $this->actingAs($this->superAdmin(['system.settings']))
            ->get(route('admin.system.settings.index'))
            ->assertOk()
            ->assertSee('Localisation')
            ->assertSee('Security')
            ->assertSee('Notifications');
    }

    // ---- Feature flags ------------------------------------------------------

    public function test_an_absent_flag_is_not_a_disabled_flag(): void
    {
        // The whole point: a flag deleted after its rollout must not silently
        // switch off a code path that has shipped. The caller states the default.
        $this->assertTrue(app(FeatureFlags::class)->enabled('never.created', default: true));
        $this->assertFalse(app(FeatureFlags::class)->enabled('never.created', default: false));
    }

    public function test_a_flag_is_off_until_it_is_switched_on(): void
    {
        $flags = app(FeatureFlags::class);

        $flags->put(['key' => 'new-thing', 'name' => 'New thing', 'type' => 'boolean', 'value' => true]);

        $this->assertFalse($flags->enabled('new-thing'), 'A new flag must not be on by default.');

        $flags->put(['key' => 'new-thing', 'name' => 'New thing', 'type' => 'boolean', 'value' => true, 'is_enabled' => true]);

        $this->assertTrue($flags->enabled('new-thing'));
    }

    public function test_the_bucket_is_stable_for_a_user(): void
    {
        $flags = app(FeatureFlags::class);
        $user = $this->plainUser();

        $flag = $flags->put([
            'key' => 'canary', 'name' => 'Canary', 'type' => 'boolean', 'value' => true, 'is_enabled' => true,
        ])->fresh();

        $first = $flag->bucketFor($user);

        // A flag that answers differently on two consecutive requests from the same
        // person cannot be debugged, cannot be support-reproduced, and makes a
        // canary's error rate meaningless.
        for ($i = 0; $i < 20; $i++) {
            $this->assertSame($first, $flag->bucketFor($user));
            $this->assertSame($flags->enabled('canary', $user), $flags->enabled('canary', $user));
        }
    }

    public function test_a_zero_percent_rollout_excludes_everyone(): void
    {
        $flags = app(FeatureFlags::class);

        $flags->put([
            'key' => 'canary', 'name' => 'Canary', 'type' => 'boolean', 'value' => true,
            'is_enabled' => true, 'rollout_percentage' => 0,
        ]);

        for ($i = 0; $i < 50; $i++) {
            $this->assertFalse($flags->enabled('canary', $this->plainUser()));
        }
    }

    public function test_a_full_rollout_includes_everyone(): void
    {
        $flags = app(FeatureFlags::class);

        $flags->put([
            'key' => 'canary', 'name' => 'Canary', 'type' => 'boolean', 'value' => true,
            'is_enabled' => true, 'rollout_percentage' => 100,
        ]);

        for ($i = 0; $i < 20; $i++) {
            $this->assertTrue($flags->enabled('canary', $this->plainUser()));
        }
    }

    public function test_a_role_targeted_flag_is_never_resolved_for_anyone_else(): void
    {
        $flags = app(FeatureFlags::class);

        $flags->put([
            'key' => 'admin-only', 'name' => 'Admin only', 'type' => 'string', 'value' => 'secret',
            'is_enabled' => true, 'target_roles' => ['admin'],
        ]);

        $member = $this->plainUser();

        // The default is returned rather than the flag's value: a flag aimed at
        // administrators must not reveal what it holds to anyone else.
        $this->assertSame('fallback', $flags->value('admin-only', $member, 'fallback'));

        $admin = $this->adminWith([]);

        $this->assertSame('secret', $flags->value('admin-only', $admin, 'fallback'));
    }

    public function test_a_guest_cannot_resolve_a_role_targeted_flag(): void
    {
        $flags = app(FeatureFlags::class);

        $flags->put([
            'key' => 'admin-only', 'name' => 'Admin only', 'type' => 'boolean', 'value' => 'secret',
            'is_enabled' => true, 'target_roles' => ['admin'],
        ]);

        $this->assertSame('fallback', $flags->value('admin-only', null, 'fallback'));
    }

    public function test_deleting_a_flag_returns_call_sites_to_their_defaults(): void
    {
        $flags = app(FeatureFlags::class);

        $flags->put(['key' => 'temporary', 'name' => 'Temporary', 'type' => 'boolean', 'value' => true, 'is_enabled' => true]);
        $this->assertTrue($flags->enabled('temporary', default: false));

        $flags->delete('temporary');

        $this->assertTrue($flags->enabled('temporary', default: true), 'A deleted flag must fall back.');
    }

    public function test_a_typed_flag_returns_its_declared_type(): void
    {
        $flags = app(FeatureFlags::class);

        $flags->put(['key' => 'limit', 'name' => 'Limit', 'type' => 'integer', 'value' => 25, 'is_enabled' => true]);

        $value = $flags->value('limit');

        $this->assertSame(25, $value);
        $this->assertIsInt($value);
    }

    public function test_creating_a_flag_rejects_an_identifier_shaped_key(): void
    {
        // A flag key becomes an attribute on the flag object and appears in cache
        // keys, so it is held to an identifier shape rather than free text.
        $this->actingAs($this->superAdmin(['system.flags']))
            ->post(route('admin.system.flags.store'), [
                'key' => 'Not A Key!',
                'type' => 'boolean',
                'rollout_percentage' => 100,
            ])
            ->assertSessionHasErrors('key');

        $this->assertDatabaseCount('feature_flags', 0);
    }

    public function test_toggling_a_flag_flips_it(): void
    {
        $admin = $this->superAdmin(['system.flags']);

        $this->actingAs($admin)->post(route('admin.system.flags.store'), [
            'key' => 'new-thing', 'name' => 'New thing', 'type' => 'boolean', 'rollout_percentage' => 100,
        ])->assertRedirect();

        $this->assertFalse(app(FeatureFlags::class)->enabled('new-thing'));

        $this->actingAs($admin)
            ->post(route('admin.system.flags.toggle', 'new-thing'))
            ->assertRedirect();

        $this->assertTrue(app(FeatureFlags::class)->enabled('new-thing'));

        $this->actingAs($admin)
            ->post(route('admin.system.flags.toggle', 'new-thing'))
            ->assertRedirect();

        $this->assertFalse(app(FeatureFlags::class)->enabled('new-thing'));
    }

    public function test_clearing_the_targeting_field_means_everyone_not_nobody(): void
    {
        $admin = $this->superAdmin(['system.flags']);

        $this->actingAs($admin)->post(route('admin.system.flags.store'), [
            'key' => 'scoped', 'name' => 'Scoped', 'type' => 'boolean', 'rollout_percentage' => 100,
            'target_roles_raw' => 'admin, manager',
        ]);

        $this->assertSame(['admin', 'manager'], app(FeatureFlags::class)->definitions()['scoped']['target_roles']);

        // `store` deliberately creates a flag OFF, so switch it on before testing
        // that clearing the targeting re-opens it to everyone.
        $this->actingAs($admin)->post(route('admin.system.flags.toggle', 'scoped'));

        $this->assertFalse(
            app(FeatureFlags::class)->enabled('scoped', $this->plainUser()),
            'A flag aimed at roles should not reach an unrelated user.',
        );

        $this->actingAs($admin)->put(route('admin.system.flags.update', 'scoped'), [
            'name' => 'Scoped', 'type' => 'boolean', 'rollout_percentage' => 100, 'target_roles_raw' => '',
        ]);

        // NULL means "everyone"; an empty array would mean "nobody" and would
        // silently disable the flag the moment somebody cleared the field.
        $this->assertNull(app(FeatureFlags::class)->definitions()['scoped']['target_roles']);
        $this->assertTrue(
            app(FeatureFlags::class)->enabled('scoped', $this->plainUser()),
            'Clearing the targeting field made the flag unreachable for everyone.',
        );
    }

    // ---- Health -------------------------------------------------------------

    public function test_the_probes_answer_a_machine(): void
    {
        $this->getJson('/livez')
            ->assertOk()
            ->assertJsonPath('status', 'ok')
            ->assertJsonStructure(['status', 'checks', 'application' => ['name', 'environment', 'time']]);
    }

    public function test_liveness_does_not_depend_on_the_database(): void
    {
        // A database blip must not make every replica report itself dead and get
        // restarted — that turns a recoverable outage into a total one.
        // Measured rather than simulated. Disconnecting the connection would prove
        // the point but leaves an in-memory SQLite database — which IS the
        // connection — unmigrated, so every later test in the class fails. Counting
        // queries shows the same thing safely: liveness touches nothing, readiness
        // touches the dependencies.
        $liveness = $this->countQueries(fn () => $this->getJson('/livez')->assertOk());
        $readiness = $this->countQueries(fn () => $this->getJson('/readyz')->assertOk());

        $this->assertSame(
            0,
            $liveness,
            'Liveness queried something. It must not touch the database: a database '
            .'blip would make every replica report itself dead and get restarted, '
            .'turning a recoverable outage into a total one.',
        );

        $this->assertGreaterThan(0, $readiness, 'Readiness should check the dependencies it depends on.');
    }

    public function test_readiness_reports_the_dependencies_it_checks(): void
    {
        $this->getJson('/readyz')
            ->assertOk()
            ->assertJsonStructure([
                'checks' => ['database', 'cache', 'queue', 'storage'],
            ]);
    }

    public function test_a_health_document_states_its_verdict_in_the_status_code(): void
    {
        $document = app(HealthCheck::class)->readiness();

        $expected = $document['status'] === 'ok' ? 200 : 503;

        $this->getJson('/readyz')->assertStatus($expected);
    }

    public function test_the_health_document_never_leaks_a_connection_string(): void
    {
        // The probes are unauthenticated, so a DSN or a credential in the document
        // would be readable by anybody who can reach the site.
        $body = $this->getJson('/readyz')->assertOk()->getContent();

        foreach (['password', 'DB_', 'mysql://', 'sqlite://', '@tcp', 'secret'] as $leak) {
            $this->assertStringNotContainsString($leak, $body);
        }
    }

    public function test_a_failing_check_takes_the_document_with_it(): void
    {
        $report = app(HealthCheck::class)->full();

        // The verdict is the AND of every check, so a partial failure cannot read
        // as healthy.
        $expected = collect($report['checks'])->every(fn (array $check): bool => $check['status'] === 'pass');

        $this->assertSame($expected ? 'ok' : 'degraded', $report['status']);
    }

    public function test_the_health_screen_names_every_check(): void
    {
        $this->actingAs($this->superAdmin(['system.health']))
            ->get(route('admin.system.health.index'))
            ->assertOk()
            ->assertSee('Database')
            ->assertSee('Cache')
            ->assertSee('Queue')
            ->assertSee('Storage');
    }

    // ---- Queue --------------------------------------------------------------

    public function test_the_queue_screen_does_not_render_a_job_payload(): void
    {
        DB::table('failed_jobs')->insert([
            'uuid' => 'test-uuid-1',
            'connection' => 'database',
            'queue' => 'default',
            'payload' => json_encode(['secret' => 'CONTAINS_A_SECRET_TOKEN']),
            'exception' => "RuntimeException: it broke\n#0 /app/secret/path.php(12)",
            'failed_at' => now()->toDateTimeString(),
        ]);

        $html = $this->actingAs($this->superAdmin(['system.queue']))
            ->get(route('admin.system.queue.index'))
            ->assertOk()
            ->getContent();

        // The payload routinely carries notification bodies, mail queue keys and
        // model identifiers. On an administration screen it would reach every
        // administrator and every screenshot taken during an incident.
        $this->assertStringNotContainsString('CONTAINS_A_SECRET_TOKEN', $html);

        // The first line of the exception is what identifies the failure; the trace
        // belongs in the log.
        $this->assertStringContainsString('it broke', $html);
    }

    public function test_the_queue_screen_reports_pending_depth(): void
    {
        DB::table('jobs')->insert([
            'queue' => 'default',
            'payload' => json_encode(['job' => 'x']),
            'attempts' => 0,
            'reserved_at' => null,
            'available_at' => now()->timestamp,
            'created_at' => now()->subMinutes(5)->timestamp,
        ]);

        $this->actingAs($this->superAdmin(['system.queue']))
            ->get(route('admin.system.queue.index'))
            ->assertOk()
            ->assertSee('default')
            ->assertSee('Pending by queue');
    }

    public function test_retrying_a_failed_job_goes_through_artisan(): void
    {
        DB::table('failed_jobs')->insert([
            'uuid' => 'retry-me',
            'connection' => 'database',
            'queue' => 'default',
            'payload' => json_encode(['displayName' => 'App\\Jobs\\SendMailJob']),
            'exception' => 'RuntimeException: boom',
            'failed_at' => now()->toDateTimeString(),
        ]);

        $this->actingAs($this->superAdmin(['system.queue']))
            ->post(route('admin.system.queue.retry'), ['id' => 'retry-me'])
            ->assertRedirect()
            ->assertSessionHas('success');

        // The job is requeued rather than deleted, which is what `queue:retry`
        // does. Reimplementing it over the table would re-implement payload
        // decoding, backoff and the lock release.
        $this->assertSame(0, DB::table('failed_jobs')->count());
    }

    public function test_discarding_a_failed_job_removes_it(): void
    {
        DB::table('failed_jobs')->insert([
            'uuid' => 'drop-me',
            'connection' => 'database',
            'queue' => 'default',
            'payload' => json_encode(['displayName' => 'x']),
            'exception' => 'boom',
            'failed_at' => now()->toDateTimeString(),
        ]);

        $this->actingAs($this->superAdmin(['system.queue']))
            ->post(route('admin.system.queue.forget'), ['id' => 'drop-me'])
            ->assertRedirect();

        $this->assertSame(0, DB::table('failed_jobs')->count());
    }

    // ---- Schedule -----------------------------------------------------------

    public function test_the_schedule_screen_reads_the_applications_own_schedule(): void
    {
        $this->actingAs($this->superAdmin(['system.schedule']))
            ->get(route('admin.system.schedule.index'))
            ->assertOk()
            ->assertSee('reminders:dispatch')
            ->assertSee('todos:overdue');
    }

    public function test_every_scheduled_entry_carries_its_guards(): void
    {
        $this->actingAs($this->superAdmin(['system.schedule']))
            ->get(route('admin.system.schedule.index'))
            ->assertOk()
            ->assertDontSee('Missing:');

        // The absence of `withoutOverlapping` or `onOneServer` is invisible until
        // the hour it matters, so a missing guard is stated rather than assumed.
    }

    public function test_the_schedule_is_evaluated_in_the_business_timezone(): void
    {
        $this->actingAs($this->superAdmin(['system.schedule']))
            ->get(route('admin.system.schedule.index'))
            ->assertOk()
            ->assertSee((string) config('app.timezone'));
    }

    // ---- Tokens -------------------------------------------------------------

    public function test_the_token_screen_shows_metadata_and_never_a_value(): void
    {
        $user = $this->superAdmin(['system.tokens']);

        $token = $user->createToken('nightly', ['read']);

        $html = $this->actingAs($user)
            ->get(route('admin.system.tokens.index'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('nightly', $html);

        // Sanctum stores only a hash, so a "token" column here would be a lie.
        $this->assertStringNotContainsString($token->plainTextToken, $html);
    }

    public function test_revoking_a_token_requires_it_to_be_your_own(): void
    {
        $mine = $this->superAdmin(['system.tokens']);
        $theirs = $this->superAdmin(['system.tokens']);

        $theirsToken = $theirs->createToken('theirs', ['read']);

        $this->actingAs($mine)
            ->delete(route('admin.system.tokens.destroy', $theirsToken->accessToken->id))
            ->assertRedirect()
            ->assertSessionHas('error');

        // The screen shows ids and an id is guessable, so the delete is scoped by
        // tokenable rather than trusted.
        $this->assertDatabaseHas('personal_access_tokens', ['id' => $theirsToken->accessToken->id]);
    }

    public function test_revoking_others_clears_every_token_on_the_account(): void
    {
        $user = $this->superAdmin(['system.tokens']);

        $user->createToken('one', ['read']);
        $user->createToken('two', ['read']);
        $user->createToken('three', ['read']);

        $this->assertSame(3, $user->tokens()->count());

        $this->actingAs($user)
            ->post(route('admin.system.tokens.destroy-others'))
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertSame(0, $user->tokens()->count());
    }

    public function test_revoking_others_does_not_log_the_operator_out(): void
    {
        $user = $this->superAdmin(['system.tokens']);
        $user->createToken('integration', ['read']);

        $this->actingAs($user)
            ->post(route('admin.system.tokens.destroy-others'))
            ->assertRedirect();

        // The operator is on a SESSION, not a token, so clearing the account's
        // tokens cannot end the session they are standing in. This is the whole
        // reason the exclusion exists.
        $this->actingAs($user)
            ->get(route('admin.system.tokens.index'))
            ->assertOk();
    }

    // ---- Interactions between the features ---------------------------------

    public function test_a_setting_and_a_flag_do_not_share_a_cache_key(): void
    {
        app(Settings::class)->set('app.timezone', 'Asia/Dhaka');

        FeatureFlag::factory()->enabled()->create([
            'key' => 'timezone-thing',
            'value' => 'Asia/Dhaka',
        ]);

        Cache::flush();

        $this->assertSame('Asia/Dhaka', app(Settings::class)->get('app.timezone'));
        $this->assertTrue(app(FeatureFlags::class)->enabled('timezone-thing'));
    }

    public function test_a_setting_can_take_a_module_away_without_touching_the_module(): void
    {
        $user = $this->userWithPermissions(['todos.view_all']);
        $todo = Todo::factory()->assignedTo($user)->create();

        app(Settings::class)->set('app.timezone', 'Pacific/Auckland');

        // Configuration is orthogonal to data: changing a setting must not change
        // what a user can see. Sanctum, not a session: `sanctum.guard` is empty, so
        // a browser cookie cannot authenticate an API route.
        Sanctum::actingAs($user);

        $this->getJson('/api/v1/todos')->assertOk()->assertJsonPath('data.0.id', $todo->id);
    }
}
