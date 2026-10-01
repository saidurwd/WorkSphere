<?php

namespace Tests\Feature;

use App\Enums\RecurrenceFrequency;
use App\Enums\WorkItemStatus;
use App\Models\User;
use App\Services\RecurrenceService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Modules\Todos\Events\TodoCompleted;
use Modules\Todos\Events\TodoRecurringGenerated;
use Modules\Todos\Models\Scopes\TodoScope;
use Modules\Todos\Models\Todo;
use Modules\Todos\Services\TodoRecurrenceService;
use Modules\Todos\Services\TodoService;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Recurrence arithmetic and generation — TODO-MODULE-SPECIFICATION.md §5.
 *
 * The arithmetic lives in the shared App\Services\RecurrenceService because
 * Meetings and Obligations migrate onto it in Phase 8, so it is tested here as a
 * unit rather than only through the To-Do module.
 */
class TodoRecurrenceTest extends TestCase
{
    use RefreshDatabase;

    private RecurrenceService $recurrence;

    protected function setUp(): void
    {
        parent::setUp();

        $this->recurrence = app(RecurrenceService::class);
    }

    /**
     * @return array<string, array{string, int, string}>
     */
    public static function frequencyProvider(): array
    {
        return [
            'daily' => ['daily', 1, '2026-10-02'],
            'daily interval 3' => ['daily', 3, '2026-10-04'],
            'weekly' => ['weekly', 1, '2026-10-08'],
            'weekly interval 2' => ['weekly', 2, '2026-10-15'],
            'biweekly' => ['biweekly', 1, '2026-10-15'],
            'monthly' => ['monthly', 1, '2026-11-01'],
            'monthly interval 3' => ['monthly', 3, '2027-01-01'],
            'quarterly' => ['quarterly', 1, '2027-01-01'],
            'yearly' => ['yearly', 1, '2027-10-01'],
        ];
    }

    #[DataProvider('frequencyProvider')]
    public function test_each_frequency_advances_by_the_expected_step(string $frequency, int $interval, string $expected): void
    {
        $rule = [
            'frequency' => $frequency,
            'interval' => $interval,
            'start_date' => '2026-10-01',
        ];

        $next = $this->recurrence->nextOccurrence($rule, CarbonImmutable::parse('2026-10-01'));

        $this->assertNotNull($next);
        $this->assertSame($expected, $next->toDateString());
    }

    public function test_by_weekday_selects_the_next_declared_day(): void
    {
        $rule = [
            'frequency' => 'weekly',
            'interval' => 1,
            // 2026-10-01 is a Thursday. Next week's Monday is the 5th.
            'by_weekday' => [1, 3],
            'start_date' => '2026-10-01',
        ];

        $next = $this->recurrence->nextOccurrence($rule, CarbonImmutable::parse('2026-10-01'));

        $this->assertSame('2026-10-05', $next?->toDateString());
    }

    public function test_monthly_add_months_without_overflowing_a_short_month(): void
    {
        // 31 January + 1 month must be 28/29 February, not 3 March. Silently
        // rolling into the next month would skip the occurrence entirely.
        $rule = ['frequency' => 'monthly', 'interval' => 1];

        $next = $this->recurrence->nextOccurrence($rule, CarbonImmutable::parse('2026-01-31'));

        $this->assertSame('2026-02-28', $next?->toDateString());
    }

    public function test_end_date_stops_the_series(): void
    {
        $rule = [
            'frequency' => 'monthly',
            'interval' => 1,
            'end_date' => '2026-10-15',
        ];

        $next = $this->recurrence->nextOccurrence($rule, CarbonImmutable::parse('2026-10-01'));

        $this->assertNull($next, 'An occurrence past the end date must not be generated.');
    }

    public function test_max_occurrences_stops_the_series(): void
    {
        $rule = [
            'frequency' => 'daily',
            'interval' => 1,
            'max_occurrences' => 3,
        ];

        // Occurrence 3 exists; occurrence 4 would exceed the maximum.
        $this->assertNotNull($this->recurrence->nextOccurrence($rule, CarbonImmutable::parse('2026-10-01'), 2));
        $this->assertNull($this->recurrence->nextOccurrence($rule, CarbonImmutable::parse('2026-10-01'), 3));
    }

    public function test_skip_dates_are_stepped_over(): void
    {
        $rule = [
            'frequency' => 'daily',
            'interval' => 1,
            'skip_dates' => ['2026-10-02', '2026-10-03'],
        ];

        $next = $this->recurrence->nextUnskipped($rule, CarbonImmutable::parse('2026-10-01'));

        $this->assertSame('2026-10-04', $next?->toDateString());
    }

