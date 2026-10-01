<?php

namespace Tests\Feature;

use App\Enums\NotificationChannel;
use App\Enums\Priority;
use App\Enums\RecurrenceFrequency;
use App\Enums\Role as RoleSlug;
use App\Enums\WorkItemStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Phase 2 created the shared enums but stored values must not move. These cases
 * read the vocabularies straight out of the migration files and assert that
 * every value the database can already contain is reachable as an enum case, so
 * an enum can never silently reject legacy data.
 */
class EnumVocabularyTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Every `$table->enum('column', [...])` declaration in database/migrations,
     * as table.column => values.
     *
     * @return array<string, list<string>>
     */
    private function databaseEnumValues(): array
    {
        $vocabularies = [];

        foreach (glob(database_path('migrations/*.php')) as $file) {
            $source = (string) file_get_contents($file);

            if (! preg_match_all("/->enum\(\s*'([a-z_]+)'\s*,\s*\[(.*?)\]/s", $source, $matches, PREG_SET_ORDER)) {
                continue;
            }

            foreach ($matches as $match) {
                preg_match_all("/'([^']+)'/", $match[2], $values);

                $key = pathinfo($file, PATHINFO_FILENAME).'.'.$match[1];
                $vocabularies[$key] = $values[1];
            }
        }

        return $vocabularies;
    }

    /**
     * The DB enum columns that the five shared enums are responsible for
     * unifying. Columns outside this list (minutes_status, participant_type,
     * attendance_status, decision_type, decision_status, minutes approval status)
     * have no shared enum yet; they are listed explicitly by
     * {@see test_the_out_of_scope_enum_columns_are_declared()} so the gap stays
     * visible instead of silently unasserted.
     *
     * @return array<string, array{string, list<string>}>
     */
    private function unifiedVocabularies(): array
    {
        return [
            'WorkItemStatus' => [
                'create_tasks_table.status',
                'create_meetings_table.status',
                'create_meeting_agendas_table.status',
                'create_meeting_action_items_table.status',
            ],
            'Priority' => [
                'create_tasks_table.priority',
                'create_meetings_table.priority',
                'create_meeting_action_items_table.priority',
                'create_meeting_templates_table.default_priority',
            ],
            'RecurrenceFrequency' => [
                'create_meeting_recurrences_table.recurrence_type',
            ],
        ];
    }

    /**
     * @return array<string, list<string>>
     */
    private function vocabularyMap(): array
    {
        $map = [];

        foreach ($this->databaseEnumValues() as $key => $values) {
            // Strip the migration timestamp so keys are stable across renames of
            // the file prefix.
            $map[preg_replace('/^\d{4}_\d{2}_\d{2}_\d{6}_/', '', $key)] = $values;
        }

        return $map;
    }

    public function test_the_database_enums_are_discovered_for_the_assertions_below(): void
    {
        $this->assertNotEmpty(
            $this->databaseEnumValues(),
            'No $table->enum() declarations were found; the guard below would pass vacuously.'
        );
    }

    public function test_every_unified_database_enum_value_is_reachable_as_an_enum_case(): void
    {
        $enums = [
            'WorkItemStatus' => WorkItemStatus::values(),
            'Priority' => Priority::values(),
            'RecurrenceFrequency' => RecurrenceFrequency::values(),
        ];

        $map = $this->vocabularyMap();
        $unmapped = [];

        foreach ($this->unifiedVocabularies() as $enum => $columns) {
            foreach ($columns as $column) {
                $this->assertArrayHasKey(
                    $column,
                    $map,
                    "The migration declaring {$column} is missing; the vocabulary it was "
                    .'meant to pin no longer exists and this test would pass vacuously.',
                );

                foreach ($map[$column] as $value) {
                    if (! in_array($value, $enums[$enum], true)) {
                        $unmapped[] = "{$column} = '{$value}' is not a {$enum} case";
                    }
                }
            }
        }

        $this->assertSame(
            [],
            $unmapped,
            'These stored values are not representable by the shared enum. '
            .'Add a case rather than remapping the value.'
        );
    }

    public function test_the_out_of_scope_enum_columns_are_declared(): void
    {
        // Every enum column must be either unified under a shared enum or listed
        // here as a known gap. A new enum column that lands in neither fails loudly
        // instead of escaping the vocabulary check unnoticed.
        $outOfScope = [
            'create_meetings_table.minutes_status',
            'create_meeting_participants_table.participant_type',
            'create_meeting_participants_table.attendance_status',
            'create_meeting_decisions_table.decision_type',
            'create_meeting_decisions_table.decision_status',
            'create_meeting_minutes_approvals_table.status',
        ];

        $unified = array_merge(...array_values($this->unifiedVocabularies()));

        $this->assertSame(
            [],
            array_intersect($unified, $outOfScope),
            'A column is declared both unified and out of scope. Remove it from one list.'
        );

        $covered = array_merge($unified, $outOfScope);
        sort($covered);

        $discovered = array_keys($this->vocabularyMap());
        sort($discovered);

        $this->assertSame(
            $discovered,
            $covered,
            'These enum columns are neither unified under a shared enum nor declared '
            .'out of scope: '.implode(', ', array_diff($discovered, $covered))
        );
    }

    public function test_each_enum_exposes_a_label_for_every_case(): void
    {
        foreach ([Priority::class, WorkItemStatus::class, RecurrenceFrequency::class, NotificationChannel::class, RoleSlug::class] as $enum) {
            $cases = $enum::cases();

            $this->assertNotEmpty($cases, $enum.' declares no cases.');

            foreach ($cases as $case) {
                $this->assertNotSame('', $case->label(), $enum.'::'.$case->name.' has no label.');
            }

            $this->assertCount(
                count($cases),
                $enum::values(),
                $enum.'::values() does not cover every case.',
            );
        }
    }

    public function test_enum_backing_values_are_unique(): void
    {
        foreach ([Priority::class, WorkItemStatus::class, RecurrenceFrequency::class, NotificationChannel::class, RoleSlug::class] as $enum) {
            $values = $enum::values();

            $this->assertSame(
                array_values(array_unique($values)),
                $values,
                $enum.' declares duplicate backing values.',
            );
        }
    }

    /**
     * @return array<string, array{class-string}>
     */
    public static function enumProvider(): array
    {
        return [
            'priority' => [Priority::class],
            'work item status' => [WorkItemStatus::class],
            'recurrence frequency' => [RecurrenceFrequency::class],
            'notification channel' => [NotificationChannel::class],
            'role slug' => [RoleSlug::class],
        ];
    }

    #[DataProvider('enumProvider')]
    public function test_an_enum_from_string_returns_itself(string $enum): void
    {
        foreach ($enum::cases() as $case) {
            $resolved = $enum::from($case->value);

            $this->assertSame($case, $resolved);
            $this->assertSame($case->value, $resolved->value);
        }
    }
}
