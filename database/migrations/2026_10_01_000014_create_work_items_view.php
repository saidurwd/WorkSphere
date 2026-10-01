<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * `work_items` read view — DATABASE-ARCHITECTURE.md §4.13.
 *
 * One row per piece of work across Tasks, To-Dos and meeting action items, for
 * the dashboard, global search and cross-module reports.
 *
 * **Read-only by contract.** It is never written to and never joined to for a
 * mutation — a view has no index and no constraint, so a write through it or a
 * foreign key against it would be a performance and integrity trap.
 *
 * MySQL/MariaDB only. SQLite has no equivalent, so the query layer falls back to
 * a UNION of the base tables (Phase 7) and tests exercise that path instead.
 *
 * Deviations from the spec's SQL, both forced by the actual schema:
 * - `tasks` has no `deleted_at` column (Task does not soft delete), so that
 *   column is emitted as a literal NULL to keep the view's shape stable.
 * - `meeting_action_items` has no `project_id`, likewise a literal NULL.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        foreach (['tasks', 'todos', 'meeting_action_items'] as $table) {
            if (! Schema::hasTable($table)) {
                return;
            }
        }

        DB::statement(<<<'SQL'
            CREATE OR REPLACE VIEW `work_items` AS
            SELECT
                'task'                AS `source_type`,
                `id`                  AS `source_id`,
                `title`               AS `title`,
                `status`              AS `status`,
                `priority`            AS `priority`,
                `responsible_user_id` AS `assignee_id`,
                `user_id`             AS `creator_id`,
                `due_date`            AS `due_date`,
                `completed_at`        AS `completed_at`,
                `project_id`          AS `project_id`,
                NULL                  AS `deleted_at`
            FROM `tasks`
            UNION ALL
            SELECT
                'todo',
                `id`,
                `title`,
                `status`,
                `priority`,
                `assignee_id`,
                `creator_id`,
                `due_date`,
                `completed_at`,
                NULL,
                `deleted_at`
            FROM `todos`
            UNION ALL
            SELECT
                'meeting_action_item',
                `id`,
                `title`,
                `status`,
                `priority`,
                `assigned_to`,
                `created_by`,
                `due_date`,
                `completed_at`,
                NULL,
                NULL
            FROM `meeting_action_items`
        SQL);
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::statement('DROP VIEW IF EXISTS `work_items`');
    }
};
