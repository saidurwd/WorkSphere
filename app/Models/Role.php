<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

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
     * Replace this role's permissions with an exact set.
     *
     * EXACT, not additive. An empty array therefore means "this role has no
     * permissions", which is a legitimate thing an operator can do and must not be
     * silently ignored — otherwise unticking every box appears to work and the role
     * keeps its grants.
     *
     * Ids are intersected with the permissions that actually exist rather than
     * trusted. The form validates `exists:permissions,id`, but a stale tab
     * submitted against a deleted permission would otherwise be a foreign-key
     * violation and a 500 — a save that half-succeeds is worse than one that
     * reports what it could not do.
     *
     * @param  list<int|string>  $permissionIds
     * @return int Number of permissions attached.
     */
    public function syncPermissions(array $permissionIds): int
    {
        $existing = Permission::query()
            ->whereIn('id', array_map(strval(...), $permissionIds))
            ->pluck('id')
            ->all();

        DB::transaction(function () use ($existing): void {
            $this->rolePermissions()->delete();

            if ($existing === []) {
                return;
            }

            $this->rolePermissions()->createMany(
                array_map(fn (int|string $id): array => ['permission_id' => $id], $existing),
            );
        });

        return count($existing);
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
