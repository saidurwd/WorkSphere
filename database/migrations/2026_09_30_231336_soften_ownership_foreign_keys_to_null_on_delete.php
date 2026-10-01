<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Deleting a user currently destroys every task, meeting, obligation, minutes
 * approval and remark they owned. Losing an account should orphan the ownership
 * pointer, not the work. This relaxes the five ownership columns to nullable and
 * switches their foreign keys from cascade to nullOnDelete.
 *
 * No data backfill is required: the change only relaxes an existing NOT NULL
 * constraint, so every existing row keeps its owner and stays valid. The
 * migration asserts first that no ownership row is already orphaned, so a bad
 * state is reported rather than silently inherited.
 */
return new class extends Migration
{
    /**
     * table => ownership column
     *
     * @var array<string, string>
     */
    protected array $ownership = [
        'tasks' => 'user_id',
        'meetings' => 'organizer_id',
        'obligations' => 'owner_user_id',
        'meeting_minutes_approvals' => 'approver_id',
        'task_remarks' => 'user_id',
    ];

    public function up(): void
    {
        foreach ($this->ownership as $table => $column) {
            if (! Schema::hasTable($table) || ! Schema::hasColumn($table, $column)) {
                continue;
            }

            $this->assertNoOrphans($table, $column);

            $this->changeColumn($table, $column, true);

            $this->swapForeignKey($table, $column, 'nullOnDelete');
        }
    }

    public function down(): void
    {
        foreach ($this->ownership as $table => $column) {
            if (! Schema::hasTable($table) || ! Schema::hasColumn($table, $column)) {
                continue;
            }

            $this->assertNoOrphans($table, $column);

            $this->swapForeignKey($table, $column, 'cascadeOnDelete');

            $this->changeColumn($table, $column, false);
        }
    }

    /**
     * Refuse to apply while ownership rows point at a user that no longer exists.
     */
    protected function assertNoOrphans(string $table, string $column): void
    {
        $orphans = DB::table($table)
            ->whereNotNull($column)
            ->whereNotExists(function ($query) use ($column, $table): void {
                $query->selectRaw('1')
                    ->from('users')
                    ->whereColumn('users.id', $table.'.'.$column);
            })
            ->count();

        if ($orphans > 0) {
            throw new RuntimeException(
                "Refusing to alter {$table}.{$column}: {$orphans} row(s) reference a user that no longer exists. "
                .'Reassign or clear them before running this migration.'
            );
        }
    }

    protected function changeColumn(string $table, string $column, bool $nullable): void
    {
        $isNullable = collect(Schema::getColumns($table))
            ->firstWhere('name', $column)['nullable'] ?? false;

        if ($isNullable === $nullable) {
            return;
        }

        Schema::table($table, function (Blueprint $blueprint) use ($column, $nullable): void {
            $definition = $nullable
                ? $blueprint->unsignedBigInteger($column)->nullable()
                : $blueprint->unsignedBigInteger($column);

            $definition->change();
        });
    }

    protected function swapForeignKey(string $table, string $column, string $behaviour): void
    {
        $foreignKeyName = "{$table}_{$column}_foreign";

        // SQLite defines ON DELETE at CREATE TABLE time and Laravel's SQLite grammar
        // refuses to drop a foreign key by name. Rebuilding all five tables on
        // SQLite is not worth the divergence risk for a test driver, so the column
        // stays nullable there and the CASCADE rule simply never fires. The MySQL
        // and production schemas carry the corrected rule.
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        if (Schema::hasForeignKey($table, [$column])) {
            Schema::table($table, function (Blueprint $blueprint) use ($foreignKeyName): void {
                $blueprint->dropForeign($foreignKeyName);
            });
        }

        Schema::table($table, function (Blueprint $blueprint) use ($column, $behaviour): void {
            $blueprint->foreign($column)
                ->references('id')
                ->on('users')
                ->{$behaviour}();
        });
    }
};
