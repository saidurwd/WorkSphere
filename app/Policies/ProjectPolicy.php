<?php

namespace App\Policies;

use App\Models\User;
use Modules\Projects\Models\Project;

/**
 * Formalises the ad-hoc `authorizeProject()` check that previously lived in
 * ProjectController:111-125.
 */
class ProjectPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Project $project): bool
    {
        return $this->owns($user, $project) || $user->hasPermission('project.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('project.create') || $user->hasRole('super-admin');
    }

    public function update(User $user, Project $project): bool
    {
        return $this->owns($user, $project) || $user->hasPermission('project.update');
    }

    public function delete(User $user, Project $project): bool
    {
        return $user->hasPermission('project.delete') || $user->hasRole('super-admin');
    }

    protected function owns(User $user, Project $project): bool
    {
        return $project->user_id === $user->id || $user->hasRole('super-admin');
    }
}
