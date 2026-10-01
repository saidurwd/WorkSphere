<?php

namespace App\Models;

use App\Enums\NotificationChannel;
use App\Enums\ReminderStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Shared reminder — DATABASE-ARCHITECTURE.md §4.8.
 *
 * Two schema guarantees do the real work here:
 *
 * - UNIQUE(subject_type, subject_id, remind_at) makes creating a duplicate
 *   reminder impossible, so the create path is idempotent by construction rather
 *   than by a check-then-insert that races.
 * - INDEX(status, remind_at) is the dispatcher's only hot query.
 *
 * Neither is enforced by this class, which is why `createOnce()` exists only as
 * a convenience — correctness comes from the unique index, not from this method.
 */
class Reminder extends Model
{
    use HasFactory;

    protected $fillable = [
        'subject_type',
        'subject_id',
        'remind_at',
        'channel',
        'status',
        'sent_at',
        'cancelled_at',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'remind_at' => 'datetime',
            'sent_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'status' => ReminderStatus::class,
            'channel' => NotificationChannel::class,
        ];
    }

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isDue(): bool
    {
        return $this->status === ReminderStatus::Pending && $this->remind_at->isPast();
    }

    public function markSent(): void
    {
        $this->forceFill([
            'status' => ReminderStatus::Sent,
            'sent_at' => now(),
        ])->save();
    }

    public function markCancelled(): void
    {
        $this->forceFill([
            'status' => ReminderStatus::Cancelled,
            'cancelled_at' => now(),
        ])->save();
    }

    /**
     * Everything the scheduler needs, in one query shape.
     *
     * @param  Builder<self>  $query
     */
    public function scopeDue(Builder $query): void
    {
        $query->where('status', ReminderStatus::Pending->value)
            ->where('remind_at', '<=', now());
    }
}
