<?php

namespace Modules\Tasks\Models;

use App\Enums\WorkItemStatus;
use App\Models\ActivityLog;
use App\Models\Comment;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Support\Collection;
use Modules\Meetings\Models\MeetingActionItem;
use Modules\Obligations\Models\Obligation;
use Modules\Projects\Models\Project;

/**
 * Task — GAP-025/026.
 *
 * `tasks.status` was widened to `pending | in_progress | on_hold | completed |
 * cancelled` to match `meeting_action_items`, and `due_date` is now nullable: a
 * task with no deadline is legitimate, not a placeholder.
 *
 * Per GAP-027 a meeting action item stays its own record rather than becoming a
 * Task. The two already link (`meeting_action_items.task_id`), and merging them
 * would break historical ids and force one of the two shapes to lose its meaning.
 */
class Task extends Model
{
    use HasFactory;

    /**
     * The original list, plus the Phase 8 columns. `completed_at` stays fillable:
     * TaskController stamps it on completion and clears it on reopen, and under
     * `preventSilentlyDiscardingAttributes` an omission there is an exception
     * rather than a silently ignored key.
     */
    protected $fillable = [
        'title',
        'description',
        'priority',
        'status',
        'due_date',
        'completed_at',
        'responsible_user_id',
        'user_id',
        'project_id',
        'obligation_id',
        'attachment',
        'task_no',
        'parent_id',
        'estimated_minutes',
        'actual_minutes',
    ];

    protected function casts(): array
    {
        return [
            'status' => WorkItemStatus::class,
            'due_date' => 'date',
            'completed_at' => 'datetime',
            'estimated_minutes' => 'integer',
            'actual_minutes' => 'integer',
        ];
    }

    // ---- Relations ---------------------------------------------------------

    /**
     * The task's author. Named `user()` because that is what every existing call
     * site uses; `creator()` is an alias so new code reads better.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function creator(): BelongsTo
    {
        return $this->user();
    }

    public function responsibleUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsible_user_id');
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function obligation(): BelongsTo
    {
        return $this->belongsTo(Obligation::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function subtasks(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function watchers(): HasMany
    {
        return $this->hasMany(TaskWatcher::class);
    }

    public function timeEntries(): HasMany
    {
        return $this->hasMany(TimeEntry::class);
    }

    public function tags(): MorphToMany
    {
        return $this->morphToMany(Tag::class, 'taggable')->withTimestamps();
    }

    /**
     * Shared comments — GAP-048.
     *
     * `remarks()` still reads `task_remarks`, which every existing screen uses;
     * this is the platform-table view that makes one comment stream span modules.
     */
    public function sharedComments(): MorphMany
    {
        return $this->morphMany(Comment::class, 'commentable');
    }

    public function activityLogs(): HasMany
    {
        return $this->hasMany(ActivityLog::class, 'record_id')
            ->where('activity_logs.module_name', self::class)
            ->latest('id');
    }

    /**
     * The existing name. `transfers()` is the alias; renaming the original would
     * break every view, controller and mail that calls it.
     */
    public function taskTransfers(): HasMany
    {
        return $this->hasMany(TaskTransfer::class);
    }

    public function transfers(): HasMany
    {
        return $this->taskTransfers();
    }

    /**
     * Legacy remarks on `task_remarks` — GAP-048.
     *
     * Still the read path for every existing screen and mail. `sharedComments()`
     * is the platform-table view; Phase 8 backfills, Phase 15 drops.
     */
    public function remarks(): HasMany
    {
        return $this->hasMany(TaskRemark::class);
    }

    public function meetingActionItems(): HasMany
    {
        return $this->hasMany(MeetingActionItem::class);
    }

    // ---- Scopes ------------------------------------------------------------

    /**
     * @param  Builder<self>  $query
     */
    public function scopeForUser(Builder $query, User $user): void
    {
        $query->where(function (Builder $inner) use ($user): void {
            $inner->where('tasks.user_id', $user->id)
                ->orWhere('tasks.responsible_user_id', $user->id)
                ->orWhereHas('watchers', fn (Builder $watchers): Builder => $watchers->where('user_id', $user->id));
        });
    }

    /**
     * Everything still open. `completed` and `cancelled` are terminal.
     *
     * @param  Builder<self>  $query
     */
    public function scopeActive(Builder $query): void
    {
        $query->whereIn($query->qualifyColumn('status'), [
            WorkItemStatus::Pending->value,
            WorkItemStatus::InProgress->value,
            WorkItemStatus::OnHold->value,
        ]);
    }

    /**
     * @param  Builder<self>  $query
     */
    public function scopeOverdue(Builder $query): void
    {
        $query->active()
            ->whereNotNull($query->qualifyColumn('due_date'))
            ->whereDate($query->qualifyColumn('due_date'), '<', now()->toDateString());
    }

