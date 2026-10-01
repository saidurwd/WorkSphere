<?php

namespace App\Policies;

use App\Models\User;

/**
 * Guards the admin identity surface. A user may always manage their own account;
 * anyone else requires `user.manage`.
 */
class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('user.manage') || $user->hasRole('super-admin');
    }

    public function view(User $user, User $model): bool
    {
        return $this->isSelf($user, $model) || $user->hasPermission('user.manage');
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('user.manage') || $user->hasRole('super-admin');
    }

    public function update(User $user, User $model): bool
    {
        return $this->isSelf($user, $model)
            || $user->hasPermission('user.manage')
            || $user->hasRole('super-admin');
    }

    public function delete(User $user, User $model): bool
    {
        return $user->hasPermission('user.manage') || $user->hasRole('super-admin');
    }

    /**
     * Role assignment is never a self-service action, even for one's own account.
     */
    public function manageRoles(User $user, User $model): bool
    {
        return $user->hasPermission('user.manage') || $user->hasRole('super-admin');
    }

    protected function isSelf(User $user, User $model): bool
    {
        return $user->getKey() === $model->getKey();
    }
}
