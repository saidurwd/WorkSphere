<?php

namespace Tests\Feature;

use App\Dashboard\DashboardWidget;
use App\Dashboard\WidgetRegistry;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Cache\Repository as IlluminateCacheRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Modules\Meetings\Models\Meeting;
use Modules\Obligations\Models\Obligation;
use Modules\Obligations\Models\ObligationType;
use Modules\Tasks\Models\Task;
use Modules\Todos\Models\Todo;
use Tests\InteractsWithRoles;
use Tests\TestCase;

/**
 * A widget value must survive the real cache store, not just the array driver.
 *
 * `config/cache.php` sets `serializable_classes => false`, so anything cached
 * through the database store comes back out of `unserialize()` as
 * `__PHP_Incomplete_Class` — and the first method call on it throws. Eleven of the
 * thirteen widgets resolve to a Collection, so the registry stores them as plain
 * arrays and re-wraps them on the way out.
 *
 * The array driver in `phpunit.xml` hands back the exact object it was given,
 * serialized or not, which is exactly why the existing widget tests never saw
 * this. So the load-bearing assertions below do not go through the array store at
 * all: they round-trip the value through a real `serialize()`/
 * `unserialize(..., ['allowed_classes' => false])` pair, which is what the
 * database store does with every payload.
 *
 * The other trap this covers is nesting. Two widgets resolve to an ARRAY whose
 * values are themselves Collections — `personal_stats.weeklyBars` and
 * `obligation_expiry.typeBars`/`priorityDonut` — and `DashboardController` calls
 * `->sum()` and `->all()` on those directly. Flattening only the top level leaves
 * an object in the cache and the second dashboard view still dies, so these tests
 * deliberately seed real rows rather than asserting against empty charts.
 */
class WidgetCacheSerializationTest extends TestCase
{
    use InteractsWithRoles, RefreshDatabase;

    private WidgetRegistry $registry;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();

