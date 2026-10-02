<?php

namespace Tests\Feature;

use App\Dashboard\DashboardWidget;
use App\Enums\Role as RoleSlug;
use App\Models\Permission;
use App\Models\Role;
use App\Providers\AppServiceProvider;
use Database\Seeders\ProjectPermissionSeeder;
use FilesystemIterator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Tests\TestCase;

/**
 * The permission catalogue and the code that enforces it are two lists that have
 * to agree, and this is the only thing holding them together.
 *
 * `ProjectPermissionSeeder` seeds an allow-list and then DELETES every permission
 * not on it. So a permission that a gate, a dashboard widget or the navigation
 * config names but the seeder omits can never exist: `hasPermission()` returns
 * false for everyone, permanently, and the screen 403s for every account rather
 * than for the unpermitted ones. That is what happened to `task.view_all` — the
 * workload report, its export and four dashboard widgets were unreachable for
 * every non-super-admin while the whole suite passed, because the tests mint
 * permission rows directly instead of seeding them.
 *
 * These assertions read the enforcement sites rather than restating them, so the
 * next permission added to a gate without a matching seeder entry fails here.
 */
class PermissionCatalogTest extends TestCase
{
    use RefreshDatabase;

    /**
     * The gate requirement that names a ROLE rather than a permission.
     *
     * `defineGate()` reads `@super-admin` as "super-admin only" and checks the
     * role. A permission there would be as broad as whatever grants permissions,
     * which is the whole reason those gates are role-gated. It is an absence, not
     * an omission, so it is not required to be in the catalogue.
     */
    private const ROLE_GATED = '@super-admin';

    // ---- The seeder creates a usable set -------------------------------------

    public function test_the_seeder_creates_the_roles_it_grants_to(): void
    {
        // The grant loop resolves roles by slug, and nothing else writes to
        // `roles`. On a freshly migrated database there are no rows to match, so
        // every permission was seeded but assigned to nobody.
        $this->assertSame(0, Role::query()->count());

        $this->seed(ProjectPermissionSeeder::class);

        foreach (RoleSlug::cases() as $role) {
            $this->assertDatabaseHas('roles', ['slug' => $role->value]);
        }
    }

    public function test_the_seeder_assigns_every_permission_to_the_admin_roles(): void
    {
        $this->seed(ProjectPermissionSeeder::class);

        $expected = Permission::query()->count();
        $this->assertGreaterThan(0, $expected);

        foreach (config('authorization.admin_roles', []) as $slug) {
            $this->assertSame(
                $expected,
                Role::query()->where('slug', $slug)->firstOrFail()->permissions()->count(),
                "The `{$slug}` role does not hold every seeded permission.",
            );
        }
    }

    public function test_running_the_seeder_twice_does_not_duplicate_the_grants(): void
    {
        $this->seed(ProjectPermissionSeeder::class);
        $after = DB::table('role_permissions')->count();

        $this->seed(ProjectPermissionSeeder::class);

        $distinct = DB::table('role_permissions')
            ->distinct()
            ->get(['role_id', 'permission_id'])
            ->count();

        $this->assertSame($after, DB::table('role_permissions')->count());
        $this->assertSame($after, $distinct);
    }

    public function test_the_seeder_prunes_a_permission_that_leaves_the_catalogue(): void
    {
        $this->seed(ProjectPermissionSeeder::class);

        Permission::query()->create(['permission_name' => 'retired.permission']);

        $this->assertDatabaseHas('permissions', ['permission_name' => 'retired.permission']);

        $this->seed(ProjectPermissionSeeder::class);

        // The pruning is the seeder's contract, and it is what makes the
        // allow-list exhaustive rather than a floor.
        $this->assertDatabaseMissing('permissions', ['permission_name' => 'retired.permission']);
    }

    // ---- The catalogue covers every enforcement site -------------------------

    public function test_every_permission_a_gate_requires_is_in_the_catalogue(): void
    {
        $required = array_values(array_diff(
            $this->seededPermissions(),
            [self::ROLE_GATED],
        ));

        $this->assertNotEmpty($required);

        $this->assertNoUnseededPermissions(
            $required,
            'These gate requirements name permissions the seeder does not create, so no account can pass them:',
        );
    }

    public function test_every_permission_the_navigation_names_is_in_the_catalogue(): void
    {
        $named = $this->navigationPermissions();

        $this->assertNotEmpty($named);

        // A nav item gated on a permission that cannot exist is hidden from
        // everybody, which reads as a missing feature rather than a permissions
        // bug — the menu is supposed to be a contract, not a suggestion.
        $this->assertNoUnseededPermissions(
            $named,
            'The navigation names permissions the seeder does not create, so these items never appear:',
        );
    }

    public function test_every_dashboard_widget_permission_is_in_the_catalogue(): void
    {
        $named = [];

        foreach ($this->widgets() as $widget) {
            $named = [...$named, ...$widget->permissions()];
        }

        $named = array_values(array_unique($named));
        $this->assertNotEmpty($named);

        $this->assertNoUnseededPermissions(
            $named,
            'These dashboard widgets require permissions the seeder does not create, so they never render:',
        );
    }

