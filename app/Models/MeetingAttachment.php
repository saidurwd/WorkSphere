<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MeetingAttachment extends Model
{
    protected $fillable = [
        'meeting_id',
        'discussion_id',
        'decision_id',
        'action_item_id',
        'file_name',
        'file_path',
        'file_type',
        'file_size',
        'uploaded_by',
        'description',
    ];

    protected function casts(): array
    {
        return [
            'file_size' => 'integer',
        ];
    }

    public function meeting(): BelongsTo
    {
        return $this->belongsTo(Meeting::class);
    }

    public function discussion(): BelongsTo
    {
        return $this->belongsTo(MeetingDiscussion::class);
    }

    public function decision(): BelongsTo
    {
        return $this->belongsTo(MeetingDecision::class);
    }

    public function actionItem(): BelongsTo
    {
        return $this->belongsTo(MeetingActionItem::class);
    }

    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
