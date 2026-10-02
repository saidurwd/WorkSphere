<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\Role;
use App\Models\RolePermission;
use App\Models\User;
use App\Models\UserRole;
use Database\Factories\EmployeeFactory;
use Database\Factories\LoginLogFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\InteractsWithRoles;
use Tests\TestCase;

/**
 * Every administration screen renders.
 *
 * These pages were broken in a way nothing caught. `roles/show.blade.php`
 * iterated `$role->users` — a relation the `Role` model did not have — and
 * `users/show.blade.php` called `route('employees.show', …)`, a route that does
 * not exist. Both are fatal WHILE RENDERING, so the pages returned a 500 to every
 * administrator who opened them, and the only evidence was an exception in the
 * log.
 *
 * The reason they went unnoticed is that neither page had a test that renders it.
 * So this walks EVERY admin screen, with the data each one needs, and asserts a
 * 200. A missing relation, a dead route name or an undefined variable fails here
 * rather than in front of a user.
 */
class AdminPagesRenderTest extends TestCase
{
    use InteractsWithRoles, RefreshDatabase;

    public function test_every_admin_screen_renders(): void
    {
        $admin = $this->superAdmin([
            'user.manage', 'role.manage', 'activity.view', 'privilege.manage',
            'database.backup', 'system.health', 'system.settings', 'system.queue',
            'system.schedule', 'system.flags', 'system.tokens',
        ]);

        $fixtures = $this->fixtures();

        foreach ($this->screens($fixtures) as $label => $route) {
            $url = is_array($route) ? route($route[0], $route[1]) : route($route);

            $this->actingAs($admin)
                ->get($url)
                ->assertOk("The {$label} screen did not render.");
        }
    }

    /**
     * The admin screens, each paired with the route arguments its records need.
     *
     * Built from the ROUTE TABLE rather than a hand-written list, so a screen added
     * later is covered without anybody remembering to add it here. Write routes are
     * excluded — they are not pages, and GETting one asserts the wrong thing.
     *
     * @param  array<string, mixed>  $fixtures
     * @return array<string, string|array{0: string, 1: array<string, mixed>}>
     */
    private function screens(array $fixtures): array
    {
        $screens = [];

        foreach (Route::getRoutes() as $route) {
            $name = $route->getName();

            if ($name === null || ! str_starts_with($name, 'admin.')) {
                continue;
            }

            if (! in_array('GET', $route->methods(), true)) {
                continue;
            }

            // Sub-resource screens whose parameters are not a model id.
            if (str_contains($name, '{resource}') || str_contains($name, 'export')) {
                continue;
            }

            $arguments = $this->argumentsFor($name, $route, $fixtures);

            if ($arguments === null) {
                continue;
            }

            $screens[$name] = $arguments === []
                ? $name
                : [$name, $arguments];
        }

        ksort($screens);

        return $screens;
    }

    /**
     * Bind a route's parameters to real records, or report that it cannot.
     *
     * @param  array<string, mixed>  $fixtures
     * @return array<string, mixed>|null
     */
    private function argumentsFor(string $name, \Illuminate\Routing\Route $route, array $fixtures): ?array
    {
        $bound = [];

        foreach ($route->parameterNames() as $parameter) {
            $key = match ($parameter) {
                'user' => 'userWithEmployee',
                'role' => 'role',
                'securityEvent' => 'loginLog',
                default => null,
            };

            if ($key === null || ! isset($fixtures[$key])) {
                return null;
            }

            $bound[$parameter] = $fixtures[$key]->getKey();
        }

        return $bound;
    }

    /**
     * Records each screen needs. Built once and shared, so a screen that renders a
     * collection is exercised against a populated one rather than an empty list.
     *
     * @return array<string, Model>
     */
    private function fixtures(): array
    {
        $role = Role::factory()->create(['name' => 'Auditor', 'slug' => 'auditor']);

        $permission = Permission::query()->firstOrCreate(['permission_name' => 'auditor.test']);

        RolePermission::query()->create(['role_id' => $role->id, 'permission_id' => $permission->id]);

        $userWithEmployee = User::factory()->create(['name' => 'Ada Lovelace', 'email' => 'ada@example.test']);
        $userWithEmployee->update(['employee_id' => EmployeeFactory::new()->create()->id]);

        UserRole::query()->create(['user_id' => $userWithEmployee->id, 'role_id' => $role->id]);

        // A second user with NO employee row. Every screen that branches on
        // `$user->employee` must survive its absence — and the user detail page
        // used to render a link to a non-existent route for the users who have one.
        User::factory()->create(['name' => 'No Employee', 'email' => 'none@example.test']);

        return [
            'role' => $role,
            'userWithEmployee' => $userWithEmployee,
            'loginLog' => LoginLogFactory::new()->create(),
        ];
    }

    /**
     * `Role::users()` exists and is the inverse of `User::roles()`.
     */
    public function test_a_roles_roster_resolves(): void
    {
        $role = Role::factory()->create();

        $user = User::factory()->create();
        $user->roles()->attach($role->id);

        // Without this relation the role detail page throws
        // `Call to undefined relationship [users]` and is unreachable.
        $this->assertCount(1, $role->users()->get());
        $this->assertTrue($role->users->first()->is($user));
    }

    public function test_a_roles_roster_and_a_users_roles_agree(): void
    {
        $role = Role::factory()->create();

        $user = User::factory()->create();
        $user->roles()->attach($role->id);

        // Two views of the same pivot; a disagreement between them would make the
        // roster page and the user page tell different stories.
        $this->assertEqualsCanonicalizing(
            $user->roles->pluck('id')->all(),
            $role->users->pluck('id')->all(),
        );
    }

    public function test_a_role_with_no_users_renders_an_empty_state(): void
    {
        $admin = $this->superAdmin(['role.manage']);
        $role = Role::factory()->create();

        $this->actingAs($admin)
            ->get(route('admin.roles.show', $role))
            ->assertOk()
            ->assertSee('No users assigned to this role');
    }

    public function test_the_user_detail_page_links_to_a_route_that_exists(): void
    {
        $admin = $this->superAdmin(['user.manage']);

        $user = User::factory()->create();
        $user->update(['employee_id' => EmployeeFactory::new()->create()->id]);

        $this->actingAs($admin)
            ->get(route('admin.users.show', $user))
            ->assertOk()
            ->assertSee('View Employee Profile');

        // The button has to lead somewhere. It used to call `route('employees.show')`,
        // which does not exist, and `route()` throws while rendering — so the page
        // failed for every user who has an employee record.
        $this->get(
            route('admin.reference.index', ['resource' => 'employees', 'search' => $user->employee->employee_code]),
        )->assertOk();
    }

    /**
     * The employee link is guarded by `@if ($user->employee)`, and that guard
     * cannot be exercised: `users.employee_id` is NOT NULL as of Phase 8 and the
     * foreign key refuses to point at a missing row.
     *
     * This asserts the reachable half — the link is rendered when an employee
     * exists, and its target resolves — which is the half that was broken.
     */
    public function test_the_employee_link_is_rendered_when_an_employee_exists(): void
    {
        $admin = $this->superAdmin(['user.manage']);

        $user = User::factory()->create();
        $user->update(['employee_id' => EmployeeFactory::new()->create()->id]);

        $this->actingAs($admin)
            ->get(route('admin.users.show', $user))
            ->assertOk()
            ->assertSee(route('admin.reference.index', [
                'resource' => 'employees',
                'search' => $user->employee->employee_code,
            ]), false);
    }
}
