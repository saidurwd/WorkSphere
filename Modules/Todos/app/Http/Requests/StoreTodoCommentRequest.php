<?php

namespace Modules\Todos\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Todos\Services\TodoCommentService;

/**
 * Comment on a To-Do.
 *
 * Mentions are resolved from `@handle` tokens here rather than accepted as a
 * submitted list, so a user cannot name somebody in a mention they never typed
 * — and cannot silently suppress one they did.
 */
class StoreTodoCommentRequest extends FormRequest
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
            'body' => ['required', 'string', 'max:5000'],
            'parent_id' => [
                'nullable',
                Rule::exists('comments', 'id')->whereNull('deleted_at'),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'body.required' => 'A comment needs some text.',
        ];
    }

    public function prepareForValidation(): void
    {
        if ($this->has('body')) {
            $this->merge(['body' => trim((string) $this->input('body'))]);
        }
    }

    /**
     * User ids mentioned with `@handle`, resolved against real accounts.
     *
     * @return list<int>
     */
    public function mentionedUserIds(): array
    {
        return TodoCommentService::mentionedUserIds((string) $this->input('body'));
    }
}
