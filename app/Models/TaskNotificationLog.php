<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaskNotificationLog extends Model
{
    protected $fillable = [
        'task_id',
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

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
