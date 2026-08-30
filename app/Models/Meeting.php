<?php

namespace App\Models;

use HasinHayder\TyroDashboard\Concerns\HasCrud;
use Illuminate\Database\Eloquent\Attributes\SoftDeletes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[SoftDeletes]
class Meeting extends Model
{
    use HasCrud;

    protected $fillable = [
        'meeting_no',
        'title',
        'meeting_type_id',
        'organizer_id',
        'chairperson_id',
        'department_id',
        'location',
        'meeting_date',
        'start_time',
        'end_time',
        'timezone',
        'status',
        'priority',
        'description',
        'agenda',
        'minutes_status',
        'minutes_prepared_by',
        'minutes_prepared_at',
        'approved_by',
        'approved_at',
        'published_at',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'meeting_date' => 'date',
            'start_time' => 'datetime:H:i',
            'end_time' => 'datetime:H:i',
            'minutes_prepared_at' => 'datetime',
            'approved_at' => 'datetime',
            'published_at' => 'datetime',
        ];
    }

    public function type(): BelongsTo
    {
        return $this->belongsTo(MeetingType::class, 'meeting_type_id');
    }

    public function organizer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'organizer_id');
    }

    public function chairperson(): BelongsTo
    {
        return $this->belongsTo(User::class, 'chairperson_id');
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function participants(): HasMany
    {
        return $this->hasMany(MeetingParticipant::class);
    }

    public function agendas(): HasMany
    {
        return $this->hasMany(MeetingAgenda::class);
    }

    public function discussions(): HasMany
    {
        return $this->hasMany(MeetingDiscussion::class);
    }

    public function decisions(): HasMany
    {
        return $this->hasMany(MeetingDecision::class);
    }

    public function actionItems(): HasMany
    {
        return $this->hasMany(MeetingActionItem::class);
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(MeetingAttachment::class);
    }

    public function versions(): HasMany
    {
        return $this->hasMany(MeetingVersion::class);
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(MeetingTag::class, 'meeting_tag_map', 'meeting_id', 'tag_id');
    }

    public function recurrence(): HasMany
    {
        return $this->hasMany(MeetingRecurrence::class);
    }

    public function minutesApprovals(): HasMany
    {
        return $this->hasMany(MeetingMinutesApproval::class);
    }

    public function scopeScheduled($query)
    {
        return $query->where('status', 'scheduled');
    }

    public function scopeInProgress($query)
    {
        return $query->where('status', 'in_progress');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeCancelled($query)
    {
        return $query->where('status', 'cancelled');
    }

    public function scopePostponed($query)
    {
        return $query->where('status', 'postponed');
    }
}
