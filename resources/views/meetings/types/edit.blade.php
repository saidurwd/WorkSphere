@extends('tyro-dashboard::layouts.admin')

@section('title', 'Edit Meeting Type')

@section('breadcrumb')
<a href="{{ route(\HasinHayder\TyroDashboard\Support\DashboardRoute::name('index')) }}">Dashboard</a>
<span class="breadcrumb-separator">/</span>
<a href="{{ route('meetings.index') }}">Meetings</a>
<span class="breadcrumb-separator">/</span>
<a href="{{ route('meetings.types.index') }}">Types</a>
<span class="breadcrumb-separator">/</span>
<span>Edit</span>
@endsection

@section('content')
<div class="page-header">
    <div class="page-header-row">
        <div>
            <h1 class="page-title">Edit Meeting Type</h1>
            <p class="page-description">Update meeting type details.</p>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <form action="{{ route('meetings.types.update', $meetingType) }}" method="POST">
            @csrf
            @method('PUT')

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1.5rem;">
                <div>
                    <label class="form-label">Name <span style="color: var(--danger);">*</span></label>
                    <input type="text" name="name" class="form-input @error('name') is-invalid @enderror" value="{{ old('name', $meetingType->name) }}" required>
                    @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div>
                    <label class="form-label">Code <span style="color: var(--danger);">*</span></label>
                    <input type="text" name="code" class="form-input @error('code') is-invalid @enderror" value="{{ old('code', $meetingType->code) }}" required>
                    @error('code')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div>
                    <label class="form-label">Color</label>
                    <div style="display: flex; align-items: center; gap: 0.75rem;">
                        <input type="color" name="color" class="color-picker-input @error('color') is-invalid @enderror" value="{{ old('color', $meetingType->color ?? '#3b82f6') }}" style="width: 48px; height: 48px; padding: 0; border: 1px solid var(--border); border-radius: var(--radius, 0.5rem); cursor: pointer; background: none;">
                        <input type="text" name="color_text" class="form-input @error('color') is-invalid @enderror" value="{{ old('color', $meetingType->color ?? '#3b82f6') }}" placeholder="#3b82f6" style="width: 140px; font-family: monospace;">
                    </div>
                    @error('color')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div>
                    <label class="form-label">Sort Order</label>
                    <input type="number" name="sort_order" class="form-input @error('sort_order') is-invalid @enderror" value="{{ old('sort_order', $meetingType->sort_order) }}" min="0">
                    @error('sort_order')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div style="display: flex; align-items: center; gap: 0.5rem; margin-top: 0.5rem;">
                    <input type="checkbox" name="is_active" id="is_active" value="1" {{ old('is_active', $meetingType->is_active) ? 'checked' : '' }} style="width: 18px; height: 18px; accent-color: var(--primary);">
                    <label for="is_active" class="form-label" style="margin-bottom: 0; cursor: pointer;">Active</label>
                </div>
            </div>

            <div style="margin-top: 1.5rem;">
                <label class="form-label">Description</label>
                <textarea name="description" class="form-input" rows="3">{{ old('description', $meetingType->description) }}</textarea>
            </div>

            <div style="display: flex; gap: 0.75rem; margin-top: 2rem;">
                <button type="submit" class="btn btn-primary">Update Type</button>
                <a href="{{ route('meetings.types.index') }}" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
