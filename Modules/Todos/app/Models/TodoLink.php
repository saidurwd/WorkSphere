<?php

namespace Modules\Todos\Models;

use App\Enums\LinkType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * To-Do polymorphic link — DATABASE-ARCHITECTURE.md §4.4.
 *
 * Replaces the six nullable FK columns a Task↔To-Do relationship would
 * otherwise need. `todo_link_reverse_idx` makes "which To-Dos point at me" a
 * lookup, which is what gives bidirectional navigation without a second column.
 *
 * `linkable_type` is never taken from request input directly: it is resolved
 * through TodoLinkService's allow-list of concrete classes.
 */
class TodoLink extends Model
{
    use HasFactory;

    protected $fillable = [
        'todo_id',
        'linkable_type',
        'linkable_id',
        'link_type',
    ];

    protected function casts(): array
    {
        return [
            'link_type' => LinkType::class,
        ];
    }

    public function todo(): BelongsTo
    {
        return $this->belongsTo(Todo::class);
    }

    public function linkable(): MorphTo
    {
        return $this->morphTo();
    }
}
