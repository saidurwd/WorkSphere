<?php

namespace Modules\Tasks\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\User;
use Modules\Tasks\Models\Task;

#[Fillable(['task_id', 'user_id', 'remark', 'attachment'])]
class TaskRemark extends Model
{
    protected function casts(): array
    {
        return [];
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
