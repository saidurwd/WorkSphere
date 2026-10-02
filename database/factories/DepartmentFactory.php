<?php

namespace Database\Factories;

use App\Models\Department;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

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
            // BOTH unique values are random strings rather than Faker pools.
            //
            // `fake()->unique()->bothify('DEP-###')` has a pool of one thousand and
            // the pool is NOT reset between tests — it lives on the Faker instance,
            // which is application-scoped. Once a long suite has created enough
            // departments, the next one throws "Maximum retries of 10000 reached
            // without finding a unique value" and fails a test that had nothing to
            // do with it. A random string has no pool to exhaust, and eight
            // hex-ish characters collide with probability small enough to ignore.
            'department_name' => Str::title(Str::random(8)),
            'department_code' => 'DEP-'.strtoupper(Str::random(8)),
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
