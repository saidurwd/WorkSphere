@extends('tyro-dashboard::layouts.app')

@section('title', $config['title'] . ' Details')

@section('breadcrumb')
<a href="{{ route($dashboardRoute::name('index')) }}">Dashboard</a>
<span class="breadcrumb-separator">/</span>
<a href="{{ route($dashboardRoute::name('resources.index'), $resource) }}">{{ $config['title'] }}</a>
<span class="breadcrumb-separator">/</span>
<span>Details</span>
@endsection

@push('styles')
<link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/easymde/dist/easymde.min.css">
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
    });
</script>
@endpush

@section('content')
<div class="page-header">
    <div class="page-header-row">
        <div style="display: flex; align-items: center; gap: 1rem;">
            <a href="{{ route($dashboardRoute::name('resources.index'), $resource) }}" class="btn btn-ghost" title="Back to {{ $config['title'] }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 20px; height: 20px;">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
            </a>
            <h1 class="page-title">{{ Str::singular($config['title']) }} Details</h1>
        </div>
        <div>
            @if(!($isReadonly ?? false))
            <a href="{{ route($dashboardRoute::name('resources.edit'), [$resource, $item->id]) }}" class="btn btn-primary">Edit</a>
            <form action="{{ route($dashboardRoute::name('resources.destroy'), [$resource, $item->id]) }}" method="POST" style="display: inline;" id="delete-resource-form">
                @csrf
                @method('DELETE')
                <button type="button" class="btn btn-danger" onclick="if (confirm('Are you sure you want to delete this item?')) { document.getElementById('delete-resource-form').submit(); }">Delete</button>
            </form>
            @endif
        </div>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <div class="details-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 1.5rem;">
            @foreach($config['fields'] as $key => $field)
                @if(!($field['hide_in_single_view'] ?? false))
                <div class="detail-item">
                    <div class="detail-label" style="font-weight: 500; color: var(--text-secondary); margin-bottom: 0.25rem;">{{ $field['label'] }}</div>
                    <div class="detail-value" style="font-size: 1rem; color: var(--text-primary);">
                        @if($field['type'] === 'file')
                            @if($item->$key)
                                <a href="{{ Storage::url($item->$key) }}" target="_blank" style="color: var(--primary); text-decoration: none;">View File</a>
                            @else
                                -
                            @endif
                        @elseif($field['type'] === 'multiselect' || ($field['type'] === 'checkbox' && isset($field['relationship'])) || ($field['type'] === 'select' && ($field['multiple'] ?? false)))
                             @if(isset($field['relationship']))
                                 {{ $item->{$field['relationship']}->pluck($field['option_label'] ?? 'name')->implode(', ') ?: '-' }}
                             @else
                                 {{ is_array($item->$key) ? implode(', ', $item->$key) : $item->$key }}
                             @endif
                        @elseif(($field['type'] === 'select' || $field['type'] === 'radio') && isset($field['options']))
                            {{ $field['options'][$item->$key] ?? $item->$key }}
                        @elseif(isset($field['relationship']))
                            {{ optional($item->{$field['relationship']})->{$field['option_label'] ?? 'name'} ?? '-' }}
                        @elseif($field['type'] === 'boolean')
                            <span class="badge {{ $item->$key ? 'badge-success' : 'badge-secondary' }}">
                                {{ $item->$key ? 'Yes' : 'No' }}
                            </span>
                        @elseif($field['type'] === 'textarea')
                            <div style="white-space: pre-wrap;">{{ $item->$key }}</div>
                        @elseif($field['type'] === 'richtext')
                            <div class="richtext-content">{!! $sanitizedRichtext[$key] ?? e($item->$key) !!}</div>
                        
                        @elseif($field['type'] === 'markdown')
                            <div class="markdown-content" id="markdown-{{ $key }}"></div>
                            <script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
                            <script src="https://cdn.jsdelivr.net/npm/dompurify/dist/purify.min.js"></script>
                            <script>
                                document.addEventListener('DOMContentLoaded', function() {
                                    var content = @json($item->$key ?? '');
                                    document.getElementById('markdown-{{ $key }}').innerHTML = DOMPurify.sanitize(marked.parse(content));
                                });
                            </script>
                        @else
                            {{ $item->$key }}
                        @endif
                    </div>
                </div>
                @endif
            @endforeach
        </div>
    </div>
</div>
@endsection
