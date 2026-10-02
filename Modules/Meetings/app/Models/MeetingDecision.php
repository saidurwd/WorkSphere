<?php

namespace Modules\Meetings\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MeetingDecision extends Model
{
    use HasFactory;

    protected $fillable = [
        'meeting_id',
        'agenda_id',
        'discussion_id',
        'decision_no',
        'decision_title',
        'decision_description',
        'decision_type',
        'decision_status',
        'decision_date',
        'approved_by',
        'effective_date',
        'remarks',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'decision_date' => 'date',
            'effective_date' => 'date',
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

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function actionItems(): HasMany
    {
        return $this->hasMany(MeetingActionItem::class, 'decision_id');
    }

    public function scopeActive($query)
    {
        return $query->where('decision_status', 'active');
    }

    public function scopeSuperseded($query)
    {
        return $query->where('decision_status', 'superseded');
    }
}
