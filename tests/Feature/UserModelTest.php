<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\Role;
use App\Models\User;
use Database\Factories\ProjectFactory;
use Database\Factories\TaskFactory;
use Illuminate\Database\Eloquent\MassAssignmentException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Modules\Projects\Models\Project;
use Modules\Tasks\Models\Task;
use Tests\InteractsWithRoles;
use Tests\TestCase;

/**
 * Phase 2 model repairs: the missing `employee` relation, the $fillable additions,
 * and the strict mass-assignment guard. Each of these is a bug that only throws
 * when the exact path is exercised, so each needs a test of its own.
 */
class UserModelTest extends TestCase
{
    use InteractsWithRoles, RefreshDatabase;

    public function test_the_employee_relation_resolves(): void
    {
        $employee = Employee::factory()->create();
        $user = User::factory()->create(['employee_id' => $employee->id]);

        $this->assertInstanceOf(Employee::class, $user->employee);
        $this->assertSame($employee->id, $user->employee->id);
    }

    /**
     * Phase 8 GAP-040: `users.employee_id` is NOT NULL, so a user without an
     * employee record cannot exist. The relation is therefore total, not optional.
     */
    public function test_every_user_has_an_employee_record(): void
    {
        $user = User::factory()->create();

        $this->assertNotNull($user->employee_id);
        $this->assertInstanceOf(Employee::class, $user->employee);
    }

    public function test_the_database_refuses_a_user_with_no_employee(): void
    {
        $this->expectException(QueryException::class);

        DB::table('users')->insert([
            'name' => 'No employee',
            'email' => 'no-employee@example.com',
            'password' => bcrypt('secret1234'),
            'employee_id' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_tasks_responsible_tasks_and_projects_resolve(): void
    {
        $user = User::factory()->create();
        TaskFactory::new()->ownedBy($user)->create(['title' => 'Owned']);
        ProjectFactory::new()->ownedBy($user)->create(['name' => 'Owned project']);

        $this->assertCount(1, $user->tasks);
        $this->assertCount(1, $user->responsibleTasks);
        $this->assertCount(1, $user->projects);
        $this->assertInstanceOf(Task::class, $user->tasks->first());
        $this->assertInstanceOf(Project::class, $user->projects->first());
    }

    public function test_status_is_mass_assignable_on_a_user(): void
    {
        $user = User::factory()->create(['status' => 'inactive']);

        $this->assertSame('inactive', $user->fresh()->status);
    }

    public function test_description_is_mass_assignable_on_a_role(): void
    {
        $role = Role::query()->create([
            'name' => 'Auditor',
            'slug' => 'auditor',
            'description' => 'Reads everything, changes nothing',
        ]);

        $this->assertSame('Reads everything, changes nothing', $role->fresh()->description);
    }

    public function test_the_password_stays_hashed_and_the_cast_is_present(): void
    {
        $user = User::factory()->create(['password' => 'plain-text-secret']);

        $this->assertNotSame('plain-text-secret', $user->fresh()->password);
        $this->assertTrue(password_verify('plain-text-secret', $user->fresh()->password));
        $this->assertContains('hashed', $user->getCasts());
    }

    public function test_writing_a_non_fillable_attribute_throws_rather_than_being_dropped_silently(): void
    {
        // preventSilentlyDiscardingAttributes is enabled in local and testing, so a
        // forgotten $fillable entry surfaces as an exception rather than data loss.
        $this->assertTrue(Model::preventsSilentlyDiscardingAttributes());

        $this->expectException(MassAssignmentException::class);

        (new User)->fill(['not_a_real_column' => 'boom']);
    }

    public function test_a_fillable_attribute_is_still_assigned_under_the_strict_guard(): void
    {
        $user = new User;
        $user->fill(['name' => 'Strictly Filled']);

        $this->assertSame('Strictly Filled', $user->name);
    }

    public function test_role_slugs_are_exposed_for_the_admin_middleware(): void
    {
        $user = $this->plainUser();
        $this->actingAs($user);

        $role = Role::query()->create(['name' => 'Admin', 'slug' => 'admin']);
        $user->roles()->attach($role->id);

        $this->assertContains('admin', $user->fresh()->roleSlugs());
    }
}
