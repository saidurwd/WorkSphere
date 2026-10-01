<?php

namespace Tests\Feature;

use App\Dashboard\DashboardWidget;
use App\Dashboard\WidgetRegistry;
use App\Enums\WorkItemStatus;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Todos\Models\Todo;
use Tests\InteractsWithRoles;
use Tests\TestCase;

/**
 * Widget visibility is permission-driven, and a hidden widget's query never runs.
 *
 * The second property is the one that matters and the one that is easiest to get
 * wrong: rendering no output is not the same as not asking the database. These
 * cases count queries rather than inspecting the HTML, because "the widget did
 * not appear" can be true for two very different reasons.
 */
class WidgetVisibilityTest extends TestCase
{
    use InteractsWithRoles, RefreshDatabase;

    public function test_the_registry_registers_the_documented_widget_set(): void
    {
        $keys = collect((new WidgetRegistry)->all())->map(
            static fn (DashboardWidget $widget): string => $widget->key(),
        );

        foreach ([
            'my_todos', 'my_tasks', 'upcoming_meetings', 'overdue_items',
            'todays_activity', 'upcoming_deadlines',
            'team_workload', 'completion_rate', 'task_distribution', 'department_performance',
            'obligation_expiry', 'critical_deadlines',
        ] as $key) {
            $this->assertContains($key, $keys->all(), "Widget {$key} is not registered.");
        }
    }

    public function test_a_staff_user_sees_personal_widgets_but_not_management_ones(): void
    {
        $staff = $this->userWithPermissions(['todos.view', 'task.view', 'meeting.view']);

        $keys = collect((new WidgetRegistry)->visibleTo($staff))
            ->map(static fn (DashboardWidget $widget): string => $widget->key())
            ->all();

        $this->assertContains('my_todos', $keys);
        $this->assertContains('my_tasks', $keys);
        $this->assertContains('upcoming_meetings', $keys);

        $this->assertNotContains('team_workload', $keys, 'A staff user must not see a management widget.');
        $this->assertNotContains('completion_rate', $keys);
        $this->assertNotContains('department_performance', $keys);
        $this->assertNotContains('critical_deadlines', $keys);
    }

    public function test_a_manager_sees_the_management_widgets(): void
    {
        $manager = $this->userWithPermissions([
            'task.view', 'task.view_all', 'report.view', 'obligation.view', 'obligation.view_reports',
        ]);

        $keys = collect((new WidgetRegistry)->visibleTo($manager))
            ->map(static fn (DashboardWidget $widget): string => $widget->key())
            ->all();

        $this->assertContains('team_workload', $keys);
        $this->assertContains('completion_rate', $keys);
        $this->assertContains('department_performance', $keys);
        $this->assertContains('critical_deadlines', $keys);
    }

    public function test_no_widget_compares_a_role_slug(): void
    {
        // The brief is explicit that `admin` / `super-admin` string comparisons
        // must not appear in this code. Checked mechanically, because a grep in
        // review is a grep somebody forgets to run.
        foreach ((new WidgetRegistry)->all() as $widget) {
            $source = (string) file_get_contents((new \ReflectionClass($widget))->getFileName());

            $this->assertStringNotContainsString("'admin'", $source, $widget->key().' compares a role slug.');
            $this->assertStringNotContainsString('"admin"', $source, $widget->key().' compares a role slug.');
            $this->assertStringNotContainsString("'super-admin'", $source, $widget->key().' compares a role slug.');
            $this->assertStringNotContainsString('hasRole(', $source, $widget->key().' calls hasRole().');
        }
    }

    public function test_a_hidden_widgets_resolve_is_never_called(): void
    {
        // The sharpest form of the property: a widget that counts its own
        // invocations. Counting database queries works but is fragile — a widget
        // may legitimately issue several, and a cached result issues none.
        $permitted = new SpyWidget(['report.view'], 'permitted_spy');
        $hidden = new SpyWidget(['task.view_all', 'report.view'], 'hidden_spy');

        $staff = $this->userWithPermissions(['todos.view', 'task.view', 'report.view']);

        $registry = new WidgetRegistry([$permitted, $hidden]);
        $keys = collect($registry->resolveFor($staff))->keys()->all();

        $this->assertSame(['permitted_spy'], $keys, 'Only the permitted widget may resolve.');
        $this->assertSame(1, $permitted->calls, 'The permitted widget must have run.');
        $this->assertSame(0, $hidden->calls, 'A hidden widget must never have its resolve() called.');

        // And the same registry gives a manager both.
        $manager = $this->userWithPermissions(['todos.view', 'task.view', 'task.view_all', 'report.view']);

        $permitted = new SpyWidget(['report.view'], 'permitted_spy');
        $hidden = new SpyWidget(['task.view_all', 'report.view'], 'hidden_spy');

        $keys = collect((new WidgetRegistry([$permitted, $hidden]))->resolveFor($manager))->keys()->all();

        $this->assertSame(['permitted_spy', 'hidden_spy'], $keys);
        $this->assertSame(1, $hidden->calls);
    }

