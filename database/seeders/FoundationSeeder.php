<?php

namespace Database\Seeders;

use App\Models\ActivityLog;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Location;
use App\Models\Permission;
use App\Models\RolePermission;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
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
            return Department::create([
                'department_name' => $d[0],
                'department_code' => $d[1],
                'status' => 'active',
            ]);
        });

        $locations = collect([
            ['Head Office', 'HQ', '12 Gulshan Avenue', 'Dhaka', 'Bangladesh'],
            ['Uttara Branch', 'UTT', '45 Uttara', 'Dhaka', 'Bangladesh'],
            ['Chittagong Hub', 'CTG', '8 Agrabad', 'Chittagong', 'Bangladesh'],
            ['Remote / WFH', 'REM', null, null, null],
        ])->map(function (array $l) {
            return Location::create([
                'location_name' => $l[0],
                'location_code' => $l[1],
                'address' => $l[2],
                'city' => $l[3],
                'country' => $l[4],
                'status' => 'active',
            ]);
        });

        collect([
            ['Dell Technologies', 'Rahman Ali', 'bd-sales@dell.example', '+8801700000001', 'Dell Tower, Dhaka'],
            ['Apple Reseller Ltd', 'Nusrat Jahan', 'sales@applereseller.example', '+8801700000002', 'Banani, Dhaka'],
            ['HP Bangladesh', 'Karim Uddin', 'contact@hpbangladesh.example', '+8801700000003', 'Motijheel, Dhaka'],
            ['Microsoft Volume', 'Sultana Yesmin', 'vl@microsoft.example', '+8801700000004', 'Online'],
            ['Local IT Wholesale', 'Jamal Hossain', 'trade@localit.example', '+8801700000005', 'Elephant Road, Dhaka'],
        ])->map(function (array $v) {
            return Vendor::create([
                'vendor_name' => $v[0],
                'contact_person' => $v[1],
                'email' => $v[2],
                'phone' => $v[3],
                'address' => $v[4],
                'website' => 'https://'.Str::slug($v[0]).'.example',
                'status' => 'active',
            ]);
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
        // Permissions, RolePermissions & Activity Logs
        // Note: roles/user_roles are owned by Tyro's built-in RBAC.
        // ---------------------------------------------------------------
        $permissionNames = [
            'report.view',
            'task.view', 'task.manage',
            'residence.view', 'residence.manage',
            'user.manage', 'role.manage', 'privilege.manage',
            'invitation.manage', 'system.manage', 'checkpoint.manage',
            'database.backup', 'media.manage',
            'activity.view', 'audit.view',
        ];
        $permissions = collect();
        foreach ($permissionNames as $p) {
            $permissions->push(Permission::create(['permission_name' => $p]));
        }

        // Link permissions to an existing role via the shared `roles` table.
        $roleId = DB::table('roles')->value('id');
        if ($roleId) {
            foreach ($permissions as $permission) {
                RolePermission::create([
                    'role_id' => $roleId,
                    'permission_id' => $permission->id,
                ]);
            }
        }

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
