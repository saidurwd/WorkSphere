@extends('layouts.app')

@section('title', 'Add Participant')

@php
    $breadcrumbs = [
        ['label' => 'Dashboard', 'url' => route('dashboard.index')],
        ['label' => 'Meetings', 'url' => route('meetings.index')],
        ['label' => $meeting->title, 'url' => route('meetings.show', $meeting)],
        ['label' => 'Add Participant'],
    ];
@endphp

@section('content')
<x-page-header title="Add Participant" subtitle="Add a participant to {{ $meeting->title }}" />

<div class="card">
    <div class="card-body">
        <form action="{{ route('meetings.participants.store', $meeting) }}" method="POST">
            @csrf

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1.5rem;">
                <div>
                    <label class="form-label">User <span style="color: var(--danger);">*</span></label>
                    <select name="user_id" class="form-select @error('user_id') is-invalid @enderror" required>
                        <option value="">Select User</option>
                        @foreach($users as $user)
                            <option value="{{ $user->id }}" {{ old('user_id') == $user->id ? 'selected' : '' }}>{{ $user->name }}</option>
                        @endforeach
                    </select>
                    @error('user_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div>
                    <label class="form-label">Participant Type <span style="color: var(--danger);">*</span></label>
                    <select name="participant_type" class="form-select @error('participant_type') is-invalid @enderror">
                        <option value="member" {{ old('participant_type') === 'member' ? 'selected' : '' }}>Member</option>
                        <option value="organizer" {{ old('participant_type') === 'organizer' ? 'selected' : '' }}>Organizer</option>
                        <option value="chairperson" {{ old('participant_type') === 'chairperson' ? 'selected' : '' }}>Chairperson</option>
                        <option value="guest" {{ old('participant_type') === 'guest' ? 'selected' : '' }}>Guest</option>
                        <option value="presenter" {{ old('participant_type') === 'presenter' ? 'selected' : '' }}>Presenter</option>
                        <option value="observer" {{ old('participant_type') === 'observer' ? 'selected' : '' }}>Observer</option>
                    </select>
                    @error('participant_type')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div>
                    <label class="form-label">Attendance Status <span style="color: var(--danger);">*</span></label>
                    <select name="attendance_status" class="form-select @error('attendance_status') is-invalid @enderror">
                        <option value="invited" {{ old('attendance_status') === 'invited' ? 'selected' : '' }}>Invited</option>
                        <option value="accepted" {{ old('attendance_status') === 'accepted' ? 'selected' : '' }}>Accepted</option>
                        <option value="declined" {{ old('attendance_status') === 'declined' ? 'selected' : '' }}>Declined</option>
                        <option value="present" {{ old('attendance_status') === 'present' ? 'selected' : '' }}>Present</option>
                        <option value="absent" {{ old('attendance_status') === 'absent' ? 'selected' : '' }}>Absent</option>
                        <option value="apology" {{ old('attendance_status') === 'apology' ? 'selected' : '' }}>Apology</option>
                    </select>
                    @error('attendance_status')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>

            <div style="margin-top: 1.5rem;">
                <label class="form-label">Remarks</label>
                <textarea name="remarks" class="form-control" rows="2">{{ old('remarks') }}</textarea>
            </div>

            <div style="display: flex; gap: 0.75rem; margin-top: 2rem;">
                <button type="submit" class="btn btn-primary">Add Participant</button>
                <a href="{{ route('meetings.show', $meeting) }}" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
