<?php

namespace Modules\Todos\Services;

use App\Enums\WorkItemStatus;
use App\Models\User;
use App\Services\ActivityLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Todos\Events\TodoAssigned;
use Modules\Todos\Events\TodoCompleted;
use Modules\Todos\Events\TodoReassigned;
use Modules\Todos\Events\TodoReopened;
use Modules\Todos\Models\Todo;

/**
 * To-Do lifecycle — TODO-MODULE-SPECIFICATION.md §3.2.
 *
 * Every method takes an already-authorised `User $actor`. The service does not
 * re-implement permission logic — that is TodoPolicy's job — but it does require
 * the actor, so a queued job or a console command can act on someone's behalf
 * and have the activity trail name a real person rather than "system".
 *
 * Every method runs in a transaction and writes exactly one `activity_logs` row
 * carrying old and new values. Events are dispatched from here, never from a
 * controller, so no entry point can mutate state silently.
 *
 * Illegal transitions throw. They do not silently no-op: a transition that
 * quietly does nothing is indistinguishable from a lost write.
 */
class TodoService
{
    /**
     * Allowed transitions, §3.2. Anything not listed here is rejected.
     *
     * @var array<string, list<string>>
     */
    private const TRANSITIONS = [
        'inbox' => ['planned', 'in_progress', 'waiting', 'archived'],
        'planned' => ['in_progress', 'waiting', 'inbox', 'archived'],
        'in_progress' => ['completed', 'waiting', 'planned', 'archived'],
        'waiting' => ['in_progress', 'planned', 'inbox', 'archived'],
        'completed' => ['in_progress', 'archived'],
        'archived' => ['inbox', 'planned'],
    ];

