<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\User;
use App\Rules\PasswordPolicy;
use Database\Factories\CompanyFactory;
use Database\Factories\DepartmentFactory;
use Database\Factories\EmployeeFactory;
use Illuminate\Database\QueryException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Modules\Obligations\Models\ApprovalWorkflow;
use Modules\Obligations\Models\ApprovalWorkflowStep;
use Modules\Obligations\Models\EscalationRule;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\InteractsWithRoles;
use Tests\TestCase;

/**
 * The Phase 8 remainder: identity administration, the password policy, account
 * status, and the escalation scope.
 */
class IdentityAndObligationTest extends TestCase
{
    use InteractsWithRoles, RefreshDatabase;

    // ---- Password policy (GAP-041) -----------------------------------------

    #[DataProvider('weakPasswordProvider')]
    public function test_the_password_policy_rejects_a_weak_password(string $password): void
    {
        $validator = validator(
            ['password' => $password],
            ['password' => ['required', new PasswordPolicy]],
        );

        $this->assertTrue($validator->fails(), "PasswordPolicy accepted '{$password}'.");
    }

    /**
     * @return array<string, array{string}>
     */
    public static function weakPasswordProvider(): array
    {
        return [
            'too short' => ['Ab1!'],
            'no digit' => ['PasswordOnlyLetters'],
            'no upper case' => ['password1234567'],
            'no lower case' => ['PASSWORD1234567'],
            'on the common list' => ['password123'],
            'a single repeated character' => ['aaaaaaaaaaaaaaaa'],
        ];
    }

    #[DataProvider('strongPasswordProvider')]
    public function test_the_password_policy_accepts_a_strong_password(string $password): void
    {
        $validator = validator(
            ['password' => $password],
            ['password' => ['required', new PasswordPolicy]],
        );

        $this->assertFalse($validator->fails(), 'PasswordPolicy rejected a strong password: '.$validator->errors()->first('password'));
    }

    /**
     * @return array<string, array{string}>
     */
    public static function strongPasswordProvider(): array
    {
        return [
            'passphrase' => ['correct-horse-Battery9'],
            'mixed with symbols' => ['Tr0ub4dor&3!'],
        ];
    }

    // ---- Account status (GAP-041) ------------------------------------------

    public function test_a_deactivated_account_cannot_use_the_application(): void
    {
        $user = User::factory()->create();
        $user->update(['status' => 'inactive']);

        $this->actingAs($user)
            ->get(route('my-work'))
            ->assertRedirect(route('login'));

        $this->assertGuest();
    }

    public function test_an_active_account_is_unaffected(): void
    {
        $user = User::factory()->create(['status' => 'active']);

        $this->actingAs($user)->get(route('my-work'))->assertOk();
    }

    public function test_a_new_user_defaults_to_an_unblocked_status(): void
    {
        // `users.status` is NOT NULL with a default, so "no status" cannot be
        // written; the meaningful case is that the default is not a blocked value.
        $user = User::factory()->create();

        $this->assertNotContains(
            strtolower((string) $user->status),
            ['inactive', 'suspended', 'disabled'],
        );

        $this->actingAs($user)->get(route('my-work'))->assertOk();
    }

    public function test_each_blocked_status_is_refused(): void
    {
        foreach (['inactive', 'suspended', 'disabled'] as $status) {
            $user = User::factory()->create();
            $user->update(['status' => $status]);

            $this->actingAs($user)
                ->get(route('my-work'))
                ->assertRedirect(route('login'), "Status '{$status}' was allowed through.");
        }
    }

    // ---- The employee_id constraint (GAP-040) ------------------------------

