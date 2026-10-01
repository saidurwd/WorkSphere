<?php

namespace Modules\Todos\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * To-Do watcher — DATABASE-ARCHITECTURE.md §4.2.
 *
 * A watcher is a party to the To-Do for visibility and notification purposes
 * only. They do not gain update or delete rights; that stays with the assignee,
 * the creator and the permission holders.
 */
class TodoWatcher extends Model
{
    use HasFactory;

    protected $fillable = [
        'todo_id',
        'user_id',
    ];

    public function todo(): BelongsTo
    {
        return $this->belongsTo(Todo::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
