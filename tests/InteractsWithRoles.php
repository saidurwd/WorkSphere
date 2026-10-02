<?php

namespace Tests;

use App\Models\Employee;
use App\Models\Permission;
use App\Models\Role;
use App\Models\RolePermission;
use App\Models\User;

/**
 * Shared helpers for building a user with a specific permission set. The RBAC
 * tables are hand-wired in tests rather than seeded, so each test states exactly
 * which permissions its actor holds.
 */
trait InteractsWithRoles
{
    /**
     * @param  list<string>  $permissions
     * @param  string|null  $slug  Reuse an existing role's slug instead of creating one.
     * @param  User|null  $attachTo  Grant to an existing user rather than creating one.
     */
    protected function userWithPermissions(array $permissions, ?string $slug = null, ?User $attachTo = null): User
    {
        // Auto-unique so a test may call this more than once without colliding on
        // the roles.slug unique index.
        static $counter = 0;
        $slug ??= 'test-role-'.(++$counter);

        $user = $attachTo ?? User::factory()->create();

        $role = Role::query()->create([
            'name' => ucfirst(str_replace('-', ' ', $slug)),
            'slug' => $slug,
        ]);

        foreach ($permissions as $permission) {
            $model = Permission::query()->firstOrCreate(['permission_name' => $permission]);

            RolePermission::query()->firstOrCreate([
                'role_id' => $role->id,
                'permission_id' => $model->id,
            ]);
        }

        $user->roles()->attach($role->id);
        $user->forgetPermissionCache();

        return $user->fresh();
    }

    /**
     * A super-admin bypasses every policy through `Gate::before`.
     *
     * Note what that does NOT do: it grants no permissions. `hasPermission()` reads
     * `role_permissions`, so a super-admin with an empty permission set still
     * passes every policy check while failing every hand-written
     * `if (! $user->hasPermission(...))` narrowing in a list query. Pass
     * `$permissions` when the test needs the list path to open too.
     *
     * @param  list<string>  $permissions
     */
    protected function superAdmin(array $permissions = []): User
    {
        $user = User::factory()->create();

        $role = Role::query()->firstOrCreate(
            ['slug' => 'super-admin'],
            ['name' => 'Super Administrator'],
        );

        $user->roles()->attach($role->id);

        foreach ($permissions as $permission) {
            $model = Permission::query()->firstOrCreate(['permission_name' => $permission]);

            RolePermission::query()->firstOrCreate([
                'role_id' => $role->id,
                'permission_id' => $model->id,
            ]);
        }

        $user->forgetPermissionCache();

        return $user->fresh();
    }

    /**
     * A user with no role and therefore no permissions at all.
     */
    protected function plainUser(): User
    {
        return User::factory()->create();
    }

    /**
     * A user belonging to a department.
     *
     * `users` has no `department_id`; the department is reached through
     * `employees`. Anything that scopes visibility by department — `TodoScope`'s
     * Team clause, for one — reads it from there, so a helper that skipped the
     * employee row would test the null branch and pass for the wrong reason.
     */
    protected function userInDepartment(?int $departmentId): User
    {
        $user = $this->plainUser();

        $employee = Employee::factory()->create(['department_id' => $departmentId]);

        $user->update(['employee_id' => $employee->id]);

        return $user->fresh();
    }

    /**
     * A user holding the `admin` role slug, so they pass the `admin` middleware,
     * but holding only the listed permissions. This is how a test proves a policy
     * denies someone who has already passed the middleware.
     *
     * @param  list<string>  $permissions
     */
    protected function adminWithout(array $permissions = [], bool $grantAll = false): User
    {
        $user = User::factory()->create();

        $role = Role::query()->firstOrCreate(
            ['slug' => 'admin'],
            ['name' => 'Administrator'],
        );

        $granted = $grantAll
            ? Permission::query()->pluck('permission_name')->all()
            : $permissions;

        foreach ($granted as $permission) {
            $model = Permission::query()->firstOrCreate(['permission_name' => $permission]);

            RolePermission::query()->firstOrCreate([
                'role_id' => $role->id,
                'permission_id' => $model->id,
            ]);
        }

        $user->roles()->attach($role->id);

        return $user->fresh();
    }
}
