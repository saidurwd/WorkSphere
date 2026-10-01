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

    /**
     * Class-scoped variants.
     *
     * `authorize('update', User::class)` passes the CLASS rather than a record, so
     * Laravel calls the ability with the user alone. Without these the Gate reaches
     * `update()`, which needs a model, and dies with an ArgumentCountError instead
     * of denying — a 500 where a 403 belongs.
     */
    public function updateAny(User $user): bool
    {
        return $this->update($user, $user);
    }

    public function deleteAny(User $user): bool
    {
        return $this->delete($user, $user);
    }

    public function manageRolesAny(User $user): bool
    {
        return $this->manageRoles($user, $user);
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
