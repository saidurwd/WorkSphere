<?php

namespace Modules\Todos\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Reassign a To-Do.
 *
 * A null `assignee_id` is a valid answer — returning a To-Do to the unassigned
 * inbox is a real operation — so the key must be PRESENT but may be null.
 *
 * `present`, not `required`: `required` rejects a null value outright, and rules
 * are evaluated in order, so `['required', 'nullable', …]` fails before `nullable`
 * is ever consulted. That made unassigning through this request impossible, which
 * is the one thing the class exists to allow.
 */
class AssignTodoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'assignee_id' => ['present', 'nullable', Rule::exists('users', 'id')],
        ];
    }

    public function assigneeId(): ?int
    {
        $value = $this->validated('assignee_id');

        return $value === null ? null : (int) $value;
    }
}
