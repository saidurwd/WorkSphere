@extends('tyro-dashboard::layouts.app')

@section('title', 'Edit ' . Str::singular($config['title']))

@section('breadcrumb')
<a href="{{ route($dashboardRoute::name('index')) }}">Dashboard</a>
<span class="breadcrumb-separator">/</span>
<span>Edit</span>
@endsection

@push('styles')
<link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/easymde/dist/easymde.min.css">
<style>
    .resource-form-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 1.25rem 1.5rem;
        align-items: start;
    }
    .resource-form-grid .form-group {
        margin-bottom: 0;
        min-width: 0;
    }
    .resource-form-grid .form-group.col-full {
        grid-column: 1 / -1;
    }
    .resource-form-grid .form-label {
        display: block;
    }
    @media (max-width: 640px) {
        .resource-form-grid {
            grid-template-columns: 1fr;
        }
    }
</style>
@endpush

@push('scripts')
<script src="https://cdn.quilljs.com/1.3.6/quill.js"></script>
<script src="https://cdn.jsdelivr.net/npm/easymde/dist/easymde.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        @foreach($config['fields'] as $key => $field)
        @if (($field['type'] ?? '') === 'richtext')
            (function () {
                var key = '{{ $key }}';
                if (document.getElementById('editor-' + key)) {
                    var quill = new Quill('#editor-' + key, {
                        theme: 'snow'
                    });
                    var textarea = document.getElementById(key);

                    if (textarea.value) {
                        quill.root.innerHTML = textarea.value;
                    }

                    quill.on('text-change', function () {
                        textarea.value = quill.root.innerHTML;
                    });
                }
            })();
        @endif
        @endforeach

        @foreach($config['fields'] as $key => $field)
        @if (($field['type'] ?? '') === 'markdown')
            (function () {
                var key = '{{ $key }}';
                var textarea = document.getElementById(key);

                if (textarea) {
                    new EasyMDE({
                        element: textarea,
                        spellChecker: false,
                        status: false,
                        toolbar: [
                            "bold",
                            "italic",
                            "heading",
                            "|",
                            "quote",
                            "unordered-list",
                            "ordered-list",
                            "|",
                            "link",
                            "image",
                            "|",
                            "preview",
                        ]
                    });
                }
            })();
        @endif
        @endforeach

        var categorySelect = document.getElementById('category_id');
        var subCategorySelect = document.getElementById('sub_category_id');

        function filterSubCategories() {
            if (!categorySelect || !subCategorySelect) return;
            var categoryId = categorySelect.value;
            var subOptions = subCategorySelect.querySelectorAll('option[data-category-id]');
            if (categoryId) {
                subOptions.forEach(function (opt) { opt.style.display = 'none'; });
                subOptions.forEach(function (opt) {
                    if (opt.getAttribute('data-category-id') === categoryId) {
                        opt.style.display = '';
                    }
                });
                if (!subCategorySelect.querySelector('option[data-category-id="' + categoryId + '"][value="' + subCategorySelect.value + '"]')) {
                    subCategorySelect.value = '';
                }
                subCategorySelect.disabled = false;
            } else {
                subOptions.forEach(function (opt) { opt.style.display = ''; });
                subCategorySelect.value = '';
                subCategorySelect.disabled = false;
            }
        }

        if (categorySelect) {
            categorySelect.addEventListener('change', filterSubCategories);
            filterSubCategories();
        }

        @if($resource === 'estate_staff')
        (function () {
            var estateSelect = document.getElementById('estate_id');
            var divisionSelect = document.getElementById('division_id');

            if (!estateSelect || !divisionSelect) {
                return;
            }

            var divisionsUrl = '{{ $estateStaffDivisionsUrl ?? '' }}';
            var selectedEstateId = '{{ $estateStaffSelectedEstateId ?? old('estate_id') ?? '' }}';
            var selectedDivisionId = '{{ $estateStaffSelectedDivisionId ?? old('division_id') ?? '' }}';

            if (!divisionsUrl) {
                console.error('[EstateStaff] divisionsUrl is empty. View composer may not have run.');
                return;
            }

            function logDebug(message) {
                console.log('[EstateStaff] ' + message);
            }

            function populateDivisions(estateId, preserveSelected) {
                divisionSelect.innerHTML = '<option value="">Select Division</option>';

                if (!estateId) {
                    logDebug('No estate selected, clearing divisions');
                    return;
                }

                logDebug('Fetching divisions for estate: ' + estateId + ' from ' + (divisionsUrl + '?estate_id=' + encodeURIComponent(estateId)));

                fetch(divisionsUrl + '?estate_id=' + encodeURIComponent(estateId))
                    .then(function (response) {
                        logDebug('Response status: ' + response.status);
                        if (!response.ok) {
                            throw new Error('HTTP error! status: ' + response.status);
                        }
                        return response.json();
                    })
                    .then(function (divisions) {
                        logDebug('Received divisions: ' + JSON.stringify(divisions));
                        divisions.forEach(function (division) {
                            var option = document.createElement('option');
                            option.value = division.id;
                            option.textContent = division.division_name_eng;
                            if (preserveSelected && division.id == selectedDivisionId) {
                                option.selected = true;
                            }
                            divisionSelect.appendChild(option);
                        });
                    })
                    .catch(function (error) {
                        logDebug('Error fetching divisions: ' + error.message);
                        divisionSelect.innerHTML = '<option value="">Select Division</option>';
                    });
            }

            estateSelect.addEventListener('change', function () {
                populateDivisions(estateSelect.value, false);
            });

            populateDivisions(estateSelect.value, !!selectedEstateId);
        })();
        @endif
    });
