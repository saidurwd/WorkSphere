<?php

namespace App\Http\Requests;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Shared list-query validation for every API index endpoint.
 *
 * Three things every index needs and none of the four modules' web controllers
 * share, so it lives here:
 *
 * 1. **A sort whitelist.** `sort` is a user-supplied column name reaching
 *    `orderBy`. Passed through unchecked it is an unauthenticated SQL injection
 *    surface on a public endpoint, so an unknown value is rejected with a 422
 *    rather than ignored. Rejecting rather than silently defaulting matters for
 *    an integration: a client that misspells `sort` gets told, instead of
 *    receiving a confidently-ordered list that is not the order it asked for.
 * 2. **A per-page cap.** `per_page` unbounded lets one request ask for 100 000
 *    rows, which is a denial of service dressed as a feature.
 * 3. **Enum-backed filters.** A `status` filter is validated against the enum
 *    rather than a duplicated `in:` string, so widening the vocabulary does not
 *    require remembering three places.
 */
abstract class ApiIndexRequest extends FormRequest
{
    /** The largest page a client may request. */
    public const MAX_PER_PAGE = 100;

    public function authorize(): bool
    {
        return true;
    }

    /**
     * Columns this endpoint may be sorted by.
     *
     * @return list<string>
     */
    abstract protected function sortableColumns(): array;

    /**
     * Module-specific filter rules, merged with the shared pagination rules.
     *
     * @return array<string, mixed>
     */
    protected function filterRules(): array
    {
        return [];
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'page' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:'.self::MAX_PER_PAGE],
            'sort' => ['sometimes', 'string', Rule::in($this->sortableColumns())],
            'order' => ['sometimes', Rule::in(['asc', 'desc'])],
            ...$this->filterRules(),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'sort.in' => 'That column cannot be sorted on. Sortable columns: '.implode(', ', $this->sortableColumns()).'.',
            'per_page.max' => 'A page may not exceed '.self::MAX_PER_PAGE.' records.',
        ];
    }

    public function perPage(): int
    {
        return (int) $this->integer('per_page', 25);
    }

    public function sortColumn(): ?string
    {
        $sort = $this->string('sort')->trim()->toString();

        return $sort === '' ? null : $sort;
    }

    public function sortDirection(): string
    {
        return $this->string('order')->toString() === 'asc' ? 'asc' : 'desc';
    }

    /**
     * Apply the whitelist ordering. A secondary key is always appended so the
     * page boundaries are deterministic: ordering by a non-unique column alone
     * lets row N appear on both page 1 and page 2, and the client sees a record
     * twice and misses another entirely.
     *
     * @param  Builder<Model>  $query
     */
    public function applySorting($query, string $fallback = 'id'): void
    {
        $column = $this->sortColumn() ?? $fallback;

        if (! in_array($column, $this->sortableColumns(), true)) {
            // Defensive: `rules()` should already have rejected this, so reaching
            // here means a route bypassed validation. Fail loudly rather than
            // ordering by something unexpected.
            throw ValidationException::withMessages([
                'sort' => 'That column cannot be sorted on.',
            ]);
        }

        $query->orderBy($column, $this->sortDirection())->orderBy('id');
    }
}
