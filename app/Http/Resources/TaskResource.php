<?php

namespace App\Http\Resources;

use App\Enums\Priority;
use App\Enums\WorkItemStatus;
use Illuminate\Http\Request;

/**
 * A Task.
 *
 * Only what a client displays: identity, state, ownership and the dates it
 * schedules by. The web list's transfer history and time-entry detail are
 * reachable through their own endpoints and are not smuggled in here.
 */
class TaskResource extends ApiResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => (int) $this->id,
            'task_no' => $this->task_no,
            'title' => (string) $this->title,
            'description' => $this->description,
            'status' => $this->status?->value,
            'priority' => $this->priority,
            'parent_id' => $this->parent_id,
            'project_id' => $this->project_id,
            'obligation_id' => $this->obligation_id,
            'user_id' => $this->user_id,
            'responsible_user_id' => $this->responsible_user_id,
            'creator' => $this->whenLoaded(
                'user',
                fn (): ?array => $this->person($this->user),
            ),
            'responsible_user' => $this->whenLoaded(
                'responsibleUser',
                fn (): ?array => $this->person($this->responsibleUser),
            ),
            'project' => $this->whenLoaded(
                'project',
                fn (): ?array => $this->project === null
                    ? null
                    : ['id' => (int) $this->project->id, 'name' => (string) $this->project->name],
            ),
            'due_date' => $this->due_date?->toDateString(),
            'estimated_minutes' => $this->estimated_minutes,
            'actual_minutes' => $this->actual_minutes,
            'completed_at' => $this->completed_at?->toIso8601String(),
            'is_overdue' => $this->resource->isOverdue(),
            'tags' => TagResource::collection($this->whenLoaded('tags')),
            'counts' => [
                'subtasks' => (int) ($this->subtasks_count ?? $this->resource->subtasks()->count()),
                'comments' => (int) ($this->shared_comments_count ?? $this->resource->sharedComments()->count()),
            ],
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
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
            'task_no' => self::nullableString(),
            'title' => ['type' => 'string'],
            'description' => self::nullableString(),
            'status' => ['type' => 'string', 'enum' => self::enumValues(WorkItemStatus::class)],
            'priority' => ['type' => 'string', 'enum' => self::enumValues(Priority::class)],
            'parent_id' => self::nullableInt(),
            'project_id' => self::nullableInt(),
            'obligation_id' => self::nullableInt(),
            'user_id' => self::nullableInt(),
            'responsible_user_id' => self::nullableInt(),
            'creator' => ['type' => ['object', 'null'], 'ref' => PersonResource::class],
            'responsible_user' => ['type' => ['object', 'null'], 'ref' => PersonResource::class],
            'project' => ['type' => ['object', 'null']],
            'due_date' => self::date(),
            'estimated_minutes' => self::nullableInt(),
            'actual_minutes' => self::nullableInt(),
            'completed_at' => self::dateTime(),
            'is_overdue' => ['type' => 'boolean'],
            'tags' => ['type' => 'array', 'items' => ['ref' => TagResource::class]],
            'counts' => ['type' => 'object'],
            'created_at' => self::dateTime(),
            'updated_at' => self::dateTime(),
        ];
    }
}
