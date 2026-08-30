<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MeetingNotificationLog extends Model
{
    protected $fillable = [
        'meeting_id',
        'action_item_id',
        'user_id',
        'channel',
        'notification_type',
        'scheduled_at',
        'sent_at',
        'status',
        'subject',
        'message',
        'retry_count',
        'error_message',
        'provider_message_id',
    ];

    protected function casts(): array
    {
        return [
            'scheduled_at' => 'datetime',
            'sent_at' => 'datetime',
            'retry_count' => 'integer',
        ];
    }

    public function meeting(): BelongsTo
    {
        return $this->belongsTo(Meeting::class);
    }

    public function actionItem(): BelongsTo
    {
        return $this->belongsTo(MeetingActionItem::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