    public function test_every_permission_a_policy_requires_is_in_the_catalogue(): void
    {
        $named = [];

        foreach ($this->policyFiles() as $file) {
            $named = [...$named, ...$this->permissionStringsIn($file)];
        }

        $named = array_values(array_unique($named));
        $this->assertNotEmpty($named);

        $this->assertNoUnseededPermissions(
            $named,
            'These policies check permissions the seeder does not create, so the check always denies:',
        );
    }

    public function test_every_permission_a_controller_requires_is_in_the_catalogue(): void
    {
        $named = [];

        foreach ($this->controllerFiles() as $file) {
            $named = [...$named, ...$this->permissionStringsIn($file)];
        }

        $named = array_values(array_unique($named));
        $this->assertNotEmpty($named);

        $this->assertNoUnseededPermissions(
            $named,
            'These controllers check permissions the seeder does not create, so the check always denies:',
        );
    }

    // ---- Helpers ------------------------------------------------------------

    /**
     * @param  list<string>  $permissions
     */
    private function assertNoUnseededPermissions(array $permissions, string $message): void
    {
        $this->seed(ProjectPermissionSeeder::class);

        $missing = array_values(array_diff(
            $permissions,
            Permission::query()->pluck('permission_name')->all(),
        ));

        $this->assertSame([], $missing, $message."\n".implode("\n", $missing));
    }

    /**
     * Gate requirements, minus the one that names a role.
     *
     * @return list<string>
     */
    private function seededPermissions(): array
    {
        return (new AppServiceProvider($this->app))->requiredPermissions();
    }

    /**
     * @return list<string>
     */
    private function navigationPermissions(): array
    {
        $walk = function (array $nodes) use (&$walk): array {
            $found = [];

            foreach ($nodes as $node) {
                $found[] = (string) ($node['permission'] ?? '');
                $found = [...$found, ...array_map(strval(...), (array) ($node['permissions'] ?? []))];
                $found = [...$found, ...$walk((array) ($node['children'] ?? []))];
            }

            return array_values(array_filter($found));
        };

        return array_values(array_unique($walk((array) config('navigation.menu', []))));
    }

    /**
     * @return list<DashboardWidget>
     */
    private function widgets(): array
    {
        $widgets = [];

        foreach ($this->files([
            app_path('Dashboard/Widgets'),
            ...array_map(
                fn (string $module): string => $module.'/app/Dashboard/Widgets',
                glob(base_path('Modules/*')) ?: [],
            ),
        ]) as $file) {
            $class = $this->classIn($file, 'App\Dashboard\Widgets');

            if ($class !== null && is_subclass_of($class, DashboardWidget::class)) {
                $widgets[] = new $class;
            }
        }

        return $widgets;
    }

    /**
     * @return list<string>
     */
    private function policyFiles(): array
    {
        return $this->files([
            app_path('Policies'),
            ...array_map(
                fn (string $module): string => $module.'/app/Policies',
                glob(base_path('Modules/*')) ?: [],
            ),
        ]);
    }

    /**
     * @return list<string>
     */
    private function controllerFiles(): array
    {
        return $this->files([
            // RecursiveIterator, not `glob()`: PHP's glob has no `**`, so the
            // wildcard silently matched the two levels below the base and nothing
            // deeper — and an empty file list makes the assertion vacuous.
            app_path('Http/Controllers'),
            ...array_map(
                fn (string $module): string => $module.'/app/Http/Controllers',
                glob(base_path('Modules/*')) ?: [],
            ),
        ]);
    }

    /**
     * The permission-shaped strings a file checks for.
     *
     * `hasPermission('x')` and `can('x')` are the two forms that resolve a
     * permission; `authorize('x')` is a gate ability and is covered by the gate
     * assertion instead. The shape is `namespace.verb`, which is what every
     * permission in this application looks like.
     *
     * @return list<string>
     */
    private function permissionStringsIn(string $file): array
    {
        $source = (string) file_get_contents($file);

        preg_match_all("/(?:hasPermission|can)\(\s*'([a-z_]+\.[a-z_]+)'/", $source, $matches);

        return $matches[1];
    }

    /**
     * PHP files under the given directories, recursively.
     *
     * @param  list<string>  $directories
     * @return list<string>
     */
    private function files(array $directories): array
    {
        $files = [];

        foreach ($directories as $directory) {
            if (! is_dir($directory)) {
                continue;
            }

            $paths = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS),
            );

            foreach ($paths as $path) {
                if ($path->isFile() && $path->getExtension() === 'php') {
                    $files[] = $path->getPathname();
                }
            }
        }

        return array_values(array_unique($files));
    }

    private function classIn(string $file, string $namespace): ?string
    {
        $class = $namespace.'\\'.basename($file, '.php');

        return class_exists($class) ? $class : null;
    }
}