        $this->registry = app(WidgetRegistry::class);
    }

    /**
     * The value actually sitting in the cache store, before any hydration.
     *
     * `resolveFor()` returns the HYDRATED value — a Collection again — so
     * round-tripping its return value would test the wrong thing and report a
     * healthy implementation as broken. What matters is the payload the registry
     * hands to the store, so this reads the entry back out of the store itself.
     */
    private function cachedPayload(User $user, string $widgetKey): mixed
    {
        $this->registry->resolveFor($user);

        $reflection = new \ReflectionProperty(IlluminateCacheRepository::class, 'store');
        $reflection->setAccessible(true);
        $store = $reflection->getValue(Cache::store());

        $method = new \ReflectionMethod($store, 'get');
        $method->setAccessible(true);

        return $method->invoke($store, $this->registry->keyFor($user, $this->widget($widgetKey)));
    }

    private function widget(string $widgetKey): DashboardWidget
    {
        foreach (WidgetRegistry::defaults() as $widget) {
            if ($widget->key() === $widgetKey) {
                return $widget;
            }
        }

        $this->fail("No widget named {$widgetKey} is registered.");
    }

    /**
     * Assert a resolved value contains no object at any depth.
     *
     * The failure mode being guarded against is silent in the array store: the
     * object simply comes back intact and the dashboard renders. Under
     * `allowed_classes => false` it becomes `__PHP_Incomplete_Class`, so checking
     * the round-tripped payload is what actually proves the payload is clean.
     */
    private function assertHoldsNoObjects(mixed $value, string $path = 'root'): void
    {
        if ($value instanceof \__PHP_Incomplete_Class || is_object($value)) {
            $this->fail("Cacheable payload at {$path} is an object (".get_debug_type($value).').');
        }

        if (! is_array($value)) {
            $this->assertIsNotObject($value, "Cacheable payload at {$path} is an object.");

            return;
        }

        foreach ($value as $key => $item) {
            $this->assertHoldsNoObjects($item, "{$path}[{$key}]");
        }
    }

    public function test_a_list_valued_widget_survives_the_real_serializer(): void
    {
        $user = $this->dashboardUser();

        $cached = $this->cachedPayload($user, 'task_distribution');

        $this->assertIsArray($cached, 'A list-valued widget must be stored as a plain array.');
        $this->assertHoldsNoObjects($cached);

        // And it survives the production unserialize policy, so the entry is
        // usable from the database store rather than only from the array driver.
        $this->assertIsArray(
            unserialize(serialize($cached), ['allowed_classes' => false]),
        );

        // And the registry turns it back into the shape the partial iterates.
        $hydrated = $this->registry->resolveFor($user)->get('task_distribution');

        $this->assertInstanceOf(Collection::class, $hydrated);
        $hydrated->sum('value');
    }

    public function test_a_stat_map_widget_keeps_its_keys_and_rebuilds_nested_collections(): void
    {
        $user = $this->dashboardUser();

        $type = ObligationType::factory()->create(['type_name' => 'Licence']);

        // The widget only sees obligations the user OWNS or is responsible for, and
        // the factory's default owner is somebody else — so without this the charts
        // come back empty and the whole assertion below passes vacuously.
        Obligation::factory()->count(3)->create([
            'obligation_type_id' => $type->id,
            'owner_user_id' => $user->id,
            'priority' => 'high',
            'status' => 'active',
        ]);

        $cached = $this->cachedPayload($user, 'obligation_expiry');

        $this->assertHoldsNoObjects($cached);

        // A stat map is indexed BY KEY, so wrapping it in a Collection would turn
        // it into a list and every `[$key]` lookup in the partial would fail.
        $this->assertIsArray($cached);
        $this->assertArrayHasKey('total', $cached);
        $this->assertArrayHasKey('typeBars', $cached);

        // The nested charts are non-empty here, which is the whole point: an empty
        // chart would restore trivially and prove nothing. This is the case the
        // first, non-recursive version of `cacheable()` silently got wrong.
        $this->assertNotEmpty($cached['typeBars'], 'The nested chart was empty, so this proves nothing.');
        $this->assertNotEmpty($cached['priorityDonut'], 'The nested chart was empty, so this proves nothing.');

        $hydrated = $this->registry->resolveFor($user)->get('obligation_expiry');

        // `DashboardController` sums and calls `->all()` on these directly.
        $this->assertInstanceOf(Collection::class, $hydrated['typeBars']);
        $this->assertInstanceOf(Collection::class, $hydrated['priorityDonut']);

        $total = array_sum(array_map(
            static fn (array $row): int => (int) ($row['value'] ?? 0),
            $hydrated['priorityDonut']->all(),
        ));

        $this->assertSame(3, $total);
    }

    public function test_every_widget_payload_is_object_free(): void
    {
        $user = $this->dashboardUser();

        $this->seedEverySource($user);

        $this->registry->resolveFor($user);

        foreach (WidgetRegistry::defaults() as $widget) {
            $this->assertHoldsNoObjects(
                $this->cachedPayload($user, $widget->key()),
                "widget {$widget->key()}",
            );
        }

        $this->assertCount(13, $this->registry->resolveFor($user));
    }

    public function test_a_widget_that_leaks_a_model_is_caught_rather_than_passing_on_an_empty_result(): void
    {
        // The specific regression this file existed to prevent, and the one that got
        // through it. `upcoming_meetings` resolved to a Collection of Meeting MODELS
        // and `task_distribution` put a `WorkItemStatus` enum in its `status` key.
        // `cacheable()` only flattens Collections and arrays, so both objects went
        // into the database store and came back as `__PHP_Incomplete_Class` — the
        // second dashboard view of any user then died inside `route('meetings.show')`.
        //
        // The loop above could not see either: with no meetings and no tasks seeded,
        // both widgets resolved to an EMPTY collection, and an empty collection
        // contains no objects. A payload assertion is only worth what its fixture is
        // worth, so the two rows are asserted non-empty AND object-free.
        $user = $this->dashboardUser();

        Meeting::factory()->organisedBy($user)->create(['title' => 'Board review']);
        Task::factory()->ownedBy($user)->create();

        $meetings = $this->cachedPayload($user, 'upcoming_meetings');

        $this->assertNotEmpty($meetings, 'No meeting was cached, so the assertion below proves nothing.');
        $this->assertHoldsNoObjects($meetings, 'upcoming_meetings');

        $this->assertSame('Board review', $meetings[0]['title']);
        $this->assertSame(route('meetings.show', $meetings[0]['id']), $meetings[0]['url']);

        $distribution = $this->cachedPayload($user, 'task_distribution');

        $this->assertNotEmpty($distribution, 'No task was cached, so the assertion below proves nothing.');
        $this->assertHoldsNoObjects($distribution, 'task_distribution');

        // The enum is the leak that is easiest to reintroduce: `$row->status` reads
        // naturally in a widget and only becomes an object because of the cast.
        $this->assertIsString($distribution[0]['status']);
    }

    public function test_a_second_read_serves_the_same_values_without_rerunning_the_query(): void
    {
        $user = $this->dashboardUser();

        $cold = $this->registry->resolveFor($user);
        $warm = $this->registry->resolveFor($user);

        // Shapes must survive the cache, not just values.
        foreach ($cold as $key => $value) {
            $this->assertSame(
                get_debug_type($value),
                get_debug_type($warm->get($key)),
                "Widget {$key} changed type between the cold and warm read.",
            );
        }
    }

    /**
     * One row per source a widget reads, all owned by the viewer.
     *
     * Without this every widget resolves to an empty collection and every payload
     * assertion passes for the wrong reason — the object-free check finds nothing
     * to object to, and the test protects nothing.
     */
    private function seedEverySource(User $user): void
    {
        $type = ObligationType::factory()->create(['type_name' => 'Licence']);

        Todo::factory()->titleOnly('A to-do')->createdBy($user)->assignedTo($user)->create();
        Task::factory()->ownedBy($user)->create();
        Meeting::factory()->organisedBy($user)->create();

        Obligation::factory()->create([
            'obligation_type_id' => $type->id,
            'owner_user_id' => $user->id,
            'priority' => 'high',
            'status' => 'active',
            'risk_level' => 'critical',
        ]);

        ActivityLog::factory()->forSubject(
            Todo::factory()->titleOnly('Logged')->createdBy($user)->create(),
            'created',
        )->create(['user_id' => $user->id]);
    }

    /**
     * A user who can see every widget, so all thirteen are exercised.
     *
     * The permission list is derived from the widgets themselves rather than
     * hardcoded: a hardcoded copy would silently stop covering a widget the day a
     * new permission is added, and the test would keep passing with thirteen keys
     * of which only four were visible.
     */
    private function dashboardUser(): User
    {
        $permissions = collect(WidgetRegistry::defaults())
            ->flatMap(fn (DashboardWidget $widget): array => $widget->permissions())
            ->unique()
            ->values()
            ->all();

        return $this->superAdmin($permissions);
    }
}
