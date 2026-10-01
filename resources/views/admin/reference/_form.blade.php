@php
    $value = static fn (string $field): mixed => old($field, $row->{$field});
    $label = static fn (string $field): string => \Illuminate\Support\Str::headline(str_replace('_id', '', $field));
    $lookup = [
        'department_id' => ['department_name'],
        'location_id' => ['location_name'],
        'head_of_department_id' => ['employee_name'],
    ];
@endphp

@foreach ($descriptor['fields'] as $field)
    <div class="mb-3">
        @php $current = $value($field); @endphp

        @if ($field === 'status')
            <x-form.select name="status" label="Status" :value="$current">
                <option value="active" @selected($current === 'active')>Active</option>
                <option value="inactive" @selected($current === 'inactive')>Inactive</option>
            </x-form.select>

        @elseif (isset($lookup[$field]))
            @php $nameColumn = $lookup[$field][0]; @endphp
            <x-form.select name="{{ $field }}" :label="$label($field)" :value="$current">
                <option value="">None</option>
                @foreach ($options[$field] as $option)
                    <option value="{{ $option->id }}" @selected((string) $current === (string) $option->id)>
                        {{ $option->{$nameColumn} }}
                    </option>
                @endforeach
            </x-form.select>

        @elseif ($field === 'joining_date')
            <x-form.input name="{{ $field }}" :label="$label($field)" type="date"
                          :value="$current ? \Illuminate\Support\Carbon::parse($current)->toDateString() : null" />

        @else
            <x-form.input name="{{ $field }}" :label="$label($field)" :value="$current" />
        @endif
    </div>
@endforeach
