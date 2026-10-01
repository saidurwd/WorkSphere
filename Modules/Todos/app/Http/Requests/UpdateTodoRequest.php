<?php

namespace Modules\Todos\Http\Requests;

use App\Enums\Priority;
use App\Enums\Visibility;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Edit a To-Do's fields.
 *
 * `status` is deliberately absent: a status change goes through TodoService's
 * transition graph (§3.2), not through a bulk field update. Accepting it here
 * would let a form bypass the transition rules entirely.
 */
class UpdateTodoRequest extends FormRequest
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
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'priority' => ['required', Rule::enum(Priority::class)],
            'visibility' => ['required', Rule::enum(Visibility::class)],
            'department_id' => ['nullable', Rule::exists('departments', 'id')],
            'start_date' => ['nullable', 'date'],
            'due_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'due_time' => ['nullable', 'date_format:H:i'],
            'estimated_minutes' => ['nullable', 'integer', 'min:0', 'max:100000'],
            'actual_minutes' => ['nullable', 'integer', 'min:0', 'max:100000'],
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
        ];
    }

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
        return $this->safe()->all();
    }
}
