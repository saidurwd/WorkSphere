<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\User;

class LoginLog extends Model
{
    public const LOGIN = 'login';

    public const LOGOUT = 'logout';

    public const FAILED = 'failed';

    public const LOCKED = 'locked';

    protected $fillable = [
        'user_id',
        'email',
        'event',
        'ip_address',
        'user_agent',
        'device',
        'failure_reason',
        'attempted_at',
    ];

    protected function casts(): array
    {
        return [
            'attempted_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeSecurityEvents(Builder $query): Builder
    {
        return $query->whereIn('event', [self::FAILED, self::LOCKED]);
    }

    /**
     * Badge variant for the recorded event.
     */
    public function eventVariant(): string
    {
        return match ($this->event) {
            self::LOGIN => 'success',
            self::LOGOUT => 'secondary',
            self::LOCKED => 'danger',
            default => 'warning',
        };
    }

    public function eventLabel(): string
    {
        return match ($this->event) {
            self::LOGIN => 'Signed in',
            self::LOGOUT => 'Signed out',
            self::LOCKED => 'Account locked',
            default => 'Failed attempt',
        };
    }
}