    /**
     * Open work due within an inclusive date range.
     *
     * Present on both Task and Todo so a widget spanning the two can call the
     * same scope on either — the absence of this is what made
     * `UpcomingDeadlinesWidget` fail against Tasks.
     *
     * @param  Builder<self>  $query
     */
    public function scopeDueBetween(Builder $query, string $from, string $to): void
    {
        $query->active()
            ->whereNotNull($query->qualifyColumn('due_date'))
            ->whereDate($query->qualifyColumn('due_date'), '>=', $from)
            ->whereDate($query->qualifyColumn('due_date'), '<=', $to);
    }

    /**
     * @param  Builder<self>  $query
     */
    public function scopeTopLevel(Builder $query): void
    {
        $query->whereNull('parent_id');
    }

    /**
     * @param  Builder<self>  $query
     * @param  WorkItemStatus|list<string>|string  $status
     */
    public function scopeStatus(Builder $query, WorkItemStatus|array|string $status): void
    {
        $values = match (true) {
            $status instanceof WorkItemStatus => [$status->value],
            is_array($status) => array_map(
                fn (mixed $item): string => $item instanceof WorkItemStatus ? $item->value : (string) $item,
                $status,
            ),
            default => [$status],
        };

        $query->whereIn($query->qualifyColumn('status'), $values);
    }

    // ---- Sub-tasks ---------------------------------------------------------

    /**
     * Whether making `$candidateParentId` this task's parent would create a cycle.
     *
     * Walks up from the proposed parent; if it reaches this task, the move would
     * make the chain circular. A self-parent is the shortest case and is covered
     * by the same walk.
     *
     * This is the *only* cycle enforcement point. A database constraint cannot
     * express it portably, so every caller must go through here — which is why it
     * is a named method rather than a `while` loop at the call site.
     */
    public function wouldCreateCycle(?int $candidateParentId): bool
    {
        if ($candidateParentId === null) {
            return false;
        }

        if ($this->exists && $candidateParentId === $this->getKey()) {
            return true;
        }

        $seen = [];
        $cursor = $candidateParentId;

        // Bounded by the number of tasks: a chain cannot legitimately be longer,
        // and the guard turns a corrupt cycle into a return rather than a hang.
        for ($hop = 0; $cursor !== null && $hop < 1000; $hop++) {
            if (isset($seen[$cursor])) {
                // A pre-existing cycle in the data. Refuse rather than loop.
                return true;
            }

            $seen[$cursor] = true;

            if ($this->exists && $cursor === $this->getKey()) {
                return true;
            }

            $cursor = static::query()
                ->whereKey($cursor)
                ->value('parent_id');
        }

        return false;
    }

    /**
     * The chain of ancestors, nearest first.
     *
     * @return Collection<int, self>
     */
    public function ancestors(): Collection
    {
        $chain = collect();
        $cursor = $this->parent_id;
        $seen = [];

        while ($cursor !== null && ! isset($seen[$cursor])) {
            $seen[$cursor] = true;

            $parent = static::query()->find($cursor);

            if ($parent === null) {
                break;
            }

            $chain->push($parent);
            $cursor = $parent->parent_id;
        }

        return $chain;
    }

    /**
     * Every descendant, breadth-first, cycle-safe.
     *
     * @return Collection<int, self>
     */
    public function descendants(): Collection
    {
        $found = collect();
        $queue = $this->subtasks()->pluck('id')->all();

        while ($queue !== []) {
            $id = array_shift($queue);

            if ($found->contains('id', $id)) {
                continue;
            }

            $child = static::query()->find($id);

            if ($child === null) {
                continue;
            }

            $found->push($child);
            $queue = array_merge($queue, $child->subtasks()->pluck('id')->all());
        }

        return $found;
    }

    public function isOverdue(): bool
    {
        return $this->due_date !== null
            && $this->due_date->isPast()
            && $this->status?->isOpen() === true;
    }

    /**
     * Due today. False for an undated task — a task with no deadline is not
     * "due today", it simply has no date.
     */
    public function isToday(): bool
    {
        return $this->due_date !== null
            && $this->due_date->isToday();
    }

    public function isUpcoming(): bool
    {
        return $this->due_date !== null
            && $this->due_date->isFuture();
    }

    /**
     * Total minutes logged, recomputed from `time_entries`.
     *
     * The stored `actual_minutes` is a cache of exactly this sum; the method is
     * the definition, and `syncActualMinutes()` keeps the cache honest.
     */
    public function loggedMinutes(): int
    {
        return (int) $this->timeEntries()->sum('minutes');
    }

    public function syncActualMinutes(): void
    {
        $this->forceFill(['actual_minutes' => $this->loggedMinutes()])->saveQuietly();
    }
}
