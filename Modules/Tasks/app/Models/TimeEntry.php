<?php

namespace Modules\Tasks\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One logged block of work against a task — GAP-025.
 *
 * `logged_on` is a business date rather than a timestamp: the question this answers
 * is "what did I spend yesterday", which is a calendar-day question in the
 * business timezone.
 */
class TimeEntry extends Model
{
    use HasFactory;

    protected $fillable = [
        'task_id',
        'user_id',
        'minutes',
        'logged_on',
        'note',
    ];

    protected function casts(): array
    {
        return [
            'minutes' => 'integer',
            'logged_on' => 'date',
        ];
    }

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
