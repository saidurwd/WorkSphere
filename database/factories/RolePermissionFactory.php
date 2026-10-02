<?php

namespace Database\Factories;

use App\Models\Permission;
use App\Models\Role;
use App\Models\RolePermission;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RolePermission>
 *
 * (role_id, permission_id) is unique, so each row is built from freshly created
 * parents rather than from constants that a second `create()` would collide on.
 */
class RolePermissionFactory extends Factory
{
    protected $model = RolePermission::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'role_id' => Role::factory(),
            'permission_id' => Permission::factory(),
        ];
    }
}
