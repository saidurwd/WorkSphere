<?php

namespace Database\Seeders;

use App\Enums\Role as RoleSlug;
use App\Models\Employee;
use App\Models\Role;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;

/**
 * Roles, the accounts that hold them, and who holds what.
 *
 * This closes the last hole in a seeded database. `ProjectPermissionSeeder` built
 * the permission catalogue and the role rows and granted the catalogue to the
 * administrator roles, but nothing ever wrote to `user_roles` — so a freshly
 * seeded database had a complete permission model belonging to nobody. Every
 * account logged in with no role at all and every gated screen 403'd.
 *
 * One account per roster member, because `users.employee_id` is NOT NULL: an
 * account without an employee record cannot exist, and a login that has no
 * department, designation or manager attached renders half-empty on every screen.
 *
 * Credentials are development-only. Every seeded account shares
 * `config('seed.php').password`, which is `password` unless
 * `SEED_DEMO_PASSWORD` says otherwise.
 */
class UserSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $roles = $this->seedRoles();

        if ($roles->isEmpty()) {
            return;
        }

        $limit = (int) config('seed.accounts', count(FoundationSeeder::ROSTER));
        $password = Hash::make((string) config('seed.password', 'password'));

        foreach (array_slice(FoundationSeeder::ROSTER, 0, $limit) as $index => $person) {
            [$name, , , , $roleSlug] = $person;

            $employee = Employee::query()
                ->where('employee_code', FoundationSeeder::employeeCode($index))
                ->first();

            if ($employee === null) {
                continue;
            }

            $user = User::query()->updateOrCreate(
                ['email' => $employee->email],
                [
                    'name' => $name,
                    // Hashed here rather than left to the model's `hashed` cast so
                    // the same cost is paid once for the whole roster instead of
                    // once per row.
                    'password' => $password,
                    'email_verified_at' => Carbon::now(),
                    'employee_id' => $employee->id,
                    'status' => $employee->status,
                ],
            );

            // `user_roles` has a surrogate key, so this is keyed on the pair.
            UserRole::query()->firstOrCreate([
                'user_id' => $user->id,
                'role_id' => $roles[$roleSlug]->id,
            ]);
        }

        $this->report();
    }

    /**
     * @return Collection<string, Role>
     */
    private function seedRoles(): Collection
    {
        $roles = collect(RoleSlug::cases())
            ->mapWithKeys(fn (RoleSlug $role): array => [
                $role->value => Role::query()->firstOrCreate(
                    ['slug' => $role->value],
                    ['name' => $role->label()],
                ),
            ]);

        return $roles;
    }

    /**
     * Print the credentials a demonstration needs.
     *
     * Written to the console rather than kept in documentation, so the accounts
     * and this seeder cannot drift apart.
     */
    private function report(): void
    {
        if ($this->command === null) {
            return;
        }

        $this->command->info(sprintf('  Roles: %d, accounts: %d, assignments: %d',
            Role::query()->count(),
            User::query()->count(),
            UserRole::query()->count(),
        ));

        $this->command->newLine();
        $this->command->line('  Demo logins — password: '.config('seed.password', 'password'));
        $this->command->table(
            ['Role', 'Email'],
            collect(FoundationSeeder::DEMO_EMAILS)
                ->map(fn (string $email, string $slug): array => [
                    RoleSlug::from($slug)->label(),
                    $email,
                ])
                ->values()
                ->all(),
        );
    }
}
