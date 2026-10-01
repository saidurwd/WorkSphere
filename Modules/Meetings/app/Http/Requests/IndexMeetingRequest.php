<?php

namespace Modules\Meetings\Http\Requests;

use App\Http\Requests\ApiIndexRequest;
use Illuminate\Validation\Rule;

/**
 * `GET /api/v1/meetings` query string.
 *
 * `meetings.status` has its own vocabulary (`scheduled|in_progress|completed|
 * cancelled|postponed`) that predates `WorkItemStatus` and is preserved verbatim,
 * so the allowed set is declared here rather than derived from the work-item enum.
 */
class IndexMeetingRequest extends ApiIndexRequest
{
    /**
     * @var list<string>
     */
    public const STATUSES = ['scheduled', 'in_progress', 'completed', 'cancelled', 'postponed'];

    /**
     * @return list<string>
     */
    protected function sortableColumns(): array
    {
        return ['id', 'title', 'status', 'meeting_date', 'created_at', 'updated_at'];
    }

    /**
     * @return array<string, mixed>
     */
    protected function filterRules(): array
    {
        return [
            'status' => ['sometimes', Rule::in(self::STATUSES)],
            'meeting_type_id' => ['sometimes', 'integer'],
            'department_id' => ['sometimes', 'integer'],
            'organizer_id' => ['sometimes', 'integer'],
            'date_from' => ['sometimes', 'date'],
            'date_to' => ['sometimes', 'date', 'after_or_equal:date_from'],
            'q' => ['sometimes', 'string', 'max:255'],
        ];
    }
}
