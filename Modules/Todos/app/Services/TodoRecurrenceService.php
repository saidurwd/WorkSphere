<?php

namespace Modules\Todos\Services;

use App\Enums\Priority;
use App\Enums\Visibility;
use App\Enums\WorkItemStatus;
use App\Services\ActivityLogger;
use App\Services\RecurrenceService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Modules\Todos\Events\TodoRecurringGenerated;
use Modules\Todos\Models\Todo;

/**
 * To-Do recurrence — TODO-MODULE-SPECIFICATION.md §5.
 *
 * Rule-on-row, materialise-on-completion (§5.1): there is no `todo_occurrences`
 * table in v1. The arithmetic itself is delegated to the shared
 * {@see RecurrenceService}; this class owns only the To-Do-specific policy:
 * how many occurrences have happened, when the series stops, and what the next
 * row looks like.
 */
class TodoRecurrenceService
{
    /**
     * Hard bound on any occurrence walk. A rule can produce at most this many
     * steps before the arithmetic is treated as a cycle rather than trusted.
     */
    private const OCCURRENCE_GUARD = 1000;

    public function __construct(
        private readonly RecurrenceService $recurrence,
        private readonly ActivityLogger $activity,
    ) {}

    /**
     * Generate the next occurrence after a To-Do has been completed.
     *
     * Returns null when the series is finished — `max_occurrences` reached, or
     * the next date would fall past `end_date`. The caller must not treat that
     * as a failure; it is the normal end of a series.
     *
     * The whole thing runs in a transaction and writes an activity row, so a
     * half-created occurrence cannot exist without its audit trail.
     */
    public function advance(Todo $completed, int $occurrenceNumber): ?Todo
    {
        $rule = $completed->recurrence_rule;

        if ($rule === null) {
            return null;
        }

        return DB::transaction(function () use ($completed, $rule, $occurrenceNumber): ?Todo {
            $nextDate = $this->recurrence->nextOccurrence($rule, $this->anchorFor($completed), $occurrenceNumber);

            if ($nextDate === null) {
                $this->activity->record(
                    Todo::class,
                    $completed,
                    'recurrence_stopped',
                    null,
                    ['reason' => $this->stopReason($rule, $occurrenceNumber)],
                );

                return null;
            }

            $next = Todo::query()->create([
                'title' => $completed->title,
                'description' => $completed->description,
                'status' => WorkItemStatus::Planned,
                // A column left to its schema default is absent from the in-memory
                // model, so `$completed->priority` is null for a To-Do that never
                // set one. Copying that null would violate the NOT NULL column, so
                // the enum default is used instead of the missing attribute.
                'priority' => $completed->priority ?? Priority::Medium,
                'visibility' => $completed->visibility ?? Visibility::Personal,
                'assignee_id' => $completed->assignee_id,
                'creator_id' => $completed->creator_id,
                'department_id' => $completed->department_id,
                'start_date' => $nextDate->toDateString(),
                'due_date' => $nextDate->toDateString(),
                'due_time' => $completed->due_time,
                'recurrence_rule' => $rule,
                'previous_occurrence_at' => $completed->start_date?->toDateString(),
            ]);

            $this->activity->record(
                Todo::class,
                $next,
                'recurrence_generated',
                null,
                [
                    'previous_todo_id' => $completed->getKey(),
                    'occurrence' => $occurrenceNumber + 1,
                    'due_date' => $nextDate->toDateString(),
                ],
            );

            // Dispatched from the service, never from a controller or a listener.
            TodoRecurringGenerated::dispatch($next, $completed->getKey());

            return $next;
        });
    }

    /**
     * Move a single occurrence forward without completing it — the
     * `todos:skip` command's operation.
     *
     * Returns the new date, or null when the series is finished. The To-Do row
     * is not touched: skipping one occurrence of a series changes when the next
     * one is due, not whether the current one exists.
     *
     * @param  array<string, mixed>  $rule
     */
    public function skip(Todo $todo, array $rule, int $occurrenceNumber): ?CarbonImmutable
    {
        $anchor = $this->anchorFor($todo);

        $next = $this->recurrence->nextUnskipped($rule, $anchor, $occurrenceNumber);

        if ($next === null) {
            $this->activity->record(
                Todo::class,
                $todo,
                'recurrence_skipped',
                null,
                ['reason' => $this->stopReason($rule, $occurrenceNumber), 'ended' => true],
            );

            return null;
        }

        $this->activity->record(
            Todo::class,
            $todo,
            'recurrence_skipped',
            ['start_date' => $anchor->toDateString()],
            ['start_date' => $next->toDateString(), 'ended' => false],
        );

        return $next;
    }

    /**
     * The date the series is measured from.
     *
     * §5.2 puts the anchor in the rule, and that is the correct source: a row's
     * own `start_date` is the date of *this* occurrence, which is later in the
     * series than the anchor. Reading the row would make every generated
     * occurrence look like occurrence one.
     *
     * Falls back to the row's own dates for a hand-created recurring To-Do whose
     * rule predates the anchor convention.
     */
    public function anchorFor(Todo $todo): CarbonImmutable
    {
        $ruleAnchor = $todo->recurrence_rule['start_date'] ?? null;

        $anchor = $ruleAnchor
            ?? $todo->start_date
            ?? $todo->due_date
            ?? $todo->created_at;

        return CarbonImmutable::parse($anchor)->startOfDay();
    }

    /**
     * How many occurrences of this series have been produced so far, counting
     * the current row.
     *
     * Derived by walking forward from the anchor and stopping once the cursor
     * passes the current row, rather than stored: a stored counter goes stale the
     * moment a row is deleted or skipped, and there is nothing that would keep it
     * honest. The guard bounds a pathological rule rather than trusting it.
     */
    public function occurrenceCount(Todo $todo): int
    {
        if (! $todo->isRecurring()) {
            return 1;
        }

        $anchor = $this->anchorFor($todo);
        $current = CarbonImmutable::parse($todo->start_date ?? $todo->due_date ?? $todo->created_at)->startOfDay();

        if ($current->lessThanOrEqualTo($anchor)) {
            return 1;
        }

        $count = 1;
        $cursor = $anchor;

        for ($i = 0; $i < self::OCCURRENCE_GUARD; $i++) {
            $next = $this->recurrence->nextOccurrence($todo->recurrence_rule, $cursor, $count);

            if ($next === null || $next->greaterThan($current)) {
                break;
            }

            $cursor = $next;
            $count++;
        }

        return $count;
    }

    /**
     * Why a series stopped, for the activity log. Never contains the rule itself:
     * log lines must stay free of payload data.
     *
     * @param  array<string, mixed>  $rule
     */
    protected function stopReason(array $rule, int $occurrenceNumber): string
    {
        if (isset($rule['max_occurrences']) && $occurrenceNumber >= (int) $rule['max_occurrences']) {
            return 'max_occurrences_reached';
        }

        return 'end_date_reached';
    }
}
