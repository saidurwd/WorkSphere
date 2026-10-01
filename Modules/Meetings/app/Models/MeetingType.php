<?php

namespace Modules\Meetings\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MeetingType extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'description',
        'color',
        'is_active',
        'sort_order',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function meetings(): HasMany
    {
        return $this->hasMany(Meeting::class);
    }

    public function templates(): HasMany
    {
        // The column is `meeting_type_id`, which is not what `hasMany` would infer
        // from `MeetingTemplate` alone in every Laravel version.
        return $this->hasMany(MeetingTemplate::class, 'meeting_type_id');
    }
}
