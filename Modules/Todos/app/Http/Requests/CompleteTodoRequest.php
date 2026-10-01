<?php

namespace Modules\Todos\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Complete a To-Do.
 *
 * Only the optional closing note. `status` is not accepted: completion is a
 * transition, not a field write, so TodoService decides whether it is legal.
 */
class CompleteTodoRequest extends FormRequest
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
            'note' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
