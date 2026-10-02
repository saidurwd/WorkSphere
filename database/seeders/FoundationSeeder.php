<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\Employee;
use App\Models\Location;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Modules\Obligations\Models\Vendor;

/**
 * The organisation every other seeder builds on.
 *
 * Departments, locations, vendors and the employee roster. Nothing here is random:
 * the names, designations and reporting lines are fixed, so a demonstration shows
 * the same organisation every time, two people can be handed the same login, and a
 * screenshot taken today still matches the data tomorrow.
 *
 * The roster is {@see self::ROSTER}. It is `public` because `UserSeeder` derives
 * one account per member from it and the demo emails have to resolve back to the
 * same people.
 *
 * People here are fictional and no contact detail is a real one: addresses use
 * `.test`, a domain reserved for this, and phone numbers are derived from the
 * roster index rather than generated.
 */
class FoundationSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * The seeded organisation, one row per person.
     *
     * Columns: name, designation, department code, location code, role slug.
     *
     * The role column is what makes a demonstration navigable: a super-admin, two
     * administrators, a manager per department, senior contributors, junior
     * contributors and a few read-only auditors. Every seeded account therefore
     * has a visibly different slice of the application rather than the same one
     * for all of them.
     *
     * The last two entries are on long-term leave and no longer with the company
     * respectively, so the active/inactive filters have rows to exclude.
     *
     * @var list<array{0: string, 1: string, 2: string, 3: string, 4: string}>
     */
    public const ROSTER = [
        // Information Technology — owns the platform and the accounts.
        ['Nabila Rahman', 'Super Administrator', 'IT', 'HQ', 'super-admin'],
        ['Jalal Uddin', 'IT Director', 'IT', 'HQ', 'admin'],
        ['Samina Parveen', 'Systems Administrator', 'IT', 'HQ', 'admin'],
        ['Nahid Hasan', 'Network Engineer', 'IT', 'GUL', 'user'],
        ['Shahriar Alam', 'Database Administrator', 'IT', 'HQ', 'user'],
        ['Tanjina Akter', 'Systems Analyst', 'IT', 'BAN', 'user'],
        ['Rezwan Karim', 'Help Desk Administrator', 'IT', 'HQ', 'employee'],
        ['Sabiha Noor', 'IT Support Specialist', 'IT', 'REM', 'employee'],

        // Engineering — the delivery teams.
        ['Arif Hossain', 'Head of Engineering', 'ENG', 'HQ', 'manager'],
        ['Imran Kabir', 'Engineering Manager', 'ENG', 'GUL', 'manager'],
        ['Nusrat Jahan', 'Senior Backend Engineer', 'ENG', 'HQ', 'user'],
        ['Sadia Islam', 'DevOps Engineer', 'ENG', 'REM', 'user'],
        ['Maliha Chowdhury', 'Mobile Engineer', 'ENG', 'GUL', 'user'],
        ['Tanvir Rahman', 'Frontend Engineer', 'ENG', 'REM', 'employee'],
        ['Rafiqul Hasan', 'QA Automation Engineer', 'ENG', 'HQ', 'employee'],

        // Quality assurance.
        ['Shamsher Alam', 'Head of Quality', 'QA', 'HQ', 'manager'],
        ['Ruma Yasmin', 'Quality Assurance Lead', 'QA', 'HQ', 'user'],
        ['Aslam Mia', 'Test Engineer', 'QA', 'GUL', 'employee'],
        ['Nupur Akter', 'Quality Analyst', 'QA', 'CTG', 'employee'],

        // Research and development.
        ['Debasish Roy', 'Head of Research', 'RND', 'HQ', 'manager'],
        ['Sunanda Das', 'Research Scientist', 'RND', 'LON', 'user'],
        ['Arifuzzaman Khan', 'Data Scientist', 'RND', 'REM', 'user'],
        ['Mehnaz Rahman', 'Research Associate', 'RND', 'HQ', 'employee'],

        // Human resources.
        ['Sharmin Akter', 'Head of Human Resources', 'HR', 'HQ', 'manager'],
        ['Farhana Kabir', 'HR Business Partner', 'HR', 'HQ', 'user'],
        ['Rina Sultana', 'HR Administrator', 'HR', 'HQ', 'user'],
        ['Nasir Uddin', 'Recruitment Specialist', 'HR', 'BAN', 'employee'],
        ['Sabrina Yasmin', 'Compensation Analyst', 'HR', 'HQ', 'employee'],
        ['Habiburul Haque', 'Learning and Development Coordinator', 'HR', 'REM', 'employee'],

        // Finance — including the read-only auditors.
        ['Kamrul Hasan', 'Head of Finance', 'FIN', 'HQ', 'manager'],
        ['Sumaiya Akhter', 'Financial Controller', 'FIN', 'HQ', 'user'],
        ['Al Amin Chowdhury', 'Accounts Manager', 'FIN', 'HQ', 'user'],
        ['Shafiq Alam', 'Treasury Officer', 'FIN', 'GUL', 'employee'],
        ['Zarin Tasnim', 'Tax Analyst', 'FIN', 'HQ', 'employee'],
        ['Shafiqul Karim', 'Internal Audit Manager', 'FIN', 'HQ', 'viewer'],
        ['Nowshin Akter', 'Audit Associate', 'FIN', 'HQ', 'viewer'],

        // Sales and marketing.
        ['Nafisa Rahman', 'Head of Sales', 'SAL', 'HQ', 'manager'],
        ['Tanvir Hasan', 'Regional Sales Manager', 'SAL', 'CTG', 'manager'],
        ['Suborna Khatun', 'Marketing Lead', 'SAL', 'GUL', 'user'],
        ['Asif Iqbal', 'Business Development Executive', 'SAL', 'CTG', 'employee'],
        ['Mitu Akter', 'Digital Marketing Specialist', 'SAL', 'REM', 'employee'],
        ['Rakib Chowdhury', 'Account Executive', 'SAL', 'SYL', 'employee'],

        // Operations.
        ['Mizanur Rahman', 'Head of Operations', 'OPS', 'HQ', 'manager'],
        ['Saiful Islam', 'Operations Manager', 'OPS', 'CTG', 'user'],
        ['Ruma Aktar', 'Logistics Coordinator', 'OPS', 'SYL', 'employee'],
        ['Anisur Rahman', 'Facilities Executive', 'OPS', 'HQ', 'employee'],
        ['Farhana Akter', 'Vendor Coordinator', 'OPS', 'HQ', 'employee'],

        // Procurement.
        ['Zahid Hasan', 'Head of Procurement', 'PRC', 'HQ', 'manager'],
        ['Shirin Akter', 'Procurement Specialist', 'PRC', 'HQ', 'user'],
        ['Raihan Chowdhury', 'Sourcing Analyst', 'PRC', 'GUL', 'employee'],
        ['Sadia Afrin', 'Tender Coordinator', 'PRC', 'HQ', 'employee'],

        // Legal and compliance.
        ['Saifur Rahman', 'Head of Legal and Compliance', 'LGL', 'HQ', 'manager'],
        ['Farhana Rahman', 'Compliance Officer', 'LGL', 'HQ', 'user'],
        ['Ziaul Haque', 'Contracts Administrator', 'LGL', 'HQ', 'employee'],
        ['Mahfuza Khatun', 'Company Secretary', 'LGL', 'HQ', 'viewer'],

        // Customer support.
        ['Masud Rana', 'Head of Customer Support', 'SUP', 'HQ', 'manager'],
        ['Arifin Sultana', 'Support Team Lead', 'SUP', 'BAN', 'user'],
        ['Sharmeen Akter', 'Customer Success Specialist', 'SUP', 'REM', 'user'],
        ['Nazmul Haque', 'Support Engineer', 'SUP', 'CTG', 'employee'],
        ['Habibur Rahman', 'Customer Support Agent', 'SUP', 'HQ', 'employee'],

        // Administration.
        ['Rafiqul Islam', 'Head of Administration', 'ADM', 'HQ', 'manager'],
        ['Momtaz Begum', 'Executive Assistant', 'ADM', 'HQ', 'user'],
        ['Khadija Rahman', 'Office Manager', 'ADM', 'HQ', 'user'],
        ['Jasim Uddin', 'Facilities Coordinator', 'ADM', 'GAZ', 'employee'],
    ];

    /**
     * Accounts that are memorable on purpose.
     *
     * A demonstration needs credentials someone can type from a slide, and every
     * other account follows the address pattern derived from the person's name.
     * Exactly one account per role is aliased, so each role is reachable by typing
     * its name.
     *
     * @var array<string, string> Role slug => email address.
     */
    public const DEMO_EMAILS = [
        'super-admin' => 'superadmin@worksphere.test',
        'admin' => 'admin@worksphere.test',
        'manager' => 'manager@worksphere.test',
        'user' => 'user@worksphere.test',
        'employee' => 'employee@worksphere.test',
        'viewer' => 'viewer@worksphere.test',
    ];

    /**
     * @var list<array{0: string, 1: string, 2: string, 3: string, 4: string|null, 5: string|null}>
     */
    private const DEPARTMENTS = [
        ['Information Technology', 'IT'],
        ['Engineering', 'ENG'],
        ['Quality Assurance', 'QA'],
        ['Research and Development', 'RND'],
        ['Human Resources', 'HR'],
        ['Finance', 'FIN'],
        ['Sales and Marketing', 'SAL'],
        ['Operations', 'OPS'],
        ['Procurement', 'PRC'],
        ['Legal and Compliance', 'LGL'],
        ['Customer Support', 'SUP'],
        ['Administration', 'ADM'],
    ];

    /**
     * @var list<array{0: string, 1: string, 2: string|null, 3: string|null, 4: string|null}>
     */
    private const LOCATIONS = [
        ['Head Office', 'HQ', '14 Gulshan Avenue', 'Dhaka', 'Bangladesh'],
        ['Gulshan Office', 'GUL', '211 Gulshan South', 'Dhaka', 'Bangladesh'],
        ['Banani Office', 'BAN', '9 Road 11, Banani', 'Dhaka', 'Bangladesh'],
        ['Uttara Branch', 'UTT', '45 Uttara Sector 4', 'Dhaka', 'Bangladesh'],
        ['Chittagong Hub', 'CTG', '8 Agrabad CDA', 'Chittagong', 'Bangladesh'],
        ['Sylhet Regional Office', 'SYL', '22 Zindabazar', 'Sylhet', 'Bangladesh'],
        ['Gazipur Distribution Centre', 'GAZ', '77 Tongi Road', 'Gazipur', 'Bangladesh'],
        ['Remote / Work From Home', 'REM', null, null, null],
        ['Kuala Lumpur Office', 'KUL', '18 Jalan Bukit Bintang', 'Kuala Lumpur', 'Malaysia'],
        ['London Office', 'LON', '31 Finsbury Square', 'London', 'United Kingdom'],
    ];

    /**
     * Vendors an obligations register actually holds: licences, maintenance
     * contractors, telecoms, utilities and the professional services an
     * organisation of this shape buys.
     *
     * @var list<string>
     */
    private const VENDORS = [
        'Dell Technologies', 'Apple Reseller Ltd', 'HP Bangladesh', 'Microsoft Volume Licensing',
        'Local IT Wholesale', 'Cisco Systems', 'IBM Bangladesh', 'Oracle Bangladesh',
        'SAP Bangladesh', 'Lenovo Solutions', 'Sony Bangladesh', 'Samsung Electronics',
        'LG Bangladesh', 'Asus Tech', 'Acer Service', 'Nokia Networks', 'Huawei Technologies',
        'Xiaomi Services', 'Logitech Bangladesh', 'Epson Bangladesh',
        'Grameenphone Enterprise', 'Robi Business', 'Banglalink Corporate',
        'Summit Power Generation', 'Dhaka Water Supply Authority', 'Dhaka Electric Supply Authority',
        'Titas Gas Transmission', 'Prasad Food Supply', 'Akij Food Industries',
        'Joy Transport Service', 'Siddhanta Insurance', 'Bangladesh Shield Insurance',
        'Delta Life Insurance', 'Legal Aid Bangladesh Chambers', 'Hossain & Associates',
        'Rahman Audit Firm', 'Bright Facilities Management', 'Clean Sweep Services',
        'Gulshan Print and Stationery', 'Skyline Catering', 'Medicrow Pharmacy',
    ];

    public function run(): void
    {
        $departments = $this->seedDepartments();
        $locations = $this->seedLocations();

        $this->seedVendors();
        $this->seedEmployees($departments, $locations);
    }

    /**
     * @return Collection<string, Department>
     */
    private function seedDepartments(): Collection
    {
        $departments = collect(self::DEPARTMENTS)
            ->map(fn (array $department): Department => Department::query()->updateOrCreate(
                ['department_code' => $department[1]],
                ['department_name' => $department[0], 'status' => 'active'],
            ))
            ->keyBy('department_code');

        $this->command?->info(sprintf('  Departments: %d', $departments->count()));

        return $departments;
    }

    /**
     * @return Collection<string, Location>
     */
    private function seedLocations(): Collection
    {
        $locations = collect(self::LOCATIONS)
            ->map(fn (array $location): Location => Location::query()->updateOrCreate(
                ['location_code' => $location[1]],
                [
                    'location_name' => $location[0],
                    'address' => $location[2],
                    'city' => $location[3],
                    'country' => $location[4],
                    'status' => 'active',
                ],
            ))
            ->keyBy('location_code');

        $this->command?->info(sprintf('  Locations: %d', $locations->count()));

        return $locations;
    }

    /**
     * @return Collection<int, Vendor>
     */
    private function seedVendors(): Collection
    {
        $vendors = collect(self::VENDORS)
            ->map(fn (string $name, int $index): Vendor => Vendor::query()->updateOrCreate(
                ['vendor_name' => $name],
                [
                    'contact_person' => $this->contactPerson($name),
                    'email' => Str::slug($name).'@worksphere.test',
                    'phone' => $this->phone(9000 + $index),
                    'address' => $this->vendorAddress($index),
                    'website' => 'https://'.Str::slug($name).'.example',
                    // A couple of dormant suppliers, because a register where
                    // every vendor is active hides the filtering.
                    'status' => $index % 11 === 10 ? 'inactive' : 'active',
                ],
            ));

        $this->command?->info(sprintf('  Vendors: %d', $vendors->count()));

        return $vendors->values();
    }

    /**
     * @param  Collection<string, Department>  $departments
     * @param  Collection<string, Location>  $locations
     */
    private function seedEmployees(
        Collection $departments,
        Collection $locations,
    ): void {
        foreach (self::ROSTER as $index => $person) {
            [$name, $designation, $departmentCode, $locationCode] = $person;

            Employee::query()->updateOrCreate(
                ['employee_code' => self::employeeCode($index)],
                [
                    'employee_name' => $name,
                    'email' => $this->emailFor($index, $name, $person[4]),
                    'phone' => $this->phone(1000 + $index),
                    'designation' => $designation,
                    'department_id' => $departments[$departmentCode]->id,
                    'location_id' => $locations[$locationCode]->id,
                    'joining_date' => $this->joiningDate($index),
                    'status' => $this->employmentStatus($index),
                ],
            );
        }

        $this->assignDepartmentHeads($departments);

        $this->command?->info(sprintf('  Employees: %d', Employee::query()->count()));
    }

    public static function employeeCode(int $index): string
    {
        return 'EMP-'.str_pad((string) ($index + 1), 4, '0', STR_PAD_LEFT);
    }

    /**
     * Active for everyone except the final two roster entries.
     *
     * An organisation where every record is active makes the status filters look
     * like they work when they are only ever being handed the one value.
     */
    private function employmentStatus(int $index): string
    {
        return $index >= count(self::ROSTER) - 2 ? 'inactive' : 'active';
    }

    /**
     * Point each department at somebody who actually works in it.
     *
     * The previous seeder picked a head at random from the whole employee list, so
     * the Finance department could be headed by a Frontend Engineer and the demo
     * showed an org chart nobody would believe. The head is the most senior
     * roster member of that department: the first one listed.
     *
     * @param  Collection<string, Department>  $departments
     */
    private function assignDepartmentHeads(Collection $departments): void
    {
        foreach ($departments as $code => $department) {
            $head = collect(self::ROSTER)
                ->first(fn (array $person): bool => $person[2] === $code && $person[4] === 'manager')
                ?? collect(self::ROSTER)->first(fn (array $person): bool => $person[2] === $code);

            if ($head === null) {
                continue;
            }

            $employee = Employee::query()
                ->where('employee_name', $head[0])
                ->where('status', 'active')
                ->first();

            if ($employee !== null) {
                $department->forceFill(['head_of_department_id' => $employee->id])->save();
            }
        }
    }

    /**
     * The address a seeded account logs in with.
     *
     * Aliases are keyed on the role slug rather than the person, and only the
     * FIRST member holding a role takes the short address: `admin@…` belongs to
     * one administrator, and the second gets their name like everybody else.
     * Keying on the role alone would hand the same address to thirteen managers
     * and collide on the unique index.
     */
    private function emailFor(int $index, string $name, string $role): string
    {
        $alias = self::aliasFor($index, $role);

        if ($alias !== null) {
            return $alias;
        }

        $slug = Str::slug($name);

        // Two people can legitimately share a name, and `employees.email` is
        // unique. When the roster holds a collision the employee code separates
        // them, so the address stays recognisable instead of becoming opaque.
        foreach (self::ROSTER as $otherIndex => $person) {
            if ($otherIndex !== $index && Str::slug($person[0]) === $slug) {
                return $slug.'-'.strtolower(self::employeeCode($otherIndex)).'@worksphere.test';
            }
        }

        return $slug.'@worksphere.test';
    }

    public static function aliasFor(int $index, string $role): ?string
    {
        if (! array_key_exists($role, self::DEMO_EMAILS)) {
            return null;
        }

        $firstOfRole = null;

        foreach (self::ROSTER as $rosterIndex => $person) {
            if ($person[4] === $role) {
                $firstOfRole = $rosterIndex;

                break;
            }
        }

        return $firstOfRole === $index ? self::DEMO_EMAILS[$role] : null;
    }

    private function contactPerson(string $vendorName): string
    {
        return Str::of($vendorName)
            ->explode(' ')
            ->take(2)
            ->implode(' ')
            .' Accounts Team';
    }

    /**
     * A deterministic number derived from an index.
     *
     * `fake()` would produce a different phone number on every run, which makes a
     * demo unrepeatable and puts a random number behind a contact form.
     */
    private function phone(int $index): string
    {
        return '+8801'.str_pad((string) (7 + ($index % 3)), 1, '0', STR_PAD_LEFT)
            .str_pad((string) (($index * 7919) % 100000000), 8, '0', STR_PAD_LEFT);
    }

    private function vendorAddress(int $index): string
    {
        return sprintf(
            'Level %d, House %d, Road %d, %s',
            ($index % 12) + 1,
            ($index * 3) % 90 + 5,
            ($index % 40) + 1,
            ['Dhaka', 'Chittagong', 'Gulshan', 'Banani', 'Uttara', 'Sylhet'][$index % 6],
        );
    }

    /**
     * A spread of joining dates from 2016, so tenure — and therefore the length of
     * service on an org chart — varies realistically.
     */
    private function joiningDate(int $index): string
    {
        $joined = Carbon::parse('2016-01-11')->addDays($index * 17);

        return ($joined->isFuture() ? Carbon::now()->subDays($index) : $joined)->toDateString();
    }
}
