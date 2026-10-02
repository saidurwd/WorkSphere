<?php

namespace Modules\Meetings\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MeetingTagMap extends Model
{
    use HasFactory;

    /**
     * SINGULAR, matching the table the migration created.
     *
     * Without this, Laravel pluralises the class name to `meeting_tag_maps` and
     * every query against this model fails with "no such table". It went unnoticed
     * because nothing read the legacy pivot by model — the tag path has dual-written
     * onto the shared `taggables` table since Phase 7 — so the model was
     * unreachable dead code that had never once been executed.
     */
    protected $table = 'meeting_tag_map';

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
