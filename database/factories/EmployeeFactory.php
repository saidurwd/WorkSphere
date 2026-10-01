<?php

namespace Database\Factories;

use App\Models\Employee;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Employee>
 */
class EmployeeFactory extends Factory
{
    protected $model = Employee::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'employee_code' => strtoupper(fake()->unique()->bothify('EMP-####')),
            'employee_name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->optional()->numerify('+8801#########'),
            'designation' => fake()->optional()->jobTitle(),
            'joining_date' => fake()->optional()->dateTimeBetween('-5 years', 'now')->format('Y-m-d'),
            'status' => 'active',
        ];
    }
}