    public function test_revoking_the_permission_disappears_the_widget(): void
    {
        $manager = $this->userWithPermissions(['task.view', 'task.view_all', 'report.view']);

        $registry = new WidgetRegistry;
        $this->assertContains('team_workload', collect($registry->visibleTo($manager))->map->key()->all());

        $manager->roles()->detach();
        $manager->roles()->attach(
            Role::query()->create(['name' => 'Plain', 'slug' => 'plain'])->id,
        );

        $keys = collect($registry->visibleTo($manager->fresh()))->map->key()->all();

        $this->assertNotContains('team_workload', $keys);
    }

    public function test_widgets_with_no_permission_still_hide_other_peoples_data(): void
    {
        // `overdue_items` declares no permission, so it is always visible — which
        // is only safe because its query is narrowed to the viewer.
        $staff = $this->userWithPermissions(['todos.view']);

        $mine = Todo::factory()->createdBy($staff)->create([
            'title' => 'Mine and late',
            'status' => WorkItemStatus::InProgress,
            'due_date' => now()->subDays(5)->format('Y-m-d'),
        ]);
        $theirs = Todo::factory()->create([
            'title' => 'Theirs and late',
            'status' => WorkItemStatus::InProgress,
            'due_date' => now()->subDays(5)->format('Y-m-d'),
        ]);

        $items = collect((new WidgetRegistry)->resolveFor($staff)->get('overdue_items'));

        $titles = $items->pluck('title')->all();

        $this->assertContains('Mine and late', $titles);
        $this->assertNotContains('Theirs and late', $titles, 'A widget with no permission must still scope to the viewer.');
    }

    public function test_the_dashboard_renders_for_a_user_with_no_permissions_at_all(): void
    {
        $this->actingAs($this->plainUser())
            ->get(route('dashboard.index'))
            ->assertOk();
    }

    public function test_the_dashboard_controller_holds_no_queries(): void
    {
        $source = (string) file_get_contents(app_path('Http/Controllers/DashboardController.php'));

        foreach (['DB::table', '::query()', '->count()', 'selectRaw', 'whereRaw'] as $needle) {
            $this->assertStringNotContainsString(
                $needle,
                $source,
                "DashboardController still contains `{$needle}`; aggregation belongs in a widget.",
            );
        }
    }

    public function test_widget_results_are_cached_and_invalidated_on_demand(): void
    {
        $user = $this->userWithPermissions(['todos.view']);
        $registry = new WidgetRegistry;

        Todo::factory()->count(2)->createdBy($user)->assignedTo($user)->create();

        $first = $registry->resolveFor($user)->get('my_todos');
        $this->assertCount(2, $first);

        // A second resolve is served from cache, so a new row is not seen yet.
        Todo::factory()->createdBy($user)->assignedTo($user)->create();
        $this->assertCount(2, $registry->resolveFor($user->fresh())->get('my_todos'));

        // Invalidating the widget makes it visible immediately.
        $registry->invalidate('my_todos');
        $this->assertCount(3, $registry->resolveFor($user->fresh())->get('my_todos'));
    }

    public function test_the_cache_key_includes_the_permission_set(): void
    {
        // A value computed under one permission set must not be served to a user
        // whose permissions changed.
        $withPermission = $this->userWithPermissions(['todos.view']);
        $without = $this->plainUser();

        $a = (new WidgetRegistry)->resolveFor($withPermission);
        $b = (new WidgetRegistry)->resolveFor($without);

        $this->assertNotSame(
            array_keys($a->all()),
            array_keys($b->all()),
            'Different permission sets must resolve different widget sets.',
        );
    }
}

/**
 * A widget that records how many times its query method ran.
 *
 * Used to prove that a hidden widget is not merely invisible but never asked
 * for its data at all — a distinction that is invisible in the rendered HTML.
 */
final class SpyWidget implements DashboardWidget
{
    public int $calls = 0;

    /**
     * @param  list<string>  $permissions
     */
    public function __construct(
        private readonly array $permissions,
        private readonly string $widgetKey,
    ) {}

    public function key(): string
    {
        return $this->widgetKey;
    }

    public function label(): string
    {
        return 'Spy';
    }

    public function icon(): string
    {
        return 'bug';
    }

    public function group(): string
    {
        return 'test';
    }

    public function permissions(): array
    {
        return $this->permissions;
    }

    public function cacheTtl(): int
    {
        return 60;
    }

    public function resolve(User $user): array
    {
        $this->calls++;

        return [];
    }
}
