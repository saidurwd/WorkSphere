<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Scope escalation rules by department and company — GAP-031.
 *
 * Today every rule applies to every obligation of its type. `days_before_expiry = 7,
 * level = 1` cannot mean "warn the finance team" for one department and "warn
 * everybody" for another.
 *
 * Both columns are NULLABLE, and NULL keeps the rule global: an existing rule
 * keeps its current behaviour rather than silently narrowing to whichever
 * department happened to be selected. A row matches an obligation when the
 * obligation's department/company equals the rule's, or when the rule's is NULL.
 *
 * `nullOnDelete` on both: removing a department must not delete the escalation
 * rules that mention it — it should widen them back to global.
 *
 * NO composite scope index is added. MySQL picks an existing index to back a new
 * foreign key and will latch onto a multi-column index, after which dropping it is
 * refused ("needed in a foreign key constraint") even once the constraint is
 * gone. Each FK already gets its own index, and the lookup the rules serve leads
 * with `obligation_type_id`, which is indexed. The composite bought nothing and
 * cost a reversible migration.
 */
return new class extends Migration
{
    /**
     * Column => the table it references. Not derivable: the plural of
     * `department_id` is `department_ids`.
     *
     * @var array<string, string>
     */
    private const TABLES = [
        'department_id' => 'departments',
        'company_id' => 'companies',
    ];

    public function up(): void
    {
        $columns = [];

        if (! Schema::hasColumn('escalation_rules', 'department_id')) {
            $columns[] = 'department_id';
        }

        if (! Schema::hasColumn('escalation_rules', 'company_id')) {
            $columns[] = 'company_id';
        }

        if ($columns !== []) {
            Schema::table('escalation_rules', function (Blueprint $table) use ($columns): void {
                foreach ($columns as $column) {
                    // `constrained()` takes a TABLE name, not the column name:
                    // constrained('department_id') would reference a table called
                    // `department_id` and fail with errno 150 on MySQL.
                    $table->foreignId($column)
                        ->nullable()
                        ->constrained(self::TABLES[$column])
                        ->nullOnDelete();
                }
            });
        }

    }

    public function down(): void
    {
        // FK -> index -> column, for the same reason every other migration here
        // needs that order: MySQL refuses to drop an index a live foreign key
        // depends on, and SQLite refuses to drop a column a live index covers.
        // Unconditional: `up()` created these, so a guarded drop that silently
        // skips leaves the foreign keys in place and MySQL then refuses to drop
        // the index they depend on.
        Schema::table('escalation_rules', function (Blueprint $table): void {
            $table->dropForeign(['department_id']);
            $table->dropForeign(['company_id']);
        });

        $columns = array_values(array_filter(
            ['company_id', 'department_id'],
            fn (string $column): bool => Schema::hasColumn('escalation_rules', $column),
        ));

        if ($columns !== []) {
            Schema::table('escalation_rules', function (Blueprint $table) use ($columns): void {
                $table->dropColumn($columns);
            });
        }
    }
};
