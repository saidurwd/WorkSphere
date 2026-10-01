<?php

namespace Modules\Tasks\Http\Requests;

use App\Enums\Priority;
use App\Enums\WorkItemStatus;
use App\Http\Requests\ApiIndexRequest;
use Illuminate\Validation\Rule;

/**
 * `GET /api/v1/tasks` query string.
 *
 * `status` is validated against `WorkItemStatus`, which is a superset of the
 * values `tasks.status` actually holds (`pending|in_progress|on_hold|completed|
 * cancelled`). A filter for a status no Task carries simply returns nothing,
 * which is honest; silently accepting it and returning every Task would not be.
 */
class IndexTaskRequest extends ApiIndexRequest
{
    /**
     * @return list<string>
     */
    protected function sortableColumns(): array
    {
        return ['id', 'title', 'status', 'priority', 'due_date', 'created_at', 'updated_at'];
    }

    /**
     * @return array<string, mixed>
     */
    protected function filterRules(): array
    {
        return [
            'status' => ['sometimes', Rule::enum(WorkItemStatus::class)],
            'priority' => ['sometimes', Rule::enum(Priority::class)],
            'project_id' => ['sometimes', 'integer'],
            'responsible_user_id' => ['sometimes', 'integer'],
            'user_id' => ['sometimes', 'integer'],
            'parent_id' => ['sometimes', 'integer'],
            'due_before' => ['sometimes', 'date'],
            'due_after' => ['sometimes', 'date'],
            'tag' => ['sometimes', 'string', 'max:100'],
            'q' => ['sometimes', 'string', 'max:255'],
            'overdue' => ['sometimes', 'boolean'],
            'top_level' => ['sometimes', 'boolean'],
        ];
    }
}
