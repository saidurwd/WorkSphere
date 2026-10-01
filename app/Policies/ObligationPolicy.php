<?php

namespace App\Policies;

use App\Models\User;
use Modules\Obligations\Models\Obligation;

/**
 * Obligations previously had NO object-level authorization on show/edit/update/
 * destroy — only a list-level filter in index(). This policy applies the same
 * rule to a single record: the owner, an active responsibility holder, or a
 * super-admin may view; the owner, an approver/reviewer, or a super-admin may
 * change it.
 */
class ObligationPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Obligation $obligation): bool
    {
        return $this->isVisibleTo($user, $obligation) || $user->hasPermission('obligation.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('obligation.create') || $user->hasRole('super-admin');
    }

    public function update(User $user, Obligation $obligation): bool
    {
        return $this->owns($user, $obligation) || $user->hasPermission('obligation.update');
    }

    public function delete(User $user, Obligation $obligation): bool
    {
        return $this->owns($user, $obligation) || $user->hasPermission('obligation.delete');
    }

    public function approve(User $user, Obligation $obligation): bool
    {
        return $obligation->approver_user_id === $user->id
            || $user->hasPermission('obligation.approve')
            || $user->hasRole('super-admin');
    }

    public function assign(User $user, Obligation $obligation): bool
    {
        return $this->owns($user, $obligation) || $user->hasPermission('obligation.assign');
    }

    public function renew(User $user, Obligation $obligation): bool
    {
        return $this->owns($user, $obligation) || $user->hasPermission('obligation.renew');
    }

    /**
     * Documents hang off the obligation and are governed by the same owner rule
     * plus a dedicated document permission.
     */
    public function manageDocuments(User $user, Obligation $obligation): bool
    {
        return $this->owns($user, $obligation) || $user->hasPermission('obligation.manage_documents');
    }

    protected function owns(User $user, Obligation $obligation): bool
    {
        return $obligation->owner_user_id === $user->id || $user->hasRole('super-admin');
    }

    protected function isVisibleTo(User $user, Obligation $obligation): bool
    {
        return $this->owns($user, $obligation)
            || $obligation->responsibilities()
                ->where('user_id', $user->id)
                ->where('active', true)
                ->exists()
            || $user->hasRole('super-admin');
    }
}
