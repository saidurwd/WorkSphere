<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MeetingAgenda extends Model
{
    protected $fillable = [
        'meeting_id',
        'agenda_no',
        'title',
        'description',
        'presented_by',
        'estimated_minutes',
        'status',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'estimated_minutes' => 'integer',
            'sort_order' => 'integer',
        ];
    }

    public function meeting(): BelongsTo
    {
        return $this->belongsTo(Meeting::class);
    }

    public function presentedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'presented_by');
    }

    public function discussions(): HasMany
    {
        return $this->hasMany(MeetingDiscussion::class, 'agenda_id');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeInProgress($query)
    {
        return $query->where('status', 'in_progress');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }
}
