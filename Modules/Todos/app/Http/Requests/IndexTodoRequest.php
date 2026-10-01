<?php

namespace Modules\Todos\Http\Requests;

use App\Enums\Priority;
use App\Enums\Visibility;
use App\Enums\WorkItemStatus;
use App\Http\Requests\ApiIndexRequest;
use Illuminate\Validation\Rule;

/**
 * `GET /api/v1/todos` query string — §9.1.
 *
 * The filter set is the web index's filter set, validated against the same
 * enums. `tag` is a slug matched through the shared `taggables` pivot, and
 * `assignee_id` accepts the literal `none` for unassigned — the same two
 * conventions the web filter uses, so a client that learned them in the UI does
 * not have to learn them again.
 */
class IndexTodoRequest extends ApiIndexRequest
{
    /**
     * @return list<string>
     */
    protected function sortableColumns(): array
    {
        return ['id', 'title', 'status', 'priority', 'due_date', 'start_date', 'created_at', 'updated_at'];
    }

    /**
     * @return array<string, mixed>
     */
    protected function filterRules(): array
    {
        return [
            'status' => ['sometimes', Rule::enum(WorkItemStatus::class)],
            'priority' => ['sometimes', Rule::enum(Priority::class)],
            'visibility' => ['sometimes', Rule::enum(Visibility::class)],
            'assignee_id' => ['sometimes', 'regex:/^\d+$|^none$/'],
            'department_id' => ['sometimes', 'integer'],
            'creator_id' => ['sometimes', 'integer'],
            'due_before' => ['sometimes', 'date'],
            'due_after' => ['sometimes', 'date'],
            'tag' => ['sometimes', 'string', 'max:100'],
            'q' => ['sometimes', 'string', 'max:255'],
            'overdue' => ['sometimes', 'boolean'],
            'recurring' => ['sometimes', 'boolean'],
        ];
    }
}
