<?php

namespace Modules\Todos\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Reassign a To-Do.
 *
 * A null `assignee_id` is a valid answer — returning a To-Do to the unassigned
 * inbox is a real operation — so `nullable` is required rather than incidental.
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
            'assignee_id' => ['required', 'nullable', Rule::exists('users', 'id')],
        ];
    }

    public function assigneeId(): ?int
    {
        $value = $this->validated('assignee_id');

        return $value === null ? null : (int) $value;
    }
}
