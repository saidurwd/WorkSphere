<?php

namespace Database\Factories;

use App\Models\Permission;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Permission>
 *
 * The `permission_name` unique index means a factory value has to be distinct per
 * row. `fake()->unique()` resets between factory instances and collides across
 * them, so the value is derived from a fresh UUID — which also makes a failing
 * test's fixture identifiable in the seed data.
 */
class PermissionFactory extends Factory
{
    protected $model = Permission::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'permission_name' => 'test.permission.'.Str::lower(Str::random(12)),
        ];
    }
}
