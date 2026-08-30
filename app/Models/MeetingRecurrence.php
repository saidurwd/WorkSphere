<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MeetingRecurrence extends Model
{
    protected $fillable = [
        'meeting_id',
        'recurrence_type',
        'recurrence_interval',
        'day_of_week',
        'day_of_month',
        'start_date',
        'end_date',
        'occurrences',
        'next_occurrence',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'recurrence_interval' => 'integer',
            'day_of_month' => 'integer',
            'occurrences' => 'integer',
            'start_date' => 'date',
            'end_date' => 'date',
            'next_occurrence' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    public function meeting(): BelongsTo
    {
        return $this->belongsTo(Meeting::class);
    }
}
