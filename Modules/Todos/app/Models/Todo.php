<?php

namespace Modules\Todos\Models;

use App\Enums\LinkType;
use App\Enums\Priority;
use App\Enums\Visibility;
use App\Enums\WorkItemStatus;
use App\Models\ActivityLog;
use App\Models\Attachment;
use App\Models\Comment;
use App\Models\Department;
use App\Models\Reminder;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;
use Modules\Todos\Models\Scopes\TodoScope;

/**
 * To-Do — DATABASE-ARCHITECTURE.md §4.1, TODO-MODULE-SPECIFICATION.md §1.
 *
 * Uses the SoftDeletes TRAIT rather than an attribute. There is no
 * `Illuminate\Database\Eloquent\Attributes\SoftDeletes` class in this Laravel
 * version; `Meeting` imports one, which is why Meetings hard delete today despite
 * their migration defining `deleted_at`.
 *
 * `TodoScope` is applied globally so the list query and TodoPolicy::view cannot
 * disagree — the failure mode the existing three modules have.
 */
class Todo extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * @var array<int, class-string<Model>>
     */
    protected static function booted(): void
    {
        static::addGlobalScope(new TodoScope);
    }

    protected $fillable = [
        'title',
        'description',
        'status',
        'priority',
        'visibility',
        'assignee_id',
        'creator_id',
        'department_id',
        'start_date',
        'due_date',
        'due_time',
        'estimated_minutes',
        'actual_minutes',
        'completed_at',
        'completed_by',
        'archived_from',
        'waiting_on',
        'color',
        'sort_order',
        'recurrence_rule',
        'previous_occurrence_at',
        'last_reminded_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => WorkItemStatus::class,
            'priority' => Priority::class,
            'visibility' => Visibility::class,
            'recurrence_rule' => 'array',
            'start_date' => 'date',
            'due_date' => 'date',
            'previous_occurrence_at' => 'date',
            'completed_at' => 'datetime',
            'last_reminded_at' => 'datetime',
            'estimated_minutes' => 'integer',
            'actual_minutes' => 'integer',
            'sort_order' => 'integer',
        ];
    }

    // ---- Relations ---------------------------------------------------------

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assignee_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    public function completedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'completed_by');
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function watchers(): HasMany
    {
        return $this->hasMany(TodoWatcher::class);
    }

    public function checklistItems(): HasMany
    {
        return $this->hasMany(TodoChecklistItem::class)->orderBy('sort_order');
    }

    public function links(): HasMany
    {
        return $this->hasMany(TodoLink::class);
    }

    public function reminders(): MorphMany
    {
        return $this->morphMany(Reminder::class, 'subject');
    }

    public function comments(): MorphMany
    {
        return $this->morphMany(Comment::class, 'commentable');
    }

    public function attachments(): MorphMany
    {
        return $this->morphMany(Attachment::class, 'attachable');
    }

    public function tags(): MorphToMany
    {
        return $this->morphToMany(Tag::class, 'taggable')->withTimestamps();
    }

    public function activityLogs(): HasMany
    {
        return $this->hasMany(ActivityLog::class, 'record_id')
            ->where('activity_logs.module_name', self::class)
            ->latest('id');
    }

    /**
     * Every To-Do that points at this one, in either direction. Backed by
     * `todo_link_reverse_idx`.
     */
    public function reverseLinks(): HasMany
    {
        return $this->hasMany(TodoLink::class, 'linkable_id')
            ->where('linkable_type', self::class);
    }

    // ---- Scopes ------------------------------------------------------------

    /**
     * @param  Builder<self>  $query
     */
    public function scopeForUser(Builder $query, User $user): void
    {
        $query->withoutGlobalScope(TodoScope::class)->where(function (Builder $inner) use ($user): void {
            $inner->where('todos.assignee_id', $user->id)
                ->orWhere('todos.creator_id', $user->id);
        });
    }

    /**
     * Everything still open. Terminal states (Completed, Cancelled, Archived)
     * are excluded; the default inbox never shows finished work.
     *
     * @param  Builder<self>  $query
     */
    public function scopeActive(Builder $query): void
    {
        $query->whereIn($query->qualifyColumn('status'), WorkItemStatus::openValues());
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
    public function scopeRecurring(Builder $query): void
    {
        $query->whereNotNull('recurrence_rule');
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

    /**
     * @param  Builder<self>  $query
     */
    public function scopeSearch(Builder $query, ?string $term): void
    {
        if ($term === null || trim($term) === '') {
            return;
        }

        $like = '%'.trim($term).'%';

        $query->where(function (Builder $inner) use ($like): void {
            $inner->where('title', 'like', $like)
                ->orWhere('description', 'like', $like);
        });
    }

    // ---- Derived state -----------------------------------------------------

    /**
     * The status this To-Do held before it was archived, or null when it is not
     * archived. Restoring an archived To-Do puts it back here.
     */
    public function archivedFromStatus(): ?WorkItemStatus
    {
        return $this->archived_from === null
            ? null
            : WorkItemStatus::tryFrom($this->archived_from);
    }

    /**
     * @return array{int, int, int}
     */
    public function checklistProgress(): array
    {
        return TodoChecklistItem::progressFor($this->checklistItems);
    }

    public function isRecurring(): bool
    {
        return $this->recurrence_rule !== null;
    }

    /**
     * A team To-Do is the only visibility that widens the audience beyond the
     * To-Do's own parties.
     */
    public function isTeamVisible(): bool
    {
        return $this->visibility === Visibility::Team && $this->department_id !== null;
    }

    /**
     * Links to a record of a given type, used by the reverse-navigation resolver.
     *
     * @return Collection<int, TodoLink>
     */
    public function linksTo(string $type): Collection
    {
        return $this->links
            ->filter(fn (TodoLink $link): bool => $link->linkable_type === $type)
            ->values();
    }

    /**
     * Link types pointing away from this To-Do.
     *
     * @return list<LinkType>
     */
    public function linkTypes(): array
    {
        return $this->links
            ->pluck('link_type')
            ->map(fn (LinkType|string $type): LinkType => $type instanceof LinkType ? $type : LinkType::from($type))
            ->unique()
            ->values()
            ->all();
    }
}
