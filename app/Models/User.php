<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Modules\Projects\Models\Project;
use Modules\Tasks\Models\Task;

#[Fillable(['name', 'email', 'password', 'avatar', 'status'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    use HasApiTokens;

    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'user_roles')->withTimestamps();
    }

    /**
     * Slugs of every role assigned to the user.
     *
     * @return list<string>
     */
    public function roleSlugs(): array
    {
        if ($this->relationLoaded('roles')) {
            return $this->roles->pluck('slug')->all();
        }

        return $this->roles()->pluck('slug')->all();
    }

    public function hasRole(string $role): bool
    {
        return in_array($role, $this->roleSlugs(), true) || in_array('*', $this->roleSlugs(), true);
    }

    /**
     * @param  list<string>  $roles
     */
    public function hasAnyRole(array $roles): bool
    {
        return array_intersect($roles, $this->roleSlugs()) !== [];
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    public function responsibleTasks(): HasMany
    {
        return $this->hasMany(Task::class, 'responsible_user_id');
    }

    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * Whether the user holds the named permission through any assigned role.
     *
     * A single argument is a permission name resolved against
     * user_roles -> role_permissions -> permissions. Two arguments are a policy
     * ability check and are delegated to Laravel's Gate, so policies added in
     * later phases keep working unchanged.
     *
     * The permission set is resolved once per instance and cached on the instance,
     * so the value is request-scoped: it never leaks between users and it is
     * discarded when the model is re-fetched. Use {@see forgetPermissionCache()}
     * after a role or permission change inside a long-running process.
     */
    public function can($abilities, $arguments = []): bool
    {
        if ($arguments === [] && is_string($abilities)) {
            return $this->hasPermission($abilities);
        }

        return parent::can($abilities, $arguments);
    }

    public function hasPermission(string $permission): bool
    {
        return in_array($permission, $this->permissionNames(), true);
    }

    /**
     * Every permission the user holds, resolved through
     * user_roles -> role_permissions -> permissions.
     *
     * @return list<string>
     */
    public function permissionNames(): array
    {
        if ($this->cachedPermissions !== null) {
            return $this->cachedPermissions;
        }

        return $this->cachedPermissions = $this->roles()
            ->with(['permissions:id,permission_name'])
            ->get()
            ->pluck('permissions')
            ->flatten()
            ->pluck('permission_name')
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    /**
     * Drop the resolved permission set so the next lookup re-queries the database.
     */
    public function forgetPermissionCache(): static
    {
        $this->cachedPermissions = null;

        return $this;
    }

    /**
     * @var list<string>|null
     */
    protected ?array $cachedPermissions = null;
}
