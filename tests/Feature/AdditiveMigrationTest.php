<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Migration work must be additive: no pre-existing table may disappear, and no
 * column may be removed. This test is the mechanical proof, and it also keeps a
 * register of *deliberate* additions so an undeclared one still fails.
 *
 * The register is the point. "Additive" does not mean "anything goes" — it means
 * every change is named. A migration that adds a column nobody declared is the
 * one this exists to catch.
 */
class AdditiveMigrationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Tables that existed before Phase 3. Every one must still exist.
     *
     * @var list<string>
     */
    private const PRE_EXISTING = [
        'users', 'roles', 'permissions', 'role_permissions', 'user_roles',
        'tasks', 'task_projects', 'task_remarks', 'task_transfers', 'task_notification_logs',
        'meetings', 'meeting_types', 'meeting_participants', 'meeting_agendas',
        'meeting_discussions', 'meeting_decisions', 'meeting_action_items', 'meeting_attachments',
        'meeting_minutes_approvals', 'meeting_recurrences', 'meeting_notification_logs',
        'meeting_templates', 'meeting_tags', 'meeting_tag_map',
        'obligations', 'obligation_types', 'obligation_categories', 'obligation_documents',
        'obligation_responsibilities', 'obligation_renewals', 'obligation_activity_logs',
        'notification_rules', 'notification_logs',
        'escalation_rules', 'approval_workflows', 'approval_workflow_steps',
        'employees', 'departments', 'companies', 'locations', 'vendors',
        'activity_logs', 'tyro_audit_logs', 'login_logs', 'sessions',
    ];

    /**
     * Columns added to a pre-existing table, by the phase that added them.
     *
     * @var array<string, list<string>>
     */
    private const DECLARED_ADDITIONS = [
        'notification_logs' => ['subject_type', 'subject_id', 'dedupe_key'],
        'activity_logs' => ['subject_type', 'subject_id', 'user_agent'],
        'tasks' => ['parent_id', 'estimated_minutes', 'actual_minutes'],
    ];

    /**
     * How many migrations the rollback test steps back: Phase 3's fourteen plus
     * Phase 8's six Task migrations. Both are additive, so the exact-removal
     * assertion below has to name what *both* added.
     */
    private const ADDITIVE_MIGRATIONS = 20;

    public function test_every_pre_existing_table_still_exists(): void
    {
        $missing = array_values(array_filter(
            self::PRE_EXISTING,
            fn (string $table): bool => ! Schema::hasTable($table),
        ));

        $this->assertSame(
            [],
            $missing,
            'Migrations must be additive. These pre-existing tables are gone: '.implode(', ', $missing)
        );
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function declaredAdditionProvider(): array
    {
        $cases = [];

        foreach (self::DECLARED_ADDITIONS as $table => $columns) {
            foreach ($columns as $column) {
                $cases["{$table}.{$column}"] = [$table, $column];
            }
        }

        return $cases;
    }

    #[DataProvider('declaredAdditionProvider')]
    public function test_a_declared_addition_exists(string $table, string $column): void
    {
        $this->assertTrue(
            Schema::hasColumn($table, $column),
            "{$table}.{$column} is declared as an addition but does not exist.",
        );
    }

    public function test_rolling_back_phase_three_leaves_the_old_schema_intact(): void
    {
        $before = $this->columnFingerprints(self::PRE_EXISTING);

        $this->artisan('migrate:rollback', ['--step' => self::ADDITIVE_MIGRATIONS]);

        foreach (self::PRE_EXISTING as $table) {
            $this->assertTrue(Schema::hasTable($table), "{$table} vanished on rollback.");
        }

        // The additive invariant: rolling back may remove columns THIS phase
        // added, and nothing else. An exact-match assertion is stronger than
        // "no unexpected removals" — it also catches a down() that quietly leaves
        // behind one of its own additions.
        $after = $this->columnFingerprints(self::PRE_EXISTING);

        $expectedRemovals = [
            'activity_logs.subject_id',
            'activity_logs.subject_type',
            'activity_logs.user_agent',
            'notification_logs.dedupe_key',
            'notification_logs.subject_id',
            'notification_logs.subject_type',
            // Phase 8 GAP-025/026.
            'tasks.actual_minutes',
            'tasks.estimated_minutes',
            'tasks.parent_id',
        ];

        // removedColumns() walks PRE_EXISTING in declaration order, so both sides
        // are sorted before comparing — the content matters, not the table order.
        sort($expectedRemovals);
        $removed = $this->removedColumns($before, $after);
        sort($removed);

        $this->assertSame(
            $expectedRemovals,
            $removed,
            'Rolling back the additive phases must remove exactly the columns they added.',
        );
        sort($expectedRemovals);

        // Re-apply so the rest of the suite sees the full schema.
        $this->artisan('migrate');

        $this->assertTrue(Schema::hasTable('todos'));
        $this->assertTrue(Schema::hasColumn('activity_logs', 'subject_type'));
        $this->assertTrue(Schema::hasColumn('notification_logs', 'dedupe_key'));
    }

    public function test_no_undeclared_column_was_added_to_a_pre_existing_table(): void
    {
        $declared = [];

        foreach (self::DECLARED_ADDITIONS as $table => $columns) {
            foreach ($columns as $column) {
                $declared["{$table}.{$column}"] = true;
            }
        }

        // A declared addition that does not exist is caught by the provider test
        // above; this one catches the reverse — something added without a record.
        $undeclared = [];

        foreach (self::PRE_EXISTING as $table) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            foreach (Schema::getColumns($table) as $column) {
                $key = "{$table}.{$column['name']}";

                if (! isset($declared[$key]) && $this->looksLikeAPhaseAddition($key)) {
                    $undeclared[] = $key;
                }
            }
        }

        $this->assertSame(
            [],
            $undeclared,
            "These columns were added to a pre-existing table without being declared:\n".implode("\n", $undeclared),
        );
    }

    /**
     * Columns this test has never heard of and that no migration mentions.
     *
     * Narrow on purpose: the pre-Phase-3 baseline is not recorded anywhere, so
     * the check is limited to a column that is absent from every migration file —
     * which means nothing in the repository created it.
     */
    protected function looksLikeAPhaseAddition(string $columnKey): bool
    {
        [$table, $column] = explode('.', $columnKey, 2);

        // Laravel's stock migrations declare some columns through camelCase helper
        // calls (`$table->rememberToken()`), so a literal search reports
        // `users.remember_token` as undeclared. Comparing with underscores
        // stripped matches both spellings.
        $camel = lcfirst(str_replace(' ', '', ucwords(str_replace('_', ' ', $column))));
        $literals = ["'{$column}'", '"'.$column.'"', $column, $camel];

        foreach (glob(database_path('migrations/*.php')) ?: [] as $file) {
            $source = (string) file_get_contents($file);

            foreach ($literals as $needle) {
                if (str_contains($source, $needle)) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Columns present before a rollback but gone afterwards.
     *
     * @param  array<string, list<string>>  $before
     * @param  array<string, list<string>>  $after
     * @return list<string>
     */
    private function removedColumns(array $before, array $after): array
    {
        $removed = [];

        foreach ($before as $table => $columns) {
            foreach (array_diff($columns, $after[$table] ?? []) as $column) {
                $removed[] = "{$table}.{$column}";
            }
        }

        return $removed;
    }

    /**
     * @param  list<string>  $tables
     * @return array<string, list<string>>
     */
    private function columnFingerprints(array $tables): array
    {
        $fingerprints = [];

        foreach ($tables as $table) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            $columns = array_map(
                fn (array $column): string => $column['name'],
                Schema::getColumns($table),
            );

            sort($columns);
            $fingerprints[$table] = $columns;
        }

        return $fingerprints;
    }
}
