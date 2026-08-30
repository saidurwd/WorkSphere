<?php

namespace App\Models;

use HasinHayder\TyroDashboard\Concerns\HasCrud;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MeetingTemplate extends Model
{
    use HasCrud;

    protected $fillable = [
        'name',
        'meeting_type_id',
        'description',
        'default_duration',
        'default_location',
        'default_priority',
        'is_active',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'default_duration' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function meetingType(): BelongsTo
    {
        return $this->belongsTo(MeetingType::class, 'meeting_type_id');
    }

    public function agendaItems(): HasMany
    {
        return $this->hasMany(MeetingTemplateAgenda::class);
    }
}
