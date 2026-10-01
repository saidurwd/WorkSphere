<?php

namespace Modules\Meetings\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MeetingTemplate extends Model
{
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

    /**
     * The foreign key is explicit: `hasMany(MeetingTemplateAgenda::class)` infers
     * `meeting_template_agenda_id`, but the column is `template_id`.
     */
    public function agendaItems(): HasMany
    {
        return $this->hasMany(MeetingTemplateAgenda::class, 'template_id');
    }
}
