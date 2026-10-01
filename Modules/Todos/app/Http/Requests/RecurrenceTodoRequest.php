<?php

namespace Modules\Todos\Http\Requests;

use App\Enums\RecurrenceFrequency;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Attach or clear a recurrence rule (§5.2).
 *
 * Cross-field rules carry the semantics: a rule with neither an end date nor a
 * maximum would recur forever, which is almost never intended and is impossible
 * to undo from the UI.
 */
class RecurrenceTodoRequest extends FormRequest
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
            'frequency' => ['required', Rule::enum(RecurrenceFrequency::class)],
            'interval' => ['nullable', 'integer', 'min:1', 'max:365'],
            'by_weekday' => ['nullable', 'array', 'max:7'],
            'by_weekday.*' => ['integer', 'between:1,7'],
            'by_month_day' => ['nullable', 'integer', 'between:1,31'],
            'start_date' => ['required', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'max_occurrences' => ['nullable', 'integer', 'min:1', 'max:1000'],
            'skip_dates' => ['nullable', 'array', 'max:100'],
            'skip_dates.*' => ['date'],
            'cleared' => ['nullable', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'frequency.required' => 'A recurrence rule needs a frequency.',
        ];
    }

    /**
     * A rule bounded by neither an end date nor a maximum would generate
     * occurrences forever.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'by_weekday' => 'weekdays',
            'by_weekday.*' => 'weekday',
            'by_month_day' => 'day of month',
            'end_date' => 'end date',
            'max_occurrences' => 'maximum occurrences',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($this->boolean('cleared')) {
                return;
            }

            if ($this->filled('end_date') || $this->filled('max_occurrences')) {
                return;
            }

            $validator->errors()->add(
                'end_date',
                'A repeating To-Do needs either an end date or a maximum number of occurrences.',
            );
        });
    }

    /**
     * The rule in the JSON shape §5.2 specifies.
     *
     * @return array<string, mixed>|null
     */
    public function recurrenceRule(): ?array
    {
        if ($this->boolean('cleared')) {
            return null;
        }

        return array_filter([
            'frequency' => $this->string('frequency')->toString(),
            'interval' => $this->integer('interval', 1),
            'by_weekday' => $this->input('by_weekday') ?: null,
            'by_month_day' => $this->input('by_month_day'),
            'start_date' => $this->date('start_date')?->toDateString(),
            'end_date' => $this->date('end_date')?->toDateString(),
            'max_occurrences' => $this->integer('max_occurrences'),
            'skip_dates' => $this->input('skip_dates') ?: null,
        ], fn (mixed $value): bool => $value !== null);
    }
}
