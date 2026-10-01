<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Stop `meeting_attachments` cascading away — GAP-030.
 *
 * All four parent columns were `cascadeOnDelete`, so deleting a meeting deleted
 * every attachment under it, and deleting a decision deleted the document that
 * justified it. An attachment is evidence: the decision it supported still
 * happened whether or not somebody tidies up the file.
 *
 * All four become `nullOnDelete`. The attachment row and the file on disk both
 * survive; only the pointer back to the parent is cleared, so the row can be
 * reconciled or re-attached by hand.
 *
 * Nothing is dropped and no existing row changes.
 */
return new class extends Migration
{
    /**
     * attachment column => parent table
     *
     * @var array<string, string>
     */
    private const PARENTS = [
        'meeting_id' => 'meetings',
        'discussion_id' => 'meeting_discussions',
        'decision_id' => 'meeting_decisions',
        'action_item_id' => 'meeting_action_items',
    ];

    public function up(): void
    {
        foreach (self::PARENTS as $column => $parent) {
            if (! Schema::hasColumn('meeting_attachments', $column)) {
                continue;
            }

            $this->assertNoOrphans($column, $parent);

            $this->swapForeignKey($column, $parent, 'nullOnDelete');
        }
    }

    public function down(): void
    {
        foreach (self::PARENTS as $column => $parent) {
            if (! Schema::hasColumn('meeting_attachments', $column)) {
                continue;
            }

            $this->swapForeignKey($column, $parent, 'cascadeOnDelete');
        }
    }

    /**
     * Refuse to add the key while attachment rows point at a parent that no
     * longer exists. Every column here is nullable, so the drop below can never
     * orphan a row, but re-adding the key would fail on such data.
     */
    protected function assertNoOrphans(string $column, string $parent): void
    {
        $orphans = DB::table('meeting_attachments')
            ->whereNotNull($column)
            ->whereNotExists(function ($query) use ($column, $parent): void {
                $query->selectRaw('1')
                    ->from($parent)
                    ->whereColumn("{$parent}.id", "meeting_attachments.{$column}");
            })
            ->count();

        if ($orphans > 0) {
            throw new RuntimeException(
                "Refusing to constrain meeting_attachments.{$column}: {$orphans} row(s) reference a {$parent} "
                .'record that no longer exists. Reattach or clear them before running this migration.'
            );
        }
    }

    protected function swapForeignKey(string $column, string $parent, string $behaviour): void
    {
        // Drop first, then re-add: MySQL cannot change a foreign key's action in
        // place, and SQLite cannot drop one by name at all — but passing the
        // columns makes Laravel rebuild the table instead.
        //
        // The drop is conditional: these constraints are missing on databases
        // created before they existed, and MySQL aborts the whole statement on an
        // unknown constraint name. Re-adding unconditionally still establishes the
        // rule there rather than leaving the column unguarded.
        if (Schema::hasForeignKey('meeting_attachments', [$column])) {
            Schema::table('meeting_attachments', function (Blueprint $table) use ($column) {
                $table->dropForeign([$column]);
            });
        }

        Schema::table('meeting_attachments', function (Blueprint $table) use ($column, $parent, $behaviour) {
            $table->foreign($column)
                ->references('id')
                ->on($parent)
                ->{$behaviour}();
        });
    }
};
