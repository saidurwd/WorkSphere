<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
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
     * @var list<string>
     */
    private const PARENTS = ['meeting_id', 'discussion_id', 'decision_id', 'action_item_id'];

    public function up(): void
    {
        foreach (self::PARENTS as $column) {
            if (! Schema::hasColumn('meeting_attachments', $column)) {
                continue;
            }

            // Drop first, then re-add: MySQL cannot change a foreign key's action
            // in place, and SQLite cannot drop one by name at all — but Laravel
            // rebuilds the table for SQLite on dropForeign.
            Schema::table('meeting_attachments', function (Blueprint $table) use ($column) {
                $table->dropForeign([$column]);
            });

            Schema::table('meeting_attachments', function (Blueprint $table) use ($column) {
                $table->foreign($column)
                    ->references('id')
                    ->on($this->parentTable($column))
                    ->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        foreach (self::PARENTS as $column) {
            if (! Schema::hasColumn('meeting_attachments', $column)) {
                continue;
            }

            Schema::table('meeting_attachments', function (Blueprint $table) use ($column) {
                $table->dropForeign([$column]);
            });

            Schema::table('meeting_attachments', function (Blueprint $table) use ($column) {
                $table->foreign($column)
                    ->references('id')
                    ->on($this->parentTable($column))
                    ->cascadeOnDelete();
            });
        }
    }

    protected function parentTable(string $column): string
    {
        return match ($column) {
            'discussion_id' => 'meeting_discussions',
            'decision_id' => 'meeting_decisions',
            'action_item_id' => 'meeting_action_items',
            default => 'meetings',
        };
    }
};
