<?php

namespace Modules\Todos\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;

/**
 * To-Do checklist item — DATABASE-ARCHITECTURE.md §4.3.
 *
 * Progress is computed from `is_completed` at read time and stored nowhere. A
 * cached percentage on the parent row goes stale the instant an item is toggled
 * and nothing would update it, so `progress()` derives it every time.
 */
class TodoChecklistItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'todo_id',
        'title',
        'is_completed',
        'completed_at',
        'completed_by',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_completed' => 'boolean',
            'completed_at' => 'datetime',
            'sort_order' => 'integer',
        ];
    }

    public function todo(): BelongsTo
    {
        return $this->belongsTo(Todo::class);
    }

    public function completedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'completed_by');
    }

    /**
     * Completion across a set of items, as [completed, total, percentage].
     *
     * @param  Collection<int, self>  $items
     * @return array{int, int, int}
     */
    public static function progressFor(Collection $items): array
    {
        $total = $items->count();

        if ($total === 0) {
            return [0, 0, 0];
        }

        $completed = $items->where('is_completed', true)->count();

        return [$completed, $total, (int) round($completed / $total * 100)];
    }
}
