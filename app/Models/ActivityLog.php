<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'record_id' => 'integer',
            'old_value' => 'array',
            'new_value' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
