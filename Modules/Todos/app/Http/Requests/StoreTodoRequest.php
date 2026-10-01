<?php

namespace Modules\Todos\Http\Requests;

use App\Enums\Priority;
use App\Enums\Visibility;
use App\Enums\WorkItemStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Create a To-Do.
 *
 * Zero inline validation in this module by design: every rule here names an enum
 * case rather than repeating an `in:` list. The vocabulary lives in
 * App\Enums, so adding a status updates this file automatically — an `in:`
 * string would have to be edited by hand and would eventually drift.
 */
class StoreTodoRequest extends FormRequest
{
    public function authorize(): bool
    {
        // The controller authorises through TodoPolicy::create, which is also
        // where `todos.create_for_others` is enforced. Returning true here does
        // not skip that; it only stops the request from duplicating it.
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'status' => ['nullable', Rule::enum(WorkItemStatus::class)],
            'priority' => ['nullable', Rule::enum(Priority::class)],
            'visibility' => ['nullable', Rule::enum(Visibility::class)],
            'assignee_id' => ['nullable', Rule::exists('users', 'id')],
            'department_id' => ['nullable', Rule::exists('departments', 'id')],
            'start_date' => ['nullable', 'date'],
            'due_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'due_time' => ['nullable', 'date_format:H:i'],
            'estimated_minutes' => ['nullable', 'integer', 'min:0', 'max:100000'],
            'color' => ['nullable', 'string', 'max:20'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'due_date.after_or_equal' => 'The due date cannot be before the start date.',
            'due_time.date_format' => 'The due time must be in HH:MM format.',
        ];
    }

    /**
     * Trim the title before it reaches the model. Without this a title of
     * "   " passes `required` and produces an invisible row in the inbox.
     *
     * @return array<string, mixed>
     */
    public function prepareForValidation(): void
    {
        if ($this->has('title')) {
            $this->merge(['title' => trim((string) $this->input('title'))]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function todoAttributes(): array
    {
        return $this->safe()->except(['assignee_id']);
    }
}
