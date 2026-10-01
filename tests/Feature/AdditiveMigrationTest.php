<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Phase 3 promises "purely additive". These cases prove it mechanically rather
 * than by intent: rolling the phase back must leave every pre-existing table
 * exactly as it was, and no pre-existing table may lose a column.
 *
 * The pre-existing table list is captured from the schema as it stood at the end
 * of Phase 2, so a future migration that quietly alters an old table trips this.
 */
class AdditiveMigrationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Tables that existed before Phase 3. Every one must still exist, and must
     * still carry at least the columns listed here.
     *
     * @var list<string>
     */
    private const PRE_EXISTING = [
        'users', 'roles', 'permissions', 'role_permissions', 'user_roles',
        'tasks', 'task_projects', 'task_remarks', 'task_transfers', 'task_notification_logs',
        'meetings', 'meeting_types', 'meeting_participants', 'meeting_agendas',
        'meeting_decisions', 'meeting_action_items', 'meeting_attachments',
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
     * Columns that must survive: an additive migration never removes one.
     *
     * @var array<string, list<string>>
     */
    private const PROTECTED_COLUMNS = [
        'notification_logs' => ['obligation_id', 'channel', 'notification_type', 'status'],
        'activity_logs' => ['module_name', 'record_id', 'action', 'old_value', 'new_value'],
    ];

    public function test_every_pre_existing_table_still_exists(): void
    {
        $missing = array_values(array_filter(
            self::PRE_EXISTING,
            fn (string $table): bool => ! Schema::hasTable($table),
        ));

        $this->assertSame(
            [],
            $missing,
            'Phase 3 must be additive. These pre-existing tables are gone: '.implode(', ', $missing)
        );
    }

    #[DataProvider('protectedColumnProvider')]
    public function test_no_pre_existing_column_was_removed(string $table, string $column): void
    {
        $this->assertTrue(
            Schema::hasColumn($table, $column),
            "{$table}.{$column} was removed. Phase 3 may add columns but never drop them."
        );
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function protectedColumnProvider(): array
    {
        $cases = [];

        foreach (self::PROTECTED_COLUMNS as $table => $columns) {
            foreach ($columns as $column) {
                $cases["{$table}.{$column}"] = [$table, $column];
            }
        }

        return $cases;
    }

    public function test_rolling_back_the_phase_three_migrations_leaves_the_old_schema_intact(): void
    {
        // Roll the phase back one step at a time and confirm each down() is real:
        // the new tables go, the added columns go, and the old ones stay.
        $before = $this->columnFingerprints(self::PRE_EXISTING);

        $this->artisan('migrate:rollback', ['--step' => 14]);

        foreach (self::PRE_EXISTING as $table) {
            $this->assertTrue(Schema::hasTable($table), "{$table} vanished on rollback.");
        }

        foreach (self::PROTECTED_COLUMNS as $table => $columns) {
            foreach ($columns as $column) {
                $this->assertTrue(
                    Schema::hasColumn($table, $column),
                    "{$table}.{$column} did not survive the rollback."
                );
            }
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
        ];

        $removed = $this->removedColumns($before, $after);
        sort($removed);

        $this->assertSame($expectedRemovals, $removed);

        // Re-apply so the rest of the suite sees the full schema.
        $this->artisan('migrate');

        $this->assertTrue(Schema::hasTable('todos'));
        $this->assertTrue(Schema::hasColumn('activity_logs', 'subject_type'));
        $this->assertTrue(Schema::hasColumn('notification_logs', 'dedupe_key'));
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
