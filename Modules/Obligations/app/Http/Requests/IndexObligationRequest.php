<?php

namespace Modules\Obligations\Http\Requests;

use App\Http\Requests\ApiIndexRequest;
use Illuminate\Validation\Rule;

/**
 * `GET /api/v1/obligations` query string.
 *
 * The status, priority and risk vocabularies are declared as constants because
 * they are stored on `obligations` as plain strings and no enum backs them yet.
 * Declaring them once here means the API and the web filter cannot disagree about
 * what a valid status is — the failure this project has already paid for once,
 * in `ProjectPermissionSeeder`.
 */
class IndexObligationRequest extends ApiIndexRequest
{
    /**
     * @var list<string>
     */
    public const STATUSES = [
        'active', 'upcoming', 'action_required', 'renewal_in_progress', 'pending_approval',
        'purchase_in_progress', 'renewed', 'expired', 'cancelled', 'not_required', 'archived',
    ];

    /**
     * @var list<string>
     */
    public const PRIORITIES = ['low', 'medium', 'high', 'critical'];

    /**
     * @return list<string>
     */
    protected function sortableColumns(): array
    {
        return ['id', 'title', 'status', 'priority', 'expiry_date', 'created_at', 'updated_at'];
    }

    /**
     * @return array<string, mixed>
     */
    protected function filterRules(): array
    {
        return [
            'status' => ['sometimes', Rule::in(self::STATUSES)],
            'priority' => ['sometimes', Rule::in(self::PRIORITIES)],
            'risk_level' => ['sometimes', Rule::in(self::PRIORITIES)],
            'obligation_type_id' => ['sometimes', 'integer'],
            'category_id' => ['sometimes', 'integer'],
            'company_id' => ['sometimes', 'integer'],
            'department_id' => ['sometimes', 'integer'],
            'vendor_id' => ['sometimes', 'integer'],
            'owner_user_id' => ['sometimes', 'integer'],
            'date_from' => ['sometimes', 'date'],
            'date_to' => ['sometimes', 'date', 'after_or_equal:date_from'],
            'q' => ['sometimes', 'string', 'max:255'],
        ];
    }
}
