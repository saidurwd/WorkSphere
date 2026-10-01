<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Additive-only index backfill for every table listed in
 * DATABASE-ARCHITECTURE.md §2.3. Each index is guarded by `hasIndex` so the
 * migration is idempotent and safe to re-run.
 */
return new class extends Migration
{
    /**
     * table => [index name => [columns]]
     *
     * @var array<string, array<string, list<string>>>
     */
    protected array $indexes = [
        'activity_logs' => [
            'activity_logs_module_record_index' => ['module_name', 'record_id'],
            'activity_logs_action_index' => ['action'],
            'activity_logs_created_at_index' => ['created_at'],
        ],
        'obligation_renewals' => [
            'obligation_renewals_obligation_id_index' => ['obligation_id'],
            'obligation_renewals_expiry_date_index' => ['expiry_date'],
        ],
        'obligation_documents' => [
            'obligation_documents_obligation_id_index' => ['obligation_id'],
        ],
        'notification_rules' => [
            'notification_rules_obligation_id_index' => ['obligation_id'],
        ],
        'escalation_rules' => [
            'escalation_rules_obligation_id_index' => ['obligation_id'],
        ],
        'tasks' => [
            'tasks_status_index' => ['status'],
            'tasks_due_date_index' => ['due_date'],
            'tasks_priority_index' => ['priority'],
            'tasks_task_no_index' => ['task_no'],
        ],
        'meeting_recurrences' => [
            'meeting_recurrences_meeting_id_index' => ['meeting_id'],
            'meeting_recurrences_next_occurrence_index' => ['next_occurrence'],
        ],
        'meeting_action_items' => [
            'meeting_action_items_priority_index' => ['priority'],
            'meeting_action_items_action_no_index' => ['action_no'],
        ],
        'meeting_notification_logs' => [
            'meeting_notification_logs_channel_index' => ['channel'],
            'meeting_notification_logs_notification_type_index' => ['notification_type'],
            'meeting_notification_logs_scheduled_at_index' => ['scheduled_at'],
        ],
        'task_notification_logs' => [
            'task_notification_logs_channel_index' => ['channel'],
            'task_notification_logs_notification_type_index' => ['notification_type'],
            'task_notification_logs_scheduled_at_index' => ['scheduled_at'],
        ],
        'notification_logs' => [
            'notification_logs_channel_index' => ['channel'],
            'notification_logs_notification_type_index' => ['notification_type'],
            'notification_logs_scheduled_at_index' => ['scheduled_at'],
        ],
        'employees' => [
            'employees_status_index' => ['status'],
            'employees_email_unique' => ['email'],
        ],
        'companies' => [
            'companies_status_index' => ['status'],
        ],
        'vendors' => [
            'vendors_status_index' => ['status'],
        ],
        'locations' => [
            'locations_status_index' => ['status'],
        ],
        'login_logs' => [
            'login_logs_email_index' => ['email'],
            'login_logs_ip_address_index' => ['ip_address'],
            'login_logs_attempted_at_index' => ['attempted_at'],
        ],
        'permissions' => [
            'permissions_permission_name_unique' => ['permission_name'],
        ],
        'roles' => [
            'roles_name_index' => ['name'],
        ],
        'users' => [
            'users_status_index' => ['status'],
        ],
    ];

    /**
     * Indexes declared unique. Kept separate because a unique index is not
     * interchangeable with a plain one when rolling back.
     *
     * @var array<string, list<string>>
     */
    protected array $unique = [
        'employees' => ['employees_email_unique'],
        'permissions' => ['permissions_permission_name_unique'],
    ];

    public function up(): void
    {
        foreach ($this->indexes as $table => $indexes) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            foreach ($indexes as $name => $columns) {
                if (! $this->columnsExist($table, $columns)
                    || Schema::hasIndex($table, $name)
                    || $this->leadingColumnAlreadyIndexed($table, $columns)) {
                    continue;
                }

                Schema::table($table, function (Blueprint $blueprint) use ($columns, $name, $table): void {
                    if (in_array($name, $this->unique[$table] ?? [], true)) {
                        $blueprint->unique($columns, $name);

                        return;
                    }

                    $blueprint->index($columns, $name);
                });
            }
        }
    }

    public function down(): void
    {
        foreach ($this->indexes as $table => $indexes) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            foreach (array_keys($indexes) as $name) {
                if (! Schema::hasIndex($table, $name)) {
                    continue;
                }

                Schema::table($table, function (Blueprint $blueprint) use ($name): void {
                    $blueprint->dropIndex($name);
                });
            }
        }
    }

    /**
     * A single-column index is redundant when another index — a foreign key
     * index created by `constrained()`, for instance — already leads with the
     * same column. Skipping it keeps the migration additive only, and keeps
     * `down()` reversible: MySQL refuses to drop an index a live foreign key
     * depends on.
     *
     * @param  list<string>  $columns
     */
    protected function leadingColumnAlreadyIndexed(string $table, array $columns): bool
    {
        if (count($columns) !== 1) {
            return false;
        }

        foreach (Schema::getIndexes($table) as $index) {
            if (($index['columns'][0] ?? null) === $columns[0]) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  list<string>  $columns
     */
    protected function columnsExist(string $table, array $columns): bool
    {
        foreach ($columns as $column) {
            if (! Schema::hasColumn($table, $column)) {
                return false;
            }
        }

        return true;
    }
};
