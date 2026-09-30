<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

#[Fillable(['name', 'email', 'password', 'avatar'])]
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
}
