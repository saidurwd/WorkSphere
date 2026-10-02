<?php

namespace Tests\Feature;

use App\Dashboard\DashboardWidget;
use App\Dashboard\WidgetRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Modules\Meetings\Models\Meeting;
use Modules\Tasks\Models\Task;
use Modules\Todos\Models\Todo;
use Tests\InteractsWithRoles;
use Tests\TestCase;

/**
 * The dashboard widget cache must not serve one user's data to another.
 *
 * Found by measurement, not by inspection: the registry keys its cache on the
 * widget and a digest of the permissions, which is identical for any two users who
 * can see the same widgets. The personal widgets resolve data ABOUT THE VIEWER, so
 * the first person to load a dashboard populated the entry and everyone after them
 * read it.
 *
 * The assertions here are deliberately about DIFFERENT users with the SAME
 * permissions, because that is the case the old key collapsed. A test with two
 * users holding different permissions would have passed throughout, which is what
 * made the bug survive Phase 10.
 */
class WidgetCacheIsolationTest extends TestCase
{
    use InteractsWithRoles, RefreshDatabase;

    private WidgetRegistry $registry;

    protected function setUp(): void
    {
        parent::setUp();

        // The array cache survives between tests in a run, so a widget value
        // resolved by an earlier test would be served here and the assertions would
        // be about the cache rather than about the key.
        Cache::flush();

        $this->registry = app(WidgetRegistry::class);
    }

    public function test_two_users_with_the_same_permissions_do_not_share_widget_data(): void
    {
        $alice = $this->userWithPermissions(['todos.view']);
        $bob = $this->userWithPermissions(['todos.view']);

        Todo::factory()->titleOnly('ALICE ONLY')->assignedTo($alice)->create();
        Todo::factory()->titleOnly('BOB ONLY')->assignedTo($bob)->create();

        // Alice first, so her entry is the one that would be served to Bob.
        $aliceItems = $this->registry->resolveFor($alice)->get('my_todos');
        $bobItems = $this->registry->resolveFor($bob)->get('my_todos');

        $this->assertSame(
            ['ALICE ONLY'],
            $this->titles($aliceItems),
            'Alice should see her own To-Do.',
        );

        $this->assertSame(
            ['BOB ONLY'],
            $this->titles($bobItems),
            'Bob was served Alice\'s cached widget data.',
        );
    }

    public function test_the_cache_key_contains_the_user_id(): void
    {
        $alice = $this->userWithPermissions(['todos.view']);
        $bob = $this->userWithPermissions(['todos.view']);

        foreach ($this->registry->visibleTo($alice) as $widget) {
            $aliceKey = $this->registry->keyFor($alice, $widget);
            $bobKey = $this->registry->keyFor($bob, $widget);

            $this->assertStringContainsString(
                ':u'.$alice->id.':',
                $aliceKey,
                "The key does not carry the user id, so it cannot separate users: {$aliceKey}",
            );

            $this->assertNotSame(
                $aliceKey,
                $bobKey,
                "Two users with identical permissions share a cache key for {$widget->key()}.",
            );
        }
    }

    public function test_a_permission_change_still_invalidates_the_entry(): void
    {
        // The other axis. A user whose OWN permissions change must not be served a
        // value computed under the previous set, even though the user id is
        // unchanged.
        $user = $this->userWithPermissions(['todos.view']);

        $before = $this->registry->resolveFor($user)->get('my_todos');
        $this->assertNotNull($before);

        // The same user, more permissions: a different signature, therefore a
        // different key.
        $this->userWithPermissions(['todos.view'], slug: 'extra', attachTo: $user);

        $after = $this->registry->resolveFor($user->fresh())->get('my_todos');

        $this->assertNotNull($after);
    }

    public function test_invalidation_still_drops_every_users_entry(): void
    {
        $user = $this->userWithPermissions(['todos.view']);

        $widget = $this->widgetNamed('my_todos');

        $before = $this->registry->keyFor($user, $widget);

        // Bumping the version must change the key for the SAME user, or a data
        // change would never be visible to anybody.
        $this->registry->invalidate('my_todos');

        $this->assertNotSame(
            $before,
            $this->registry->keyFor($user, $widget),
            'Invalidation did not change the cache key, so a data change would never be seen.',
        );
    }

    public function test_the_version_segment_is_not_the_user_segment(): void
    {
        // The two segments do different jobs, and swapping or merging them would
        // silently undo one of the two protections above.
        $user = $this->userWithPermissions(['todos.view']);
        $widget = $this->widgetNamed('my_todos');

        $key = $this->registry->keyFor($user, $widget);

        $this->assertMatchesRegularExpression(
            '/^dashboard:widget:my_todos:u\d+:v\d+:[0-9a-f]{12}$/',
            $key,
            'The key shape is not what the invalidation and isolation guarantees assume.',
        );
    }

    public function test_personal_widgets_do_not_leak_across_users_end_to_end(): void
    {
        $alice = $this->userWithPermissions(['todos.view', 'task.view', 'meeting.view']);
        $bob = $this->userWithPermissions(['todos.view', 'task.view', 'meeting.view']);

        Todo::factory()->titleOnly('ALICE TODO')->assignedTo($alice)->create();
        Task::factory()->create(['title' => 'ALICE TASK', 'user_id' => $alice->id]);
        Meeting::factory()->organisedBy($alice)->create(['title' => 'ALICE MEETING']);

        $this->actingAs($alice)->get('/dashboard')->assertOk();

        // Bob's first dashboard load, with Alice's already cached.
        $this->actingAs($bob)->get('/dashboard')->assertOk();

        $data = $this->registry->resolveFor($bob);

        foreach (['my_todos', 'my_tasks', 'upcoming_meetings'] as $key) {
            $serialised = json_encode($data->get($key));

            $this->assertStringNotContainsString(
                'ALICE',
                (string) $serialised,
                "Bob's {$key} widget contains Alice's data.",
            );
        }
    }

    /**
     * @return list<string>
     */
    /**
     * @return list<string>
     */
    private function titles(mixed $items): array
    {
        // `->all()`, never `(array) $collection`. Casting an object to array
        // exposes its private PROPERTIES — `['items' => [...], 'escapeWhen…' => …]` —
        // so `array_column` then finds no `title` and the assertion passes vacuously
        // as an empty list rather than failing loudly.
        $rows = $items instanceof Collection ? $items->all() : (array) $items;

        return array_values(array_column($rows, 'title'));
    }

    private function widgetNamed(string $key): DashboardWidget
    {
        foreach ($this->registry->all() as $widget) {
            if ($widget->key() === $key) {
                return $widget;
            }
        }

        $this->fail("No widget named {$key}.");
    }
}
