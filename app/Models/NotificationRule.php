<?php

namespace App\Models;

use HasinHayder\TyroDashboard\Concerns\HasCrud;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class NotificationRule extends Model
{
    use HasCrud;

    protected $fillable = [
        'obligation_type_id',
        'days_before_expiry',
        'notification_level',
        'recipient_type',
        'channel',
        'subject_template',
        'message_template',
        'active',
    ];

    protected function casts(): array
    {
        return [
            'active' => 'boolean',
            'days_before_expiry' => 'integer',
        ];
    }

    public function obligationType(): BelongsTo
    {
        return $this->belongsTo(ObligationType::class);
    }

    public function notificationLogs(): HasMany
    {
        return $this->hasMany(NotificationLog::class);
    }
}
