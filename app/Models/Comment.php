<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Shared comment — DATABASE-ARCHITECTURE.md §4.5.
 *
 * One polymorphic table for every module. `Meeting` currently writes to
 * `meeting_discussions` and `Task` to `task_remarks`; those become dual-write
 * targets in Phase 7 and are dropped in Phase 15.
 *
 * `parent_id` has no foreign key on purpose: a soft-deleted parent must stay
 * renderable in its thread, and a hard constraint would either block the delete
 * or cascade the replies away.
 */
class Comment extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'commentable_type',
        'commentable_id',
        'user_id',
        'parent_id',
        'body',
        'mentions',
        'edited_at',
    ];

    protected function casts(): array
    {
        return [
            'mentions' => 'array',
            'edited_at' => 'datetime',
        ];
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function replies(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    /**
     * @param  Builder<self>  $query
     */
    public function scopeForSubject(Builder $query, string $type, int $id): void
    {
        $query->where('commentable_type', $type)->where('commentable_id', $id);
    }

    /**
     * Top-level comments only. Used by every list so a thread is not rendered
     * twice, once as a root and once as somebody else's reply.
     *
     * @param  Builder<self>  $query
     */
    public function scopeRoot(Builder $query): void
    {
        $query->whereNull('parent_id');
    }

    /**
     * @param  Builder<self>  $query
     */
    public function scopeMentioning(Builder $query, int $userId): void
    {
        $query->whereJsonContains('mentions', $userId);
    }
}
