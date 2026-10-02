@extends('layouts.app')

@section('title', 'New Agenda Item')

@php
    $breadcrumbs = [
        ['label' => 'Dashboard', 'url' => route('dashboard.index')],
        ['label' => 'Meetings', 'url' => route('meetings.index')],
        ['label' => $meeting->title, 'url' => route('meetings.show', $meeting)],
        ['label' => 'New Agenda'],
    ];
@endphp

@section('content')
<x-page-header title="New Agenda Item" subtitle="Add an agenda item to {{ $meeting->title }}" />

<div class="card">
    <div class="card-body">
        <form action="{{ route('meetings.agendas.store', $meeting) }}" method="POST">
            @csrf

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1.5rem;">
                <div>
                    <label class="form-label" for="agenda-no">Agenda # <span style="color: var(--danger);">*</span></label>
                    <input type="number" name="agenda_no" class="form-control @error('agenda_no') is-invalid @enderror" value="{{ old('agenda_no') }}" min="1" required id="agenda-no">
                    @error('agenda_no')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div>
                    <label class="form-label" for="title">Title <span style="color: var(--danger);">*</span></label>
                    <input type="text" name="title" class="form-control @error('title') is-invalid @enderror" value="{{ old('title') }}" required id="title">
                    @error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div>
                    <label class="form-label" for="presented-by">Presented By</label>
                    <select name="presented_by" class="form-select @error('presented_by') is-invalid @enderror" id="presented-by">
                        <option value="">Select Presenter</option>
                        @foreach($users as $user)
                            <option value="{{ $user->id }}" {{ old('presented_by') == $user->id ? 'selected' : '' }}>{{ $user->name }}</option>
                        @endforeach
                    </select>
                    @error('presented_by')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div>
                    <label class="form-label" for="estimated-minutes">Estimated Minutes</label>
                    <input type="number" name="estimated_minutes" class="form-control @error('estimated_minutes') is-invalid @enderror" value="{{ old('estimated_minutes') }}" min="1" id="estimated-minutes">
                    @error('estimated_minutes')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div>
                    <label class="form-label" for="status">Status</label>
                    <select name="status" class="form-select @error('status') is-invalid @enderror" id="status">
                        <option value="pending" {{ old('status') === 'pending' ? 'selected' : '' }}>Pending</option>
                        <option value="in_progress" {{ old('status') === 'in_progress' ? 'selected' : '' }}>In Progress</option>
                        <option value="completed" {{ old('status') === 'completed' ? 'selected' : '' }}>Completed</option>
                        <option value="skipped" {{ old('status') === 'skipped' ? 'selected' : '' }}>Skipped</option>
                    </select>
                    @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div>
                    <label class="form-label" for="sort-order">Sort Order</label>
                    <input type="number" name="sort_order" class="form-control @error('sort_order') is-invalid @enderror" value="{{ old('sort_order', 0) }}" min="0" id="sort-order">
                    @error('sort_order')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>

            <div style="margin-top: 1.5rem;">
                <label class="form-label" for="description">Description</label>
                <textarea name="description" class="form-control" rows="3" id="description">{{ old('description') }}</textarea>
            </div>

            <div style="display: flex; gap: 0.75rem; margin-top: 2rem;">
                <button type="submit" class="btn btn-primary">Create Agenda</button>
                <a href="{{ route('meetings.show', $meeting) }}" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