    public function test_the_database_refuses_a_user_with_no_employee(): void
    {
        $this->assertTrue(Schema::hasColumn('users', 'employee_id'));

        $this->expectException(QueryException::class);

        DB::table('users')->insert([
            'name' => 'Orphan',
            'email' => 'orphan@example.com',
            'password' => Hash::make('secret1234'),
            'employee_id' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_the_backfill_command_reports_without_writing(): void
    {
        // The command is a report unless --link is passed. A backfill that invents
        // employee records is destructive, so the safe mode has to be the default.
        $this->relaxEmployeeConstraint();

        // One unlinked user, or the command short-circuits with "every user
        // already has an employee record" and the report path is never taken.
        DB::table('users')->insert([
            'name' => 'Unlinked',
            'email' => 'unlinked@example.com',
            'password' => Hash::make('secret1234'),
            'employee_id' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $before = DB::table('users')->whereNull('employee_id')->count();

        $this->artisan('users:backfill-employees')
            ->expectsOutputToContain('Report only')
            ->assertSuccessful();

        $this->assertSame($before, DB::table('users')->whereNull('employee_id')->count());
    }

    public function test_the_backfill_command_links_by_matching_email(): void
    {
        $this->relaxEmployeeConstraint();

        $employee = EmployeeFactory::new()->create(['email' => 'match@example.com']);

        DB::table('users')->insert([
            'name' => 'Matchable',
            'email' => 'match@example.com',
            'password' => Hash::make('secret1234'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $userId = DB::table('users')->where('email', 'match@example.com')->value('id');

        $this->artisan('users:backfill-employees --link')
            ->expectsOutputToContain('Linked 1 user')
            ->assertSuccessful();

        $this->assertSame($employee->id, DB::table('users')->where('id', $userId)->value('employee_id'));
    }

    public function test_the_backfill_leaves_an_unmatched_user_alone(): void
    {
        $this->relaxEmployeeConstraint();

        DB::table('users')->insert([
            'name' => 'Nobody',
            'email' => 'nobody-at-all@example.com',
            'password' => Hash::make('secret1234'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->artisan('users:backfill-employees --link')
            ->expectsOutputToContain('still have no employee record')
            ->assertSuccessful();

        $this->assertNull(
            DB::table('users')->where('email', 'nobody-at-all@example.com')->value('employee_id')
        );
    }

    public function test_the_backfill_is_idempotent(): void
    {
        $this->relaxEmployeeConstraint();

        $employee = EmployeeFactory::new()->create(['email' => 'again@example.com']);

        DB::table('users')->insert([
            'name' => 'Again',
            'email' => 'again@example.com',
            'password' => Hash::make('secret1234'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->artisan('users:backfill-employees --link')->assertSuccessful();
        $this->artisan('users:backfill-employees --link')->assertSuccessful();

        $linked = DB::table('users')->where('employee_id', $employee->id)->count();

        $this->assertSame(1, $linked, 'Re-running the backfill must not attach a second user.');
    }

    // ---- Escalation scope (GAP-031) ----------------------------------------

    public function test_a_global_rule_applies_to_every_obligation(): void
    {
        $rule = EscalationRule::query()->create([
            'days_before_expiry' => 7,
            'escalation_level' => 1,
            'recipient_type' => 'OWNER',
            'channel' => 'EMAIL',
            'active' => true,
        ]);

        $this->assertTrue($rule->appliesToScope(null, null));
        $this->assertTrue($rule->appliesToScope(1, 1));
    }

    public function test_a_scoped_rule_applies_only_inside_its_scope(): void
    {
        $department = DepartmentFactory::new()->create();

        $rule = EscalationRule::query()->create([
            'department_id' => $department->id,
            'days_before_expiry' => 7,
            'escalation_level' => 1,
            'recipient_type' => 'OWNER',
            'channel' => 'EMAIL',
            'active' => true,
        ]);

        $this->assertTrue($rule->appliesToScope($department->id, null));
        $this->assertFalse($rule->appliesToScope($department->id + 999, null));
    }

    public function test_a_rule_scoped_to_both_requires_both_to_match(): void
    {
        $department = DepartmentFactory::new()->create();
        $company = CompanyFactory::new()->create();

        $rule = EscalationRule::query()->create([
            'department_id' => $department->id,
            'company_id' => $company->id,
            'days_before_expiry' => 7,
            'escalation_level' => 1,
            'recipient_type' => 'OWNER',
            'channel' => 'EMAIL',
            'active' => true,
        ]);

        $this->assertTrue($rule->appliesToScope($department->id, $company->id));
        $this->assertFalse($rule->appliesToScope($department->id, $company->id + 999));
        $this->assertFalse($rule->appliesToScope($department->id + 999, $company->id));
    }

    public function test_the_escalation_scope_columns_exist_and_are_nullable(): void
    {
        foreach (['department_id', 'company_id'] as $column) {
            $this->assertTrue(Schema::hasColumn('escalation_rules', $column));

            $definition = collect(Schema::getColumns('escalation_rules'))->firstWhere('name', $column);

            $this->assertTrue(
                $definition['nullable'],
                "escalation_rules.{$column} must stay nullable so an existing rule keeps its global reach.",
            );
        }
    }

    public function test_the_approval_workflow_tables_are_gone(): void
    {
        // GAP-031, decided: a second approval mechanism alongside
        // obligations.approver_user_id, with no route, controller or query.
        $this->assertFalse(Schema::hasTable('approval_workflows'));
        $this->assertFalse(Schema::hasTable('approval_workflow_steps'));
    }

    public function test_the_approval_models_are_gone(): void
    {
        $this->assertFalse(class_exists(ApprovalWorkflow::class));
        $this->assertFalse(class_exists(ApprovalWorkflowStep::class));
    }

    /**
     * Temporarily drop the `users.employee_id` NOT NULL constraint.
     *
     * The backfill exists to repair users with no employee record, so its own
     * tests have to be able to produce that state — which the Phase 8 constraint
     * now forbids. Relaxing it here is deliberate: the alternative is a test that
     * can never reach the case it exists for.
     */
    private function relaxEmployeeConstraint(): void
    {
        // Laravel rebuilds the table for SQLite when a column is changed, and
        // ALTERs it directly on MySQL, so `->change()` covers both drivers where
        // raw SQL covers only one.
        Schema::table('users', function (Blueprint $table): void {
            $table->unsignedBigInteger('employee_id')->nullable()->change();
        });
    }
}
