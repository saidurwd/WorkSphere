<?php

namespace Database\Factories;

use App\Models\Department;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Department>
 */
class DepartmentFactory extends Factory
{
    protected $model = Department::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'department_name' => fake()->unique()->words(2, true),
            'department_code' => strtoupper(fake()->unique()->bothify('DEP-###')),
            // The column the model still lists in $fillable was dropped by
            // 2026_07_09_070842 in favour of head_of_department_id.
            'head_of_department_id' => null,
            'status' => 'active',
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (): array => ['status' => 'inactive']);
    }
}
