@extends('layouts.app')

@section('title', 'New Meeting Tag')

@php
    $breadcrumbs = [
        ['label' => 'Dashboard', 'url' => route('dashboard.index')],
        ['label' => 'Meetings', 'url' => route('meetings.index')],
        ['label' => 'Tags', 'url' => route('meetings.tags.index')],
        ['label' => 'New Tag'],
    ];
@endphp

@section('content')
<x-page-header title="New Meeting Tag" subtitle="Create a new tag for meetings." />

<div class="card">
    <div class="card-body">
        <form action="{{ route('meetings.tags.store') }}" method="POST">
            @csrf

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1.5rem;">
                <div>
                    <label class="form-label" for="name">Name <span style="color: var(--danger);">*</span></label>
                    <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" required id="name">
                    @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div>
                    <label class="form-label" for="color">Color</label>
                    <div style="display: flex; align-items: center; gap: 0.75rem;">
                        <input type="color" name="color" class="form-control form-control-color @error('color') is-invalid @enderror" value="{{ old('color', '#3b82f6') }}" style="width: 48px; height: 48px; padding: 0; border: 1px solid var(--border); border-radius: var(--radius, 0.5rem); cursor: pointer; background: none;" id="color">
                        <input type="text" name="color_text" class="form-control @error('color') is-invalid @enderror" value="{{ old('color', '#3b82f6') }}" placeholder="#3b82f6" style="width: 140px; font-family: monospace;">
                    </div>
                    @error('color')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div style="display: flex; align-items: center; gap: 0.5rem; margin-top: 0.5rem;">
                    <input type="checkbox" name="is_active" id="is_active" value="1" {{ old('is_active', true) ? 'checked' : '' }} style="width: 18px; height: 18px; accent-color: var(--primary);">
                    <label for="is_active" class="form-label" style="margin-bottom: 0; cursor: pointer;">Active</label>
                </div>
            </div>

            <div style="display: flex; gap: 0.75rem; margin-top: 2rem;">
                <button type="submit" class="btn btn-primary">Create Tag</button>
                <a href="{{ route('meetings.tags.index') }}" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function() {
    const colorInput = document.querySelector('input[name="color"]');
    const colorText = document.querySelector('input[name="color_text"]');
    if (colorInput && colorText) {
        colorInput.addEventListener('input', function() {
            colorText.value = this.value;
        });
        colorText.addEventListener('input', function() {
            if (/^#[0-9A-Fa-f]{6}$/.test(this.value)) {
                colorInput.value = this.value;
            }
        });
    }
})();
</script>
@endpush
