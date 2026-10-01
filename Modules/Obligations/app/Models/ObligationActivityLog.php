<?php

namespace Modules\Obligations\Models;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphOne;

class ObligationActivityLog extends Model
{
    protected $fillable = [
        'obligation_id',
        'user_id',
        'action',
        'old_value',
        'new_value',
        'remarks',
        'ip_address',
        'user_agent',
    ];

    protected function casts(): array
    {
        return [
            // GAP-032. Both columns are TEXT holding JSON, so without a cast every
            // read returns a raw string and callers do `json_decode()` themselves.
            // A cast makes the column behave like the shared `activity_logs`
            // columns, which are already cast — that symmetry is what lets the two
            // tables be reconciled.
            //
            // The cast tolerates a value that is not valid JSON by returning null
            // rather than throwing: a hand-edited row must not take a listing page
            // down.
            'old_value' => 'array',
            'new_value' => 'array',
            'created_at' => 'datetime',
        ];
    }

    /**
     * The shared platform trail this row is mirrored into — GAP-048.
     */
    public function sharedActivity(): MorphOne
    {
        return $this->morphOne(ActivityLog::class, 'subject', 'subject_type', 'record_id')
            ->where('subject_type', ActivityLog::class);
    }

    public function obligation(): BelongsTo
    {
        return $this->belongsTo(Obligation::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
