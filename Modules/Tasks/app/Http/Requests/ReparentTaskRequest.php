<?php

namespace Modules\Tasks\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Move a task under a different parent.
 *
 * The cycle check is deliberately in the request rather than the controller: it is
 * a rule about the input, and leaving it out would let a caller who reaches the
 * service by another route create a cycle.
 */
class ReparentTaskRequest extends FormRequest
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
            // Only existence is checked here. Whether the move would create a
            // cycle is `Task::wouldCreateCycle()` — the single enforcement point,
            // which sees the whole ancestry rather than just the immediate parent.
            // Duplicating the trivial self-parent case here would only create a
            // second place to get it wrong.
            'parent_id' => ['nullable', Rule::exists('tasks', 'id')],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'parent_id.exists' => 'That parent task does not exist.',
        ];
    }

    public function parentId(): ?int
    {
        $value = $this->validated('parent_id');

        return $value === null ? null : (int) $value;
    }
}
