<?php

namespace Tests;

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
     */
    protected function userWithPermissions(array $permissions, string $slug = 'test-role'): User
    {
        $user = User::factory()->create();

        $role = Role::query()->create([
            'name' => ucfirst($slug),
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
     * A super-admin bypasses every policy through Gate::before.
     */
    protected function superAdmin(): User
    {
        $user = User::factory()->create();

        $role = Role::query()->firstOrCreate(
            ['slug' => 'super-admin'],
            ['name' => 'Super Administrator'],
        );

        $user->roles()->attach($role->id);

        return $user->fresh();
    }

    /**
     * A user with no role and therefore no permissions at all.
     */
    protected function plainUser(): User
    {
        return User::factory()->create();
    }
}
