<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MeetingDiscussion extends Model
{
    protected $fillable = [
        'meeting_id',
        'agenda_id',
        'topic',
        'discussion',
        'key_points',
        'discussion_by',
        'sort_order',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
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

    public function discussionBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'discussion_by');
    }

    public function decisions(): HasMany
    {
        return $this->hasMany(MeetingDecision::class, 'discussion_id');
    }

    public function actionItems(): HasMany
    {
        return $this->hasMany(MeetingActionItem::class, 'discussion_id');
    }
}
