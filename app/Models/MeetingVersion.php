<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MeetingVersion extends Model
{
    protected $fillable = [
        'meeting_id',
        'version_no',
        'snapshot_data',
        'change_summary',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'snapshot_data' => 'array',
            'version_no' => 'integer',
        ];
    }

    public function meeting(): BelongsTo
    {
        return $this->belongsTo(Meeting::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
