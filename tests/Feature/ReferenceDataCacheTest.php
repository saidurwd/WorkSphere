<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Location;
use App\Models\User;
use App\Support\ReferenceData;
use Illuminate\Database\Eloquent\Collection;
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

    public function test_a_cached_list_is_returned_as_a_collection(): void
    {
        User::factory()->count(2)->create();

        // Some stores hand back an unserialised plain array; a view calling
        // `->first()` on that would fail at render time rather than at read time.
        $this->assertInstanceOf(
            Collection::class,
            $this->referenceData->users(),
        );
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
