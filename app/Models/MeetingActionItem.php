<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MeetingActionItem extends Model
{
    protected $fillable = [
        'meeting_id',
        'agenda_id',
        'discussion_id',
        'decision_id',
        'action_no',
        'title',
        'description',
        'assigned_to',
        'assigned_department_id',
        'priority',
        'start_date',
        'due_date',
        'status',
        'completion_percentage',
        'task_id',
        'completed_at',
        'completed_by',
        'remarks',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'due_date' => 'date',
            'completed_at' => 'datetime',
            'completion_percentage' => 'integer',
        ];
    }

    public function meeting(): BelongsTo
    {
        return $this->belongsTo(Meeting::class);
    }

    public function agenda(): BelongsTo
    {
        return $this->belongsTo(MeetingAgenda::class);
    }

    public function discussion(): BelongsTo
    {
        return $this->belongsTo(MeetingDiscussion::class);
    }

    public function decision(): BelongsTo
    {
        return $this->belongsTo(MeetingDecision::class);
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function assignedDepartment(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'assigned_department_id');
    }

    public function completedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'completed_by');
    }

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    public function isOverdue(): bool
    {
        return ! $this->completed_at
            && ! in_array($this->status, ['completed', 'cancelled'], true)
            && $this->due_date
            && $this->due_date->isPast();
    }

    public function scopeOpen($query)
    {
        return $query->where('status', 'open');
    }

    public function scopeInProgress($query)
    {
        return $query->where('status', 'in_progress');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeOverdue($query)
    {
        return $query->where('status', '!=', 'completed')
            ->where('status', '!=', 'cancelled')
            ->where('due_date', '<', now()->startOfDay());
    }
}
