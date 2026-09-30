<?php

namespace Database\Seeders;

use App\Models\ActivityLog;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Location;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class FoundationSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $faker = fake();

        // ---------------------------------------------------------------
        // Foundation: Departments, Locations, Vendors
        // ---------------------------------------------------------------
        $departments = collect([
            ['Engineering', 'ENG'],
            ['Human Resources', 'HR'],
            ['Finance', 'FIN'],
            ['Sales & Marketing', 'SAL'],
            ['Operations', 'OPS'],
        ])->map(function (array $d) {
            return Department::firstOrCreate(
                ['department_code' => $d[1]],
                ['department_name' => $d[0], 'status' => 'active']
            );
        });

        $locations = collect([
            ['Head Office', 'HQ', '12 Gulshan Avenue', 'Dhaka', 'Bangladesh'],
            ['Uttara Branch', 'UTT', '45 Uttara', 'Dhaka', 'Bangladesh'],
            ['Chittagong Hub', 'CTG', '8 Agrabad', 'Chittagong', 'Bangladesh'],
            ['Remote / WFH', 'REM', null, null, null],
        ])->map(function (array $l) {
            return Location::firstOrCreate(
                ['location_code' => $l[1]],
                ['location_name' => $l[0], 'address' => $l[2], 'city' => $l[3], 'country' => $l[4], 'status' => 'active']
            );
        });

        $vendorNames = [
            'Dell Technologies', 'Apple Reseller Ltd', 'HP Bangladesh', 'Microsoft Volume', 'Local IT Wholesale',
            'Cisco Systems', 'IBM Bangladesh', 'Oracle Bangladesh', 'SAP Bangladesh', 'Lenovo Solutions',
            'Sony Bangladesh', 'Samsung Electronics', 'LG Bangladesh', 'Asus Tech', 'Acer Service',
            'Nokia Networks', 'Huawei Technologies', 'Xiaomi Services', 'Logitech Bangladesh', 'Epson Bangladesh',
        ];

        collect($vendorNames)->map(function (string $vendorName, int $index) use ($faker) {
            return Vendor::firstOrCreate(
                ['vendor_name' => $vendorName],
                [
                    'contact_person' => $faker->name(),
                    'email' => strtolower(Str::slug($vendorName)).'@example.com',
                    'phone' => '+88017'.str_pad((string) $faker->numberBetween(10000000, 99999999), 8, '0', STR_PAD_LEFT),
                    'address' => $faker->address(),
                    'website' => 'https://'.Str::slug($vendorName).'.example',
                    'status' => $faker->randomElement(['active', 'active', 'active', 'inactive']),
                ]
            );
        });

        // ---------------------------------------------------------------
        // Employees
        // ---------------------------------------------------------------
        $employees = collect();
        for ($i = 1; $i <= 30; $i++) {
            $employees->push(Employee::create([
                'employee_code' => 'EMP-'.str_pad((string) $i, 4, '0', STR_PAD_LEFT),
                'employee_name' => $faker->name(),
                'email' => $faker->unique()->safeEmail(),
                'phone' => '+88017'.str_pad((string) $faker->numberBetween(10000000, 99999999), 8, '0', STR_PAD_LEFT),
                'designation' => $faker->jobTitle(),
                'department_id' => $departments->random()->id,
                'location_id' => $locations->random()->id,
                'joining_date' => $faker->dateTimeBetween('-5 years', 'now')->format('Y-m-d'),
                'status' => $faker->randomElement(['active', 'active', 'active', 'inactive']),
            ]));
        }

        // Assign a head of department (an employee) to each department now
        // that employees exist.
        $departments->each(function (Department $department) use ($employees) {
            $department->update([
                'head_of_department_id' => $employees->random()->id,
            ]);
        });

        // ---------------------------------------------------------------
        // Activity Logs
        // ---------------------------------------------------------------
        for ($i = 0; $i < 20; $i++) {
            ActivityLog::create([
                'user_id' => User::inRandomOrder()->value('id'),
                'module_name' => $faker->randomElement(['tasks', 'task_projects', 'meetings', 'obligations']),
                'record_id' => fake()->numberBetween(1, 40),
                'action' => $faker->randomElement(['created', 'updated', 'deleted', 'viewed']),
                'old_value' => null,
                'new_value' => json_encode(['sample' => $faker->word()]),
                'ip_address' => $faker->ipv4(),
                'created_at' => $faker->dateTimeBetween('-3 months', 'now'),
            ]);
        }
    }
}
