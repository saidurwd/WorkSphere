@extends('tyro-dashboard::layouts.admin')

@section('title', 'New Meeting')

@section('breadcrumb')
<a href="{{ route(\HasinHayder\TyroDashboard\Support\DashboardRoute::name('index')) }}">Dashboard</a>
<span class="breadcrumb-separator">/</span>
<a href="{{ route('meetings.index') }}">Meetings</a>
<span class="breadcrumb-separator">/</span>
<span>New Meeting</span>
@endsection

@section('content')
<div class="page-header">
    <div class="page-header-row">
        <div>
            <h1 class="page-title">Schedule New Meeting</h1>
            <p class="page-description">Fill in the details to schedule a new meeting.</p>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <form action="{{ route('meetings.store') }}" method="POST">
            @csrf

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1.5rem;">
                <div>
                    <label class="form-label">Meeting Title <span style="color: var(--danger);">*</span></label>
                    <input type="text" name="title" class="form-input @error('title') is-invalid @enderror" value="{{ old('title') }}" required>
                    @error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div>
                    <label class="form-label">Meeting Type <span style="color: var(--danger);">*</span></label>
                    <select name="meeting_type_id" class="form-select @error('meeting_type_id') is-invalid @enderror" required>
                        <option value="">Select Type</option>
                        @foreach($types as $type)
                            <option value="{{ $type->id }}" {{ old('meeting_type_id') == $type->id ? 'selected' : '' }}>{{ $type->name }}</option>
                        @endforeach
                    </select>
                    @error('meeting_type_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div>
                    <label class="form-label">Organizer <span style="color: var(--danger);">*</span></label>
                    <select name="organizer_id" class="form-select @error('organizer_id') is-invalid @enderror" required>
                        <option value="">Select Organizer</option>
                        @foreach($users as $user)
                            <option value="{{ $user->id }}" {{ old('organizer_id') == $user->id ? 'selected' : '' }}>{{ $user->name }}</option>
                        @endforeach
                    </select>
                    @error('organizer_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div>
                    <label class="form-label">Chairperson</label>
                    <select name="chairperson_id" class="form-select @error('chairperson_id') is-invalid @enderror">
                        <option value="">Select Chairperson</option>
                        @foreach($users as $user)
                            <option value="{{ $user->id }}" {{ old('chairperson_id') == $user->id ? 'selected' : '' }}>{{ $user->name }}</option>
                        @endforeach
                    </select>
                    @error('chairperson_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div>
                    <label class="form-label">Department</label>
                    <select name="department_id" class="form-select @error('department_id') is-invalid @enderror">
                        <option value="">Select Department</option>
                        @foreach($departments as $department)
                            <option value="{{ $department->id }}" {{ old('department_id') == $department->id ? 'selected' : '' }}>{{ $department->department_name }}</option>
                        @endforeach
                    </select>
                    @error('department_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div>
                    <label class="form-label">Location</label>
                    <input type="text" name="location" class="form-input @error('location') is-invalid @enderror" value="{{ old('location') }}">
                    @error('location')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div>
                    <label class="form-label">Meeting Date <span style="color: var(--danger);">*</span></label>
                    <input type="date" name="meeting_date" class="form-input @error('meeting_date') is-invalid @enderror" value="{{ old('meeting_date') }}" required>
                    @error('meeting_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div>
                    <label class="form-label">Start Time <span style="color: var(--danger);">*</span></label>
                    <input type="time" name="start_time" class="form-input @error('start_time') is-invalid @enderror" value="{{ old('start_time') }}" required>
                    @error('start_time')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div>
                    <label class="form-label">End Time <span style="color: var(--danger);">*</span></label>
                    <input type="time" name="end_time" class="form-input @error('end_time') is-invalid @enderror" value="{{ old('end_time') }}" required>
                    @error('end_time')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div>
                    <label class="form-label">Priority</label>
                    <select name="priority" class="form-select @error('priority') is-invalid @enderror">
                        <option value="normal" {{ old('priority') === 'normal' ? 'selected' : '' }}>Normal</option>
                        <option value="important" {{ old('priority') === 'important' ? 'selected' : '' }}>Important</option>
                        <option value="urgent" {{ old('priority') === 'urgent' ? 'selected' : '' }}>Urgent</option>
                    </select>
                    @error('priority')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>

            <div style="margin-top: 1.5rem;">
                <label class="form-label">Description</label>
                <textarea name="description" class="form-input" rows="3">{{ old('description') }}</textarea>
            </div>

            <div style="margin-top: 1.5rem;">
                <label class="form-label">Agenda</label>
                <textarea name="agenda" class="form-input" rows="4">{{ old('agenda') }}</textarea>
            </div>

            <div style="display: flex; gap: 0.75rem; margin-top: 2rem;">
                <button type="submit" class="btn btn-primary">Create Meeting</button>
                <a href="{{ route('meetings.index') }}" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
