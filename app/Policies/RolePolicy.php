<?php

namespace App\Policies;

use App\Models\Role;
use App\Models\User;

/**
 * Role and permission administration is the highest-privilege surface in the
 * application. `role.manage` or super-admin is required for every action.
 */
class RolePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('role.manage') || $user->hasPermission('privilege.manage');
    }

    public function view(User $user, Role $role): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('role.manage') || $user->hasRole('super-admin');
    }

    public function update(User $user, Role $role): bool
    {
        return $this->create($user);
    }

    public function delete(User $user, Role $role): bool
    {
        return $user->hasPermission('role.manage') || $user->hasRole('super-admin');
    }

    public function managePermissions(User $user, Role $role): bool
    {
        return $this->create($user);
    }
}