</script>
@endpush

@section('content')
<div class="page-header">
    <div style="display: flex; align-items: center; gap: 1rem;">
        <a href="{{ route($dashboardRoute::name('resources.index'), $resource) }}" class="btn btn-ghost" title="Back to {{ $config['title'] }}">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 20px; height: 20px;">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
            </svg>
        </a>
        <h1 class="page-title">Edit {{ Str::singular($config['title']) }}</h1>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <form action="{{ route($dashboardRoute::name('resources.update'), [$resource, $item->id]) }}" method="POST" enctype="multipart/form-data">
            @csrf
            @method('PUT')

            <div class="resource-form-grid">
            @foreach($config['fields'] as $key => $field)
            @if(($field['hide_in_form'] ?? false) || ($field['hide_in_edit'] ?? false))
            @continue
            @endif

            @php
                if ($resource === 'maintenance_history' && $key === 'maintenance_request_id' && isset($options[$key])) {
                    $currentId = old($key, $item->$key);
                    $options[$key] = $options[$key]->filter(function ($request) use ($currentId) {
                        return in_array($request->status, ['open', 'in_progress'], true)
                            || $request->id == $currentId;
                    });
                }
            @endphp

            @if($field['type'] === 'hidden')
            <input type="hidden" name="{{ $key }}" value="{{ old($key, $item->$key) }}">
            @continue
            @endif

            @php
                $fullWidthTypes = ['textarea', 'richtext', 'markdown', 'file'];
                $isFullWidth = in_array($field['type'] ?? 'text', $fullWidthTypes, true);
            @endphp

            <div class="form-group @if($isFullWidth) col-full @endif">
                <label for="{{ $key }}" class="form-label">{{ $field['label'] }}</label>

                @php
                    if ($key === 'sub_category_id' && ! isset($options[$key])) {
                        $options[$key] = \App\Models\AssetSubCategory::all();
                    }
                @endphp

                @if($field['type'] === 'textarea')
                <textarea name="{{ $key }}" id="{{ $key }}" class="form-input @error($key) is-invalid @enderror" rows="5" placeholder="{{ $field['placeholder'] ?? '' }}" {{ ($field['readonly'] ?? false) ? 'readonly' : '' }} @if(isset($field['attributes'])) @foreach($field['attributes'] as $attr => $value) {{ $attr }}="{{ $value }}" @endforeach @endif>{{ old($key, $item->$key) }}</textarea>

                @elseif($field['type'] === 'richtext')
                <div class="richtext-wrapper">
                    <div id="editor-{{ $key }}" style="height: 200px; background: #fff;"></div>
                    <textarea name="{{ $key }}" id="{{ $key }}" style="display:none">{{ old($key, $item->$key) }}</textarea>
                </div>

                @elseif($field['type'] === 'markdown')
                <textarea name="{{ $key }}" id="{{ $key }}" class="@error($key) is-invalid @enderror" placeholder="{{ $field['placeholder'] ?? '' }}">{{ old($key, $item->$key) }}</textarea>

                @elseif($field['type'] === 'select')
                @if($field['multiple'] ?? false)
                <select name="{{ $key }}[]" id="{{ $key }}" class="form-select @error($key) is-invalid @enderror" multiple>
                    @if(isset($options[$key]))
                    @foreach($options[$key] as $option)
                    <option value="{{ $option->id }}" {{ in_array($option->id, old($key, $selectedValues[$key] ?? [])) ? 'selected' : '' }}>
                        {{ $option->{$field['option_label'] ?? 'name'} }}
                    </option>
                    @endforeach
                    @elseif(isset($field['options']))
                    @foreach($field['options'] as $value => $label)
                    @php
                    $optionValue = is_int($value) ? $label : $value;
                    $optionLabel = $label;
                    @endphp
                    <option value="{{ $optionValue }}" {{ in_array($optionValue, old($key, $selectedValues[$key] ?? [])) ? 'selected' : '' }}>
                        {{ $optionLabel }}
                    </option>
                    @endforeach
                    @endif
                </select>
                @else
                <select name="{{ $key }}" id="{{ $key }}" class="form-select @error($key) is-invalid @enderror">
                    <option value="">Select {{ $field['label'] }}</option>
                @if(isset($options[$key]))
                @foreach($options[$key] as $option)
                @php
                $extraAttrs = '';
                if ($key === 'sub_category_id') {
                    $extraAttrs = ' data-category-id="' . ($option->category_id ?? '') . '"';
                }
                @endphp
                <option value="{{ $option->id }}" {{ old($key, $item->$key) == $option->id ? 'selected' : '' }}{!! $extraAttrs !!}>
                    {{ $option->{$field['option_label'] ?? 'name'} }}
                </option>
                @endforeach
                    @elseif(isset($field['options']))
                    @foreach($field['options'] as $value => $label)
                    @php
                    $optionValue = is_int($value) ? $label : $value;
                    $optionLabel = $label;
                    @endphp
                    <option value="{{ $optionValue }}" {{ old($key, $item->$key) == $optionValue ? 'selected' : '' }}>
                        {{ $optionLabel }}
                    </option>
                    @endforeach
                    @endif
                </select>
                @endif

                @elseif($field['type'] === 'multiselect')
                <select name="{{ $key }}[]" id="{{ $key }}" class="form-select @error($key) is-invalid @enderror" multiple>
                    @if(isset($options[$key]))
                    @foreach($options[$key] as $option)
                    <option value="{{ $option->id }}" {{ in_array($option->id, old($key, $selectedValues[$key] ?? ($item->$key ?? []))) ? 'selected' : '' }}>
                        {{ $option->{$field['option_label'] ?? 'name'} }}
                    </option>
                    @endforeach
                    @elseif(isset($field['options']))
                    @foreach($field['options'] as $value => $label)
                    @php
                    $optionValue = is_int($value) ? $label : $value;
                    $optionLabel = $label;
                    @endphp
                    <option value="{{ $optionValue }}" {{ in_array($optionValue, old($key, $item->$key ?? [])) ? 'selected' : '' }}>
                        {{ $optionLabel }}
                    </option>
                    @endforeach
                    @endif
                </select>

                @elseif($field['type'] === 'radio')
                <div class="radio-group">
                    @if(isset($options[$key]))
                    @foreach($options[$key] as $option)
                    <div class="form-check">
                        <input type="radio" name="{{ $key }}" id="{{ $key }}_{{ $option->id }}" value="{{ $option->id }}" {{ old($key, $item->$key) == $option->id ? 'checked' : '' }}>
                        <label for="{{ $key }}_{{ $option->id }}">{{ $option->{$field['option_label'] ?? 'name'} }}</label>
                    </div>
                    @endforeach
                    @elseif(isset($field['options']))
                    @foreach($field['options'] as $value => $label)
                    @php
                    $optionValue = is_int($value) ? $label : $value;
                    $optionLabel = $label;
                    @endphp
                    <div class="form-check">
                        <input type="radio" name="{{ $key }}" id="{{ $key }}_{{ $optionValue }}" value="{{ $optionValue }}" {{ old($key, $item->$key) == $optionValue ? 'checked' : '' }}>
                        <label for="{{ $key }}_{{ $optionValue }}">{{ $optionLabel }}</label>
                    </div>
                    @endforeach
                    @endif
                </div>

                @elseif($field['type'] === 'checkbox' && (isset($options[$key]) || isset($field['options'])))
                <div class="checkbox-group">
                    @if(isset($options[$key]))
                    @foreach($options[$key] as $option)
                    <div class="form-check">
                        <input type="checkbox" name="{{ $key }}[]" id="{{ $key }}_{{ $option->id }}" value="{{ $option->id }}" {{ in_array($option->id, old($key, $selectedValues[$key] ?? ($item->$key ?? []))) ? 'checked' : '' }}>
                        <label for="{{ $key }}_{{ $option->id }}">{{ $option->{$field['option_label'] ?? 'name'} }}</label>
                    </div>
                    @endforeach
                    @elseif(isset($field['options']))
                    @foreach($field['options'] as $value => $label)
                    @php
                    $optionValue = is_int($value) ? $label : $value;
                    $optionLabel = $label;
                    @endphp
                    <div class="form-check">
                        <input type="checkbox" name="{{ $key }}[]" id="{{ $key }}_{{ $optionValue }}" value="{{ $optionValue }}" {{ in_array($optionValue, old($key, $item->$key ?? [])) ? 'checked' : '' }}>
                        <label for="{{ $key }}_{{ $optionValue }}">{{ $optionLabel }}</label>
                    </div>
                    @endforeach
                    @endif
                </div>

                @elseif($field['type'] === 'file')
                @php
                    $displayImage = $field['display_image'] ?? false;
                    $displayImagePosition = $field['display_image_position'] ?? 'top';
                    $isImage = !empty($item->$key) && $displayImage && preg_match('/\.(jpg|jpeg|png|gif|webp|svg)$/i', $item->$key);
                @endphp
                
                @if($isImage && $displayImagePosition === 'top')
                <div style="margin-bottom: 0.5rem;">
                    <img src="{{ Storage::url($item->$key) }}" alt="Current image" style="width: 200px; height: auto; border: 1px solid var(--border); border-radius: 4px;">
                </div>
                @endif
                
                <input type="file" name="{{ $key }}" id="{{ $key }}" class="form-input @error($key) is-invalid @enderror" {{ ($field['readonly'] ?? false) ? 'readonly' : '' }} @if(isset($field['attributes'])) @foreach($field['attributes'] as $attr => $value) {{ $attr }}="{{ $value }}" @endforeach @endif>
                @if(!empty($item->$key))
                <div style="margin-top: 0.5rem;">
                    <small>Current file: <a href="{{ Storage::url($item->$key) }}" target="_blank">{{ basename($item->$key) }}</a></small>
                </div>
                @endif
                
                @if($isImage && $displayImagePosition === 'bottom')
                <div style="margin-top: 0.5rem;">
                    <img src="{{ Storage::url($item->$key) }}" alt="Current image" style="width: 200px; height: auto; border: 1px solid var(--border); border-radius: 4px;">
                </div>
                @endif

                @elseif($field['type'] === 'boolean')
                <div class="form-check">
                    <input type="checkbox" name="{{ $key }}" id="{{ $key }}" value="1" {{ old($key, $item->$key) ? 'checked' : '' }}>
                    <label for="{{ $key }}">Yes</label>
                </div>

                @else
                <input type="{{ $field['type'] }}" name="{{ $key }}" id="{{ $key }}" class="form-input @error($key) is-invalid @enderror" value="{{ old($key, $item->$key) }}" placeholder="{{ $field['placeholder'] ?? '' }}" {{ ($field['readonly'] ?? false) ? 'readonly' : '' }} @if(isset($field['attributes'])) @foreach($field['attributes'] as $attr => $value) {{ $attr }}="{{ $value }}" @endforeach @endif>
                @endif

                @if(isset($field['help_text']))
                <div class="form-help-text" style="color: var(--text-secondary); font-size: 0.875rem; margin-top: 0.25rem;">{{ $field['help_text'] }}</div>
                @endif

                @error($key)
                @if(config('tyro-dashboard.resource_ui.show_field_errors', true))
                <div class="form-error" style="color: var(--danger); font-size: 0.875rem; margin-top: 0.25rem;">{{ $message }}</div>
                @endif
                @enderror
            </div>
            @endforeach
            </div>

            <div class="form-actions" style="margin-top: 1.5rem;">
                <a href="{{ route($dashboardRoute::name('resources.index'), $resource) }}" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary">Update {{ Str::singular($config['title']) }}</button>
            </div>
        </form>
    </div>
</div>
@endsection
