<?php

namespace App\Models;

use App\Enums\NotificationChannel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Per-user, per-type, per-channel opt-out — DATABASE-ARCHITECTURE.md §4.10.
 *
 * A user with no row for a (type, channel) triple is opted IN. Storing only the
 * exceptions keeps this table small, and means a new notification type is
 * delivered by default rather than silently swallowed by a missing row.
 */
class NotificationPreference extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'notification_type',
        'channel',
        'enabled',
    ];

    protected function casts(): array
    {
        return [
            'enabled' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Record a choice. Creating the same row twice is not an error — it is a
     * user changing their mind.
     */
    public static function set(User $user, string $notificationType, NotificationChannel|string $channel, bool $enabled): self
    {
        $channelValue = $channel instanceof NotificationChannel ? $channel->value : $channel;

        return self::query()->updateOrCreate(
            [
                'user_id' => $user->id,
                'notification_type' => $notificationType,
                'channel' => $channelValue,
            ],
            ['enabled' => $enabled],
        );
    }

    public static function isEnabledFor(User $user, string $notificationType, NotificationChannel|string $channel): bool
    {
        $channelValue = $channel instanceof NotificationChannel ? $channel->value : $channel;

        $preference = self::query()
            ->where('user_id', $user->id)
            ->where('notification_type', $notificationType)
            ->where('channel', $channelValue)
            ->first();

        return $preference === null || $preference->enabled;
    }
}
