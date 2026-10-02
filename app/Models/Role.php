<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Role extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
    ];

    public function rolePermissions(): HasMany
    {
        return $this->hasMany(RolePermission::class);
    }

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'role_permissions');
    }

    public function userRoles(): HasMany
    {
        return $this->hasMany(UserRole::class);
    }

    /**
     * The users holding this role.
     *
     * The inverse of `User::roles()` and defined the same way — through
     * `user_roles`, `withTimestamps()` — so a role's roster and a user's roles can
     * never disagree about what the pivot holds.
     *
     * This relation was MISSING, and `resources/views/admin/roles/show.blade.php`
     * iterates `$role->users` twice. Every visit to a role's detail page therefore
     * threw `Call to undefined relationship [users]`, which is not a layout
     * problem but an unreachable screen: the page had never loaded.
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_roles')->withTimestamps();
    }
}
