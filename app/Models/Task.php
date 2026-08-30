<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['title', 'description', 'priority', 'status', 'due_date', 'completed_at', 'user_id', 'responsible_user_id', 'project_id', 'attachment', 'obligation_id', 'task_no'])]
#[Hidden(['pivot'])]
class Task extends Model
{
    protected function casts(): array
    {
        return [
            'due_date' => 'date',
            'completed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function responsibleUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsible_user_id');
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function taskTransfers(): HasMany
    {
        return $this->hasMany(TaskTransfer::class);
    }

    public function remarks(): HasMany
    {
        return $this->hasMany(TaskRemark::class);
    }

    public function isOverdue(): bool
    {
        return ! $this->completed_at && $this->due_date->isPast();
    }

    public function isToday(): bool
    {
        return $this->due_date->isToday();
    }

    public function isUpcoming(): bool
    {
        return ! $this->isOverdue() && ! $this->isToday() && $this->due_date->isFuture();
    }

    public function obligation(): BelongsTo
    {
        return $this->belongsTo(Obligation::class);
    }

    public function meetingActionItems(): HasMany
    {
        return $this->hasMany(MeetingActionItem::class, 'task_id');
    }
}