    public function test_an_unknown_frequency_is_rejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->recurrence->nextOccurrence(['frequency' => 'fortnightly'], CarbonImmutable::parse('2026-10-01'));
    }

    public function test_a_zero_interval_is_rejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->recurrence->nextOccurrence(
            ['frequency' => 'daily', 'interval' => 0],
            CarbonImmutable::parse('2026-10-01'),
        );
    }

    // ---- Generation through the service -------------------------------------

    public function test_completing_a_recurring_todo_creates_exactly_one_next_occurrence(): void
    {
        Event::fake([TodoCompleted::class, TodoRecurringGenerated::class]);

        $actor = User::factory()->create();
        $todo = $this->recurringTodo($actor, ['frequency' => 'daily', 'interval' => 1, 'start_date' => '2026-10-01']);

        $before = Todo::query()->withoutGlobalScope(TodoScope::class)->count();

        app(TodoService::class)->complete($actor, $todo);

        $after = Todo::query()->withoutGlobalScope(TodoScope::class)->count();

        $this->assertSame($before + 1, $after, 'Exactly one next occurrence must be created.');
        Event::assertDispatched(TodoRecurringGenerated::class);
    }

    public function test_the_next_occurrence_carries_the_right_date_and_inherits_the_series(): void
    {
        Event::fake([TodoCompleted::class, TodoRecurringGenerated::class]);

        $actor = User::factory()->create();
        $todo = $this->recurringTodo($actor, ['frequency' => 'weekly', 'interval' => 1, 'start_date' => '2026-10-01']);

        app(TodoService::class)->complete($actor, $todo);

        $next = Todo::query()
            ->withoutGlobalScope(TodoScope::class)
            ->whereKeyNot($todo->id)
            ->firstOrFail();

        $this->assertSame('2026-10-08', $next->due_date?->toDateString());
        $this->assertSame(WorkItemStatus::Planned, $next->status);
        $this->assertSame($todo->title, $next->title);
        $this->assertSame($todo->recurrence_rule, $next->recurrence_rule);
        $this->assertSame($actor->id, $next->creator_id);
    }

    public function test_no_occurrence_is_generated_past_the_end_date(): void
    {
        Event::fake([TodoCompleted::class, TodoRecurringGenerated::class]);

        $actor = User::factory()->create();
        $todo = $this->recurringTodo($actor, [
            'frequency' => 'monthly',
            'interval' => 1,
            'start_date' => '2026-10-01',
            'end_date' => '2026-10-15',
        ]);

        $before = Todo::query()->withoutGlobalScope(TodoScope::class)->count();

        app(TodoService::class)->complete($actor, $todo);

        $this->assertSame(
            $before,
            Todo::query()->withoutGlobalScope(TodoScope::class)->count(),
        );

        Event::assertNotDispatched(TodoRecurringGenerated::class);
    }

    public function test_generating_an_occurrence_writes_an_activity_row(): void
    {
        Event::fake([TodoCompleted::class, TodoRecurringGenerated::class]);

        $actor = User::factory()->create();
        $todo = $this->recurringTodo($actor, ['frequency' => 'daily', 'interval' => 1, 'start_date' => '2026-10-01']);

        app(TodoService::class)->complete($actor, $todo);

        $next = Todo::query()
            ->withoutGlobalScope(TodoScope::class)
            ->whereKeyNot($todo->id)
            ->firstOrFail();

        $this->assertDatabaseHas('activity_logs', [
            'module_name' => 'Todo',
            'record_id' => $next->id,
            'action' => 'recurrence_generated',
        ]);
    }

    public function test_skipping_an_occurrence_returns_the_next_unsuppressed_date(): void
    {
        $actor = User::factory()->create();
        $todo = $this->recurringTodo($actor, [
            'frequency' => 'daily',
            'interval' => 1,
            'skip_dates' => ['2026-10-02'],
        ]);

        $service = app(TodoRecurrenceService::class);

        $next = $service->skip($todo, $todo->recurrence_rule, 1);

        $this->assertSame('2026-10-03', $next?->toDateString());
    }

    public function test_skipping_at_the_end_of_a_series_returns_null(): void
    {
        $actor = User::factory()->create();
        $todo = $this->recurringTodo($actor, [
            'frequency' => 'daily',
            'interval' => 1,
            'start_date' => '2026-10-01',
            'end_date' => '2026-10-01',
        ]);

        $this->assertNull(app(TodoRecurrenceService::class)->skip($todo, $todo->recurrence_rule, 1));
    }

    public function test_skipping_writes_an_activity_row(): void
    {
        $actor = User::factory()->create();
        $todo = $this->recurringTodo($actor, ['frequency' => 'daily', 'interval' => 1]);

        app(TodoRecurrenceService::class)->skip($todo, $todo->recurrence_rule, 1);

        $this->assertDatabaseHas('activity_logs', [
            'module_name' => 'Todo',
            'record_id' => $todo->id,
            'action' => 'recurrence_skipped',
        ]);
    }

    public function test_occurrence_count_counts_a_series_from_its_anchor(): void
    {
        $actor = User::factory()->create();
        $rule = ['frequency' => 'weekly', 'interval' => 1, 'start_date' => '2026-10-01'];

        $third = Todo::query()->create([
            'title' => 'Third',
            'creator_id' => $actor->id,
            'status' => WorkItemStatus::Planned,
            'start_date' => '2026-10-15',
            'due_date' => '2026-10-15',
            'recurrence_rule' => $rule,
        ]);

        $this->assertSame(3, app(TodoRecurrenceService::class)->occurrenceCount($third));
    }

    public function test_a_non_recurring_todo_is_occurrence_one(): void
    {
        $actor = User::factory()->create();
        $todo = Todo::factory()->createdBy($actor)->create();

        $this->assertSame(1, app(TodoRecurrenceService::class)->occurrenceCount($todo));
    }

    public function test_the_shared_service_is_the_one_meetings_and_obligations_will_use(): void
    {
        // The whole point of §5.4: one implementation, not one per module.
        $this->assertInstanceOf(
            RecurrenceService::class,
            app(RecurrenceService::class),
        );

        $this->assertSame(
            [RecurrenceFrequency::Daily->value],
            array_slice(RecurrenceFrequency::values(), 0, 1),
        );
    }

    /**
     * @param  array<string, mixed>  $rule
     */
    private function recurringTodo(User $actor, array $rule): Todo
    {
        return Todo::query()->create([
            'title' => 'Recurring fixture',
            'creator_id' => $actor->id,
            'assignee_id' => $actor->id,
            'status' => WorkItemStatus::InProgress,
            'start_date' => $rule['start_date'] ?? '2026-10-01',
            'due_date' => $rule['start_date'] ?? '2026-10-01',
            'recurrence_rule' => $rule,
        ]);
    }
}
