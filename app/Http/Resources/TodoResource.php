<?php

namespace App\Http\Resources;

use App\Enums\Priority;
use App\Enums\Visibility;
use App\Enums\WorkItemStatus;
use Illuminate\Http\Request;

/**
 * A To-Do — TODO-MODULE-SPECIFICATION.md §9.2.
 *
 * `recurrence_rule` is deliberately NOT exposed. Its stored value is a JSON blob
 * whose keys are the recurrence service's internal vocabulary (`by_weekday`,
 * `skip_dates`, `max_occurrences`); a client that reads it couples itself to that
 * shape and breaks whenever the engine is refactored. What a client actually
 * needs is `is_recurring` — is there more of this coming? — which is answered
 * here without pinning anyone to the storage format.
 *
 * `deleted_at` is absent for the same reason the audit columns are: a soft-deleted
 * To-Do is not a To-Do, and a client that can read tombstones can enumerate
 * deletions.
 *
 * Note the counts: they are computed from `withCount()` columns when the caller
 * eager-loaded them and fall back to a per-record count otherwise. The index
 * endpoint always eager-loads, so a list of 25 is 25 rows in three queries rather
 * than 75 — see `TodoNPlusOneTest`.
 */
class TodoResource extends ApiResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => (int) $this->id,
            'title' => (string) $this->title,
            'description' => $this->description,
            'status' => $this->status?->value,
            'status_label' => $this->status?->label(),
            'priority' => $this->priority?->value,
            'visibility' => $this->visibility?->value,
            'assignee_id' => $this->assignee_id,
            'creator_id' => (int) $this->creator_id,
            'department_id' => $this->department_id,
            'assignee' => $this->whenLoaded(
                'assignee',
                fn (): ?array => $this->person($this->assignee),
            ),
            'creator' => $this->whenLoaded(
                'creator',
                fn (): ?array => $this->person($this->creator),
            ),
            'start_date' => $this->start_date?->toDateString(),
            'due_date' => $this->due_date?->toDateString(),
            'due_time' => $this->due_time,
            'estimated_minutes' => $this->estimated_minutes,
            'actual_minutes' => $this->actual_minutes,
            'waiting_on' => $this->waiting_on,
            'archived_from' => $this->archived_from,
            'completed_at' => $this->completed_at?->toIso8601String(),
            'completed_by' => $this->completed_by,
            'is_recurring' => $this->resource->isRecurring(),
            'color' => $this->color,
            'checklist' => $this->when(
                $this->relationLoaded('checklistItems'),
                fn (): array => $this->checklistSummary(),
            ),
            'counts' => [
                'comments' => $this->relatedCount('comments'),
                'checklist_items' => $this->relatedCount('checklistItems'),
            ],
            'tags' => TagResource::collection($this->whenLoaded('tags')),
            'links' => $this->when(
                $this->relationLoaded('links'),
                fn (): array => $this->linkSummary(),
            ),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }

    /**
     * Checklist progress is COMPUTED, never stored (§4.3), so it is derived from
     * whichever form of the relation is loaded.
     *
     * @return array{completed: int, total: int, percent: int}
     */
    protected function checklistSummary(): array
    {
        [$completed, $total, $percent] = $this->resource->checklistProgress();

        return ['completed' => $completed, 'total' => $total, 'percent' => $percent];
    }

    /**
     * @return list<array{type: string, linkable_type: string, linkable_id: int}>
     */
    protected function linkSummary(): array
    {
        return $this->resource->links
            ->map(fn ($link): array => [
                'type' => (string) $link->link_type,
                'linkable_type' => (string) $link->linkable_type,
                'linkable_id' => (int) $link->linkable_id,
            ])
            ->values()
            ->all();
    }

    /**
     * A `withCount()` alias when present, a live count otherwise.
     *
     * The fallback exists so the single-record endpoint can be called without
     * the caller knowing which eager loads the index endpoint happens to use.
     */
    protected function relatedCount(string $relation): int
    {
        $snake = Str($relation)->snake()->plural()->toString();

        if (array_key_exists($snake.'_count', $this->resource->getAttributes())) {
            return (int) $this->resource->getAttribute($snake.'_count');
        }

        return $this->resource->{$relation}()->count();
    }

    /**
     * @return array{id: int, name: string}|null
     */
    protected function person(?object $user): ?array
    {
        return $user === null
            ? null
            : ['id' => (int) $user->id, 'name' => (string) $user->name];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public static function schema(): array
    {
        return [
            'id' => ['type' => 'integer'],
            'title' => ['type' => 'string'],
            'description' => self::nullableString(),
            'status' => ['type' => 'string', 'enum' => self::enumValues(WorkItemStatus::class)],
            'status_label' => ['type' => 'string'],
            'priority' => ['type' => 'string', 'enum' => self::enumValues(Priority::class)],
            'visibility' => ['type' => 'string', 'enum' => self::enumValues(Visibility::class)],
            'assignee_id' => self::nullableInt(),
            'creator_id' => ['type' => 'integer'],
            'department_id' => self::nullableInt(),
            'assignee' => ['type' => ['object', 'null'], 'ref' => PersonResource::class],
            'creator' => ['type' => ['object', 'null'], 'ref' => PersonResource::class],
            'start_date' => self::date(),
            'due_date' => self::date(),
            'due_time' => self::nullableString(),
            'estimated_minutes' => self::nullableInt(),
            'actual_minutes' => self::nullableInt(),
            'waiting_on' => self::nullableString(),
            'archived_from' => self::nullableString(),
            'completed_at' => self::dateTime(),
            'completed_by' => self::nullableInt(),
            'is_recurring' => ['type' => 'boolean'],
            'color' => self::nullableString(),
            'checklist' => ['type' => 'object'],
            'counts' => ['type' => 'object'],
            'tags' => ['type' => 'array', 'items' => ['ref' => TagResource::class]],
            'links' => ['type' => 'array'],
            'created_at' => self::dateTime(),
            'updated_at' => self::dateTime(),
        ];
    }
}
