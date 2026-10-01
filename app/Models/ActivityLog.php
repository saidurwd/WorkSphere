<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ActivityLog extends Model
{
    protected $fillable = [
        'user_id',
        'module_name',
        'record_id',
        'action',
        'old_value',
        'new_value',
        'ip_address',
        // Polymorphic subject columns, added by Phase 3 §4.12. `module_name` +
        // `record_id` are retained for backward compatibility during the
        // migration window and are dropped in Phase 15.
        'subject_type',
        'subject_id',
        'user_agent',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'record_id' => 'integer',
            'subject_id' => 'integer',
            'old_value' => 'array',
            'new_value' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }
}
