@extends('layouts.app')

@section('title', 'Upload Attachment')

@section('breadcrumb', [
    ['label' => 'Dashboard', 'url' => {{ route('dashboard.index') }}],
    ['label' => 'Meetings', 'url' => {{ route('meetings.index') }}],
    ['label' => '{{ $meeting->title }}', 'url' => {{ route('meetings.show', $meeting) }}],
    ['label' => 'Upload Attachment'],
])

@section('content')
<div class="page-header">
    <div class="page-header-row">
        <div>
            <h1 class="page-title">Upload Attachment</h1>
            <p class="page-description">Upload a file for {{ $meeting->title }}</p>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <form action="{{ route('meetings.attachments.store', $meeting) }}" method="POST" enctype="multipart/form-data">
            @csrf

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1.5rem;">
                <div>
                    <label class="form-label">File <span style="color: var(--danger);">*</span></label>
                    <input type="file" name="file" class="form-input @error('file') is-invalid @enderror" required>
                    @error('file')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    <div style="font-size: 0.85rem; color: var(--muted-foreground); margin-top: 0.5rem;">Maximum file size: 10MB</div>
                </div>

                <div>
                    <label class="form-label">Description</label>
                    <input type="text" name="description" class="form-input @error('description') is-invalid @enderror" value="{{ old('description') }}">
                    @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>

            <div style="display: flex; gap: 0.75rem; margin-top: 2rem;">
                <button type="submit" class="btn btn-primary">Upload Attachment</button>
                <a href="{{ route('meetings.show', $meeting) }}" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