    public function __construct(
        private readonly ActivityLogger $activity,
        private readonly TodoRecurrenceService $recurrence,
    ) {}

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function create(User $actor, array $attributes): Todo
    {
        return DB::transaction(function () use ($actor, $attributes): Todo {
            $todo = Todo::query()->create([
                ...$attributes,
                'creator_id' => $actor->id,
                'status' => $attributes['status'] ?? WorkItemStatus::Inbox,
            ]);

            // A To-Do assigned to somebody else at creation needs the assignee
            // told; one left in the creator's own inbox does not.
            if ($todo->assignee_id !== null && $todo->assignee_id !== $actor->id) {
                TodoAssigned::dispatch($todo, $actor->id);
            }

            return $todo;
        });
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function update(User $actor, Todo $todo, array $attributes): Todo
    {
        return DB::transaction(function () use ($actor, $todo, $attributes): Todo {
            $before = $todo->only(array_keys($attributes));

            // Status is never set through a plain update: it goes through
            // transition(), which enforces §3.2 and the Waiting/Completed
            // side-effects. Letting update() bypass that would make the state
            // machine advisory.
            unset($attributes['status']);

            $todo->fill($attributes)->save();

            $after = $todo->only(array_keys($attributes));

            $this->log($todo, 'updated', $before, $after, $actor);

            return $todo;
        });
    }

    /**
     * Reassign ownership. `assignee_id` from the request is never trusted on its
     * own — the caller authorises through `assign` first.
     */
    public function assign(User $actor, Todo $todo, ?int $assigneeId): Todo
    {
        return DB::transaction(function () use ($actor, $todo, $assigneeId): Todo {
            $previous = $todo->assignee_id;

            $todo->forceFill(['assignee_id' => $assigneeId])->save();

            $this->log($todo, 'assigned', ['assignee_id' => $previous], ['assignee_id' => $assigneeId], $actor);

            if ($previous === $assigneeId) {
                return $todo;
            }

            if ($previous !== null) {
                TodoReassigned::dispatch($todo, $previous, $actor->id);
            }

            if ($assigneeId !== null) {
                TodoAssigned::dispatch($todo, $actor->id);
            }

            return $todo;
        });
    }

    /**
     * Mark a To-Do done. Sets `completed_at` / `completed_by`, and — for a
     * recurring To-Do — materialises the next occurrence (§5.1).
     */
    public function complete(User $actor, Todo $todo): Todo
    {
        return DB::transaction(function () use ($actor, $todo): Todo {
            $before = ['status' => $todo->status?->value];

            $this->assertTransition($todo, WorkItemStatus::Completed);

            $todo->forceFill([
                'status' => WorkItemStatus::Completed,
                'completed_at' => now(),
                'completed_by' => $actor->id,
                'waiting_on' => null,
            ])->save();

            $this->log($todo, 'completed', $before, [
                'status' => $todo->status->value,
                'completed_at' => $todo->completed_at?->toDateTimeString(),
                'completed_by' => $actor->id,
            ], $actor);

            TodoCompleted::dispatch($todo, $actor->id);

            if ($todo->isRecurring()) {
                // Inside this transaction on purpose: a generated occurrence
                // without its activity row would be invisible to the timeline.
                $this->recurrence->advance($todo, $this->recurrence->occurrenceCount($todo));
            }

            return $todo;
        });
    }

    /**
     * Undo a completion. Both `completed_at` and `completed_by` are cleared —
     * leaving either behind produces a record that claims to be done by someone
     * while sitting in an open status.
     */
    public function reopen(User $actor, Todo $todo): Todo
    {
        return DB::transaction(function () use ($actor, $todo): Todo {
            $before = [
                'status' => $todo->status?->value,
                'completed_at' => $todo->completed_at?->toDateTimeString(),
                'completed_by' => $todo->completed_by,
            ];

            $target = WorkItemStatus::InProgress;
            $this->assertTransition($todo, $target);

            $todo->forceFill([
                'status' => $target,
                'completed_at' => null,
                'completed_by' => null,
            ])->save();

            $this->log($todo, 'reopened', $before, [
                'status' => $todo->status->value,
                'completed_at' => null,
                'completed_by' => null,
            ], $actor);

            TodoReopened::dispatch($todo, $actor->id);

            return $todo;
        });
    }

    /**
     * Archive from any non-archived state. The prior status is kept in
     * `archived_from` so unarchiving is a true reversal rather than a guess.
     */
    public function archive(User $actor, Todo $todo): Todo
    {
        return DB::transaction(function () use ($actor, $todo): Todo {
            $before = ['status' => $todo->status?->value, 'archived_from' => $todo->archived_from];

            if ($todo->status === WorkItemStatus::Archived) {
                throw ValidationException::withMessages([
                    'status' => 'This To-Do is already archived.',
                ]);
            }

            $todo->forceFill([
                'status' => WorkItemStatus::Archived,
                'archived_from' => $todo->status?->value,
            ])->save();

            $this->log($todo, 'archived', $before, [
                'status' => $todo->status->value,
                'archived_from' => $todo->archived_from,
            ], $actor);

            return $todo;
        });
    }

    /**
     * Return an archived To-Do to the state it held before archiving.
     */
    public function restore(User $actor, Todo $todo): Todo
    {
        return DB::transaction(function () use ($actor, $todo): Todo {
            $before = ['status' => $todo->status?->value, 'archived_from' => $todo->archived_from];

            if ($todo->status !== WorkItemStatus::Archived) {
                throw ValidationException::withMessages([
                    'status' => 'Only an archived To-Do can be restored.',
                ]);
            }

            $restoredTo = $todo->archivedFromStatus() ?? WorkItemStatus::Planned;

            $todo->forceFill([
                'status' => $restoredTo,
                'archived_from' => null,
            ])->save();

            $this->log($todo, 'restored', $before, [
                'status' => $todo->status->value,
                'archived_from' => null,
            ], $actor);

            return $todo;
        });
    }

    /**
     * Soft delete. `restore()` here is the archive/unarchive pair above; this is
     * the delete the policy's `delete` ability governs.
     */
    public function destroy(User $actor, Todo $todo): void
    {
        DB::transaction(function () use ($actor, $todo): void {
            $attributes = $todo->attributesToArray();

            $todo->delete();

            $this->log($todo, 'deleted', $attributes, null, $actor);
        });
    }

    /**
     * Move a To-Do to an arbitrary new status, enforcing §3.2.
     *
     * The only path by which `status` may change.
     */
    public function transition(User $actor, Todo $todo, WorkItemStatus $target): Todo
    {
        return match ($target) {
            WorkItemStatus::Completed => $this->complete($actor, $todo),
            WorkItemStatus::Archived => $this->archive($actor, $todo),
            WorkItemStatus::InProgress => $this->moveTo($actor, $todo, $target),
            default => $this->moveTo($actor, $todo, $target),
        };
    }

    protected function moveTo(User $actor, Todo $todo, WorkItemStatus $target): Todo
    {
        return DB::transaction(function () use ($actor, $todo, $target): Todo {
            $before = ['status' => $todo->status?->value, 'waiting_on' => $todo->waiting_on];

            $this->assertTransition($todo, $target);

            $attributes = ['status' => $target];

            // Waiting without a reason is meaningless and pollutes reporting
            // (§3.2), so the transition is refused rather than half-applied.
            $attributes['waiting_on'] = $target->requiresWaitingOn()
                ? $this->resolveWaitingOn($todo)
                : null;

            $todo->forceFill($attributes)->save();

            $this->log($todo, 'transitioned', $before, [
                'status' => $todo->status->value,
                'waiting_on' => $todo->waiting_on,
            ], $actor);

            return $todo;
        });
    }

    /**
     * §3.2: `Waiting` requires `waiting_on` — a user id or free text.
     */
    protected function resolveWaitingOn(Todo $todo): string
    {
        if ($todo->waiting_on !== null && $todo->waiting_on !== '') {
            return $todo->waiting_on;
        }

        throw ValidationException::withMessages([
            'waiting_on' => 'Moving a To-Do to Waiting requires saying what it is waiting on.',
        ]);
    }

    /**
     * @throws ValidationException when the move is not in the §3.2 graph
     */
    public function assertTransition(Todo $todo, WorkItemStatus $target): void
    {
        $from = $todo->status ?? WorkItemStatus::Inbox;

        if ($from === $target) {
            return;
        }

        $allowed = self::TRANSITIONS[$from->value] ?? [];

        if (! in_array($target->value, $allowed, true)) {
            throw ValidationException::withMessages([
                'status' => sprintf(
                    'A To-Do cannot move from %s to %s.',
                    $from->label(),
                    $target->label(),
                ),
            ]);
        }
    }

    /**
     * @return list<string>
     */
    public static function allowedTransitionsFrom(WorkItemStatus $from): array
    {
        return self::TRANSITIONS[$from->value] ?? [];
    }

    /**
     * @param  array<string, mixed>|null  $old
     * @param  array<string, mixed>|null  $new
     */
    protected function log(Todo $todo, string $action, ?array $old, ?array $new, ?User $actor = null): void
    {
        $this->activity->record(Todo::class, $todo, $action, $old, $new, $actor?->id);
    }
}
