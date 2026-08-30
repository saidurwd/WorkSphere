<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MeetingTagMap extends Model
{
    protected $fillable = [
        'meeting_id',
        'tag_id',
    ];

    public function meeting(): BelongsTo
    {
        return $this->belongsTo(Meeting::class);
    }

    public function tag(): BelongsTo
    {
        return $this->belongsTo(MeetingTag::class);
    }
}
