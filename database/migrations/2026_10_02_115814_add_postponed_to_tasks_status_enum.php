<?php

use App\Enums\WorkItemStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Add `postponed` to `tasks.status`.
 *
 * `2026_10_02_000001_widen_tasks_status_enum` already put `on_hold` and `cancelled`
 * in the column, but `postponed` was missing, and neither of the other two ever
 * reached the validation rules or the dropdowns — the copies of this list had
 * drifted from the schema. `WorkItemStatus::TASK_CASES` is now the one list, and
 * this migration makes the column agree with it.
 *
 * Purely additive: every existing value keeps its meaning and every existing row
 * stays valid, so there is nothing to remap. `postponed` is OPEN work — it appears
 * in `Task::scopeActive()` and counts towards workload — while `cancelled` is
 * closed, which is what `WorkItemStatus::openValues()` already said.
 *
 * Laravel's schema builder cannot ALTER a MySQL ENUM and this column is one, so
 * the statement is issued directly and skipped on SQLite, where `enum()` is a plain
 * VARCHAR with no constraint to widen.
 *
 * The values are read from the enum rather than repeated, so a future addition to
 * `TASK_CASES` cannot land in the code and miss the database — which is exactly
 * how `postponed` came to be missing in the first place.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        // The enum is the source of truth. Rendering the list from it means this
        // migration cannot disagree with the validation rules or the dropdowns
        // about which statuses exist.
        $wanted = WorkItemStatus::taskValues();

        if ($this->columnHas($wanted)) {
            return;
        }

        DB::statement($this->alterTo($wanted));
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        $removing = [WorkItemStatus::Postponed->value];

        // Narrowing cannot happen while rows use a value the narrower enum omits,
        // and MySQL would silently coerce them to '' — an empty status that matches
        // no scope, no dropdown and no report. Refuse instead.
        $stranded = DB::table('tasks')
            ->whereIn('status', $removing)
            ->count();

        if ($stranded > 0) {
            throw new RuntimeException(
                "Refusing to narrow tasks.status: {$stranded} row(s) are postponed, "
                .'which the previous enum did not allow. Move them to another status before rolling back.'
            );
        }

        DB::statement($this->alterTo(array_values(array_diff(
            WorkItemStatus::taskValues(),
            $removing,
        ))));
    }

    /**
     * Whether the column already permits every one of these values.
     *
     * Checks all of them rather than the last one, so a column that was widened
     * only partway — which is the state this migration is repairing — is still
     * detected and completed. The previous migration keyed its guard on `on_hold`,
     * which meant it could not tell a fully widened column from a partial one.
     *
     * @param  list<string>  $values
     */
    protected function columnHas(array $values): bool
    {
        $column = collect(Schema::getColumns('tasks'))->firstWhere('name', 'status');

        $type = strtolower((string) ($column['type'] ?? ''));

        foreach ($values as $value) {
            if (! str_contains($type, $value)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param  list<string>  $values
     */
    protected function alterTo(array $values): string
    {
        $list = implode(', ', array_map(
            static fn (string $value): string => "'".$value."'",
            $values,
        ));

        return 'ALTER TABLE `tasks` MODIFY COLUMN `status` ENUM('.$list.") NOT NULL DEFAULT 'pending'";
    }
};
