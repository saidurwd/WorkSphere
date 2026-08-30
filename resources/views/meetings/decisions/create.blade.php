@extends('tyro-dashboard::layouts.admin')

@section('title', 'New Decision')

@section('breadcrumb')
<a href="{{ route(\HasinHayder\TyroDashboard\Support\DashboardRoute::name('index')) }}">Dashboard</a>
<span class="breadcrumb-separator">/</span>
<a href="{{ route('meetings.index') }}">Meetings</a>
<span class="breadcrumb-separator">/</span>
<a href="{{ route('meetings.show', $meeting) }}">{{ $meeting->title }}</a>
<span class="breadcrumb-separator">/</span>
<span>New Decision</span>
@endsection

@section('content')
<div class="page-header">
    <div class="page-header-row">
        <div>
            <h1 class="page-title">New Decision</h1>
            <p class="page-description">Record a decision for {{ $meeting->title }}</p>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <form action="{{ route('meetings.decisions.store', $meeting) }}" method="POST">
            @csrf

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1.5rem;">
                <div>
                    <label class="form-label">Decision # <span style="color: var(--danger);">*</span></label>
                    <input type="number" name="decision_no" class="form-input @error('decision_no') is-invalid @enderror" value="{{ old('decision_no') }}" min="1" required>
                    @error('decision_no')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div>
                    <label class="form-label">Title <span style="color: var(--danger);">*</span></label>
                    <input type="text" name="decision_title" class="form-input @error('decision_title') is-invalid @enderror" value="{{ old('decision_title') }}" required>
                    @error('decision_title')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div>
                    <label class="form-label">Decision Type <span style="color: var(--danger);">*</span></label>
                    <select name="decision_type" class="form-select @error('decision_type') is-invalid @enderror">
                        <option value="approved" {{ old('decision_type') === 'approved' ? 'selected' : '' }}>Approved</option>
                        <option value="rejected" {{ old('decision_type') === 'rejected' ? 'selected' : '' }}>Rejected</option>
                        <option value="deferred" {{ old('decision_type') === 'deferred' ? 'selected' : '' }}>Deferred</option>
                        <option value="noted" {{ old('decision_type') === 'noted' ? 'selected' : '' }}>Noted</option>
                        <option value="further_discussion_required" {{ old('decision_type') === 'further_discussion_required' ? 'selected' : '' }}>Further Discussion Required</option>
                    </select>
                    @error('decision_type')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div>
                    <label class="form-label">Decision Status <span style="color: var(--danger);">*</span></label>
                    <select name="decision_status" class="form-select @error('decision_status') is-invalid @enderror">
                        <option value="active" {{ old('decision_status') === 'active' ? 'selected' : '' }}>Active</option>
                        <option value="superseded" {{ old('decision_status') === 'superseded' ? 'selected' : '' }}>Superseded</option>
                        <option value="cancelled" {{ old('decision_status') === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                    </select>
                    @error('decision_status')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div>
                    <label class="form-label">Decision Date</label>
                    <input type="date" name="decision_date" class="form-input @error('decision_date') is-invalid @enderror" value="{{ old('decision_date') }}">
                    @error('decision_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div>
                    <label class="form-label">Effective Date</label>
                    <input type="date" name="effective_date" class="form-input @error('effective_date') is-invalid @enderror" value="{{ old('effective_date') }}">
                    @error('effective_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>

            <div style="margin-top: 1.5rem;">
                <label class="form-label">Description</label>
                <textarea name="decision_description" class="form-input" rows="3">{{ old('decision_description') }}</textarea>
            </div>

            <div style="margin-top: 1.5rem;">
                <label class="form-label">Remarks</label>
                <textarea name="remarks" class="form-input" rows="2">{{ old('remarks') }}</textarea>
            </div>

            <div style="display: flex; gap: 0.75rem; margin-top: 2rem;">
                <button type="submit" class="btn btn-primary">Create Decision</button>
                <a href="{{ route('meetings.show', $meeting) }}" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
