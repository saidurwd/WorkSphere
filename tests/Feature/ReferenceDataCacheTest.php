<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Location;
use App\Models\User;
use App\Support\ReferenceData;
use Illuminate\Cache\Repository as IlluminateCacheRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Modules\Meetings\Models\Meeting;
use Tests\InteractsWithRoles;
use Tests\TestCase;

/**
 * The reference-list cache: it caches, it invalidates, and it cannot leak.
 *
 * Three properties, and the third is the one that would be a security problem
 * rather than a bug.
 *
 * The dashboard widget cache — a sibling of this one, built the same way — had no
 * user segment in its key and served one user's personal To-Dos to another. So the
 * question "can this key be shared?" is asked here explicitly rather than assumed,
 * and the answer for these lists is yes (they are identical for every caller) only
 * because they are UNFILTERED. The scope segment exists so a future filtered caller
 * cannot collide with the unfiltered value.
 */
class ReferenceDataCacheTest extends TestCase
{
    use InteractsWithRoles, RefreshDatabase;

    private ReferenceData $referenceData;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();

        $this->referenceData = app(ReferenceData::class);
    }

    public function test_it_caches_a_list_so_the_second_read_costs_nothing(): void
    {
        User::factory()->count(5)->create();

        $first = $this->countQueries(fn () => $this->referenceData->users());
        $second = $this->countQueries(fn () => $this->referenceData->users());

        $this->assertGreaterThan(0, $first, 'The first read should query.');
        $this->assertSame(0, $second, 'The second read should be served from the cache.');
    }

    public function test_writing_a_user_invalidates_the_cached_list(): void
    {
        User::factory()->count(3)->create();

        $this->assertCount(3, $this->referenceData->users());

        User::factory()->create(['name' => 'Zeta Person']);

        $names = $this->referenceData->users()->pluck('name')->all();

        // Without the observer this would still be three rows, and the new person
        // would be invisible in every dropdown until the TTL expired.
        $this->assertContains('Zeta Person', $names);
    }

    public function test_renaming_a_user_invalidates_the_cached_list(): void
    {
        $user = User::factory()->create(['name' => 'Before Name']);

        $this->referenceData->users();

        $user->update(['name' => 'After Name']);

        $this->assertContains('After Name', $this->referenceData->users()->pluck('name')->all());
    }

    public function test_deleting_a_user_invalidates_the_cached_list(): void
    {
        $user = User::factory()->create(['name' => 'Departing']);

        $this->referenceData->users();

        $user->delete();

        $this->assertNotContains('Departing', $this->referenceData->users()->pluck('name')->all());
    }

    public function test_writing_a_department_invalidates_the_department_list(): void
    {
        Department::factory()->create(['department_name' => 'Alpha Dept']);

        $this->assertCount(1, $this->referenceData->departments());

        Department::factory()->create(['department_name' => 'Beta Dept']);

        $names = $this->referenceData->departments()->pluck('department_name')->all();

        $this->assertContains('Beta Dept', $names);
    }

    public function test_invalidating_one_resource_leaves_the_others_cached(): void
    {
        User::factory()->count(3)->create();
        Department::factory()->count(3)->create();

        $this->referenceData->users();
        $this->referenceData->departments();

        $this->referenceData->invalidate('users');

        $this->assertGreaterThan(0, $this->countQueries(fn () => $this->referenceData->users()), 'Users should have been invalidated.');
        $this->assertSame(0, $this->countQueries(fn () => $this->referenceData->departments()), 'Departments should still be cached.');
    }

    public function test_a_scoped_list_cannot_collide_with_the_unscoped_one(): void
    {
        Location::factory()->create(['location_name' => 'Live Site', 'status' => 'active']);
        Location::factory()->create(['location_name' => 'Closed Site', 'status' => 'inactive']);

        $all = $this->referenceData->locations();
        $active = $this->referenceData->locations(scope: ReferenceData::SCOPE_ACTIVE);

        $this->assertCount(2, $all);
        $this->assertSame(
            ['Live Site'],
            $active->pluck('location_name')->values()->all(),
            'The active scope did not filter.',
        );

        // The two must not share a key, or the filtered caller would be served the
        // full list — and a form would offer a retired location.
        $this->assertNotSame(
            $this->referenceData->keyFor('locations'),
            $this->referenceData->keyFor('locations', ReferenceData::SCOPE_ACTIVE),
        );
    }

    public function test_an_unknown_scope_is_rejected(): void
    {
        // Left unchallenged, an unknown scope would store the UNFILTERED list under
        // a key that reads as filtered, and the caller would silently get more than
        // it asked for.
        $this->expectException(\InvalidArgumentException::class);

        $this->referenceData->locations(scope: 'dept:7');
    }

    public function test_users_can_be_scoped_to_active(): void
    {
        // Every reference table here carries a `status`, so the active scope is a
        // real filter rather than a decorative key segment.
        User::factory()->create(['name' => 'Active Person', 'status' => 'active']);
        User::factory()->create(['name' => 'Suspended Person', 'status' => 'suspended']);

        $names = $this->referenceData->users(scope: ReferenceData::SCOPE_ACTIVE)
            ->pluck('name')
            ->all();

        $this->assertSame(['Active Person'], $names);
    }

    public function test_the_key_is_stable_and_carries_the_version(): void
    {
        $this->referenceData->users();

        $before = $this->referenceData->keyFor('users');

        $this->assertMatchesRegularExpression(
            '/^reference:users:v\d+:all$/',
            $before,
            'The key shape is what the invalidation guarantee depends on.',
        );

        $this->referenceData->invalidate('users');

        $this->assertNotSame($before, $this->referenceData->keyFor('users'));
    }

    public function test_a_cached_list_is_returned_as_a_collection_of_models(): void
    {
        User::factory()->count(2)->create();

        $users = $this->referenceData->users();

        $this->assertInstanceOf(Collection::class, $users);

        // Models, not arrays and not `__PHP_Incomplete_Class`. A view reads
        // `$user->id` and `$user->name`, so anything else throws at render time
        // rather than at read time.
        foreach ($users as $user) {
            $this->assertInstanceOf(User::class, $user);
            $this->assertIsInt($user->id);
            $this->assertIsString($user->name);
        }
    }

    /**
     * The list survives a store that actually serializes.
     *
     * Every other test in this file runs on the `array` driver, which hands back
     * the very object that was put in and therefore cannot surface a bad payload
     * shape. Production runs `database`, which unserializes — and
     * `config('cache.serializable_classes')` is `false`, so it unserializes with
     * `allowed_classes => false` and turns every cached object into
     * `__PHP_Incomplete_Class`.
     *
     * That is precisely how `/tasks/create` came to throw "Attempt to read property
     * id on __PHP_Incomplete_Class" while the whole suite passed. This test pins
     * the round trip through a real serializer, so the guarantee is asserted
     * against the code path production uses rather than a stand-in for it.
     */
    public function test_a_cached_list_survives_a_round_trip_through_a_real_serializer(): void
    {
        User::factory()->count(3)->create();

        $expected = $this->referenceData->users()
            ->map(fn (User $user): array => [$user->id, $user->name])
            ->all();

        // What the `allowed_classes => false` the database store is configured
        // with does to the payload.
        $roundTripped = unserialize(
            serialize($this->cachedPayload('users')),
            ['allowed_classes' => false],
        );

        $users = $this->hydrateThrough($roundTripped, User::class);

        $this->assertSame(
            $expected,
            $users->map(fn (User $user): array => [$user->id, $user->name])->all(),
            'A list read back through the production serializer does not match the one stored.',
        );

        foreach ($users as $user) {
            $this->assertInstanceOf(User::class, $user);
        }
    }

    /**
     * Nothing object-shaped is written to the cache in the first place.
     *
     * Stronger than the round trip above, and the property actually being relied
     * on: the payload is scalars only, so the unserialize policy cannot affect it
     * at all. A stored model is a latent failure for any store with a restricted
     * allow-list, whatever that list happens to be today.
     */
    public function test_the_cached_payload_contains_no_objects(): void
    {
        User::factory()->count(2)->create();
        Department::factory()->count(2)->create();

        $this->referenceData->users();
        $this->referenceData->departments();

        foreach (['users', 'departments'] as $resource) {
            $payload = $this->cachedPayload($resource);

            $this->assertIsArray($payload, "The [{$resource}] payload is not an array.");
            $this->assertNotEmpty($payload);

            foreach ($payload as $row) {
                $this->assertIsArray($row, "The [{$resource}] payload holds a non-array row.");
                $this->assertNotEmpty($row);

                foreach ($row as $value) {
                    $this->assertIsNotObject(
                        $value,
                        "The [{$resource}] payload holds a model, which unserializes to __PHP_Incomplete_Class.",
                    );
                }
            }
        }
    }

    /**
     * The cached value for a resource, read the way the store reads it.
     */
    private function cachedPayload(string $resource): mixed
    {
        $this->referenceData->users();
        $this->referenceData->departments();

        $key = $this->referenceData->keyFor($resource);

        $reflection = new \ReflectionProperty(IlluminateCacheRepository::class, 'store');
        $reflection->setAccessible(true);
        $store = $reflection->getValue(Cache::store());

        $method = new \ReflectionMethod($store, 'get');
        $method->setAccessible(true);

        return $method->invoke($store, $key);
    }

    /**
     * Run a payload back through the service's own rehydration, which is the code
     * a real read executes.
     *
     * @param  class-string<Model>  $model
     * @return Collection<int, Model>
     */
    private function hydrateThrough(mixed $payload, string $model): Collection
    {
        $method = new \ReflectionMethod(ReferenceData::class, 'hydrate');
        $method->setAccessible(true);

        return $method->invoke(app(ReferenceData::class), $payload, $model);
    }

    public function test_a_page_with_several_reference_lists_queries_each_once(): void
    {
        $viewer = $this->userWithPermissions(['meeting.view', 'meeting.create', 'meeting.edit']);

        Meeting::factory()->count(4)->create();
        Department::factory()->count(4)->create();
        User::factory()->count(4)->create();

        $cold = $this->countTableQueries(fn () => $this->actingAs($viewer)->get('/meetings/create')->assertOk());

        // The second render of the same page. Before the cache the user and
        // department lists were re-queried here; now they are not.
        $warm = $this->countTableQueries(fn () => $this->actingAs($viewer)->get('/meetings/create')->assertOk());

        $this->assertGreaterThanOrEqual(
            1,
            $cold['users'],
            'The cold render should read the user list once; zero means the page stopped rendering it.',
        );

        $this->assertSame(
            0,
            $warm['users'],
            'The user reference list was queried '.$warm['users'].' times on a warm render.',
        );

        $this->assertSame(
            0,
            $warm['departments'],
            'The department reference list was queried '.$warm['departments'].' times on a warm render.',
        );
    }

    /**
     * Count queries per reference table across one request.
     *
     * @param  callable(): mixed  $request
     * @return array<string, int>
     */
    private function countTableQueries(callable $request): array
    {
        $tables = ['users', 'departments', 'locations'];
        $counts = array_fill_keys($tables, 0);

        $listener = function ($query) use (&$counts): void {
            foreach (array_keys($counts) as $table) {
                if (preg_match('/from "'.$table.'"/', $query->sql) === 1) {
                    $counts[$table]++;
                }
            }
        };

        DB::listen($listener);

        $request();

        DB::connection()->getEventDispatcher()?->forget('Illuminate\Database\Events\QueryExecuted');

        return $counts;
    }

    /**
     * @param  callable(): mixed  $callback
     */
    private function countQueries(callable $callback): int
    {
        $queries = 0;

        DB::listen(function () use (&$queries): void {
            $queries++;
        });

        $callback();

        return $queries;
    }
}
