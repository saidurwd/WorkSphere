<?php

namespace Modules\Todos\Models;

use App\Enums\Priority;
use App\Enums\Visibility;
use App\Enums\WorkItemStatus;
use App\Models\Department;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * To-Do — DATABASE-ARCHITECTURE.md §4.1, TODO-MODULE-SPECIFICATION.md §1.
 *
 * Phase 3 delivers the schema and the model that maps the columns, so the factory
 * and the cast behaviour are testable. Relations that need their own model
 * (watchers, checklist items, links, comments, attachments, tags, reminders) and
 * the query scopes are Phase 4 work and are deliberately absent rather than
 * stubbed: a relation pointing at a class that does not exist would fail at
 * runtime, not at build time.
 *
 * Uses the SoftDeletes TRAIT rather than an attribute. There is no
 * `Illuminate\Database\Eloquent\Attributes\SoftDeletes` class in this Laravel
 * version; `Meeting` imports one, which is why Meetings hard delete today despite
 * their migration defining `deleted_at`.
 */
class Todo extends Model
{
    use HasFactory, SoftDeletes;

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
}
