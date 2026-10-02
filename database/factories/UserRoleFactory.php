<?php

namespace Database\Factories;

use App\Models\Role;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<UserRole>
 *
 * The (user_id, role_id) pair is unique — that is what makes role assignment
 * idempotent — so both parents are fresh.
 */
class UserRoleFactory extends Factory
{
    protected $model = UserRole::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'role_id' => Role::factory(),
        ];
    }
}
