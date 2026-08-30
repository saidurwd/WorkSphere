@extends('tyro-dashboard::layouts.admin')

@section('title', 'Attachments')

@section('breadcrumb')
<a href="{{ route(\HasinHayder\TyroDashboard\Support\DashboardRoute::name('index')) }}">Dashboard</a>
<span class="breadcrumb-separator">/</span>
<a href="{{ route('meetings.index') }}">Meetings</a>
<span class="breadcrumb-separator">/</span>
<a href="{{ route('meetings.show', $meeting) }}">{{ $meeting->title }}</a>
<span class="breadcrumb-separator">/</span>
<span>Attachments</span>
@endsection

@section('content')
<div class="page-header">
    <div class="page-header-row">
        <div>
            <h1 class="page-title">Attachments</h1>
            <p class="page-description">Manage files for {{ $meeting->title }}</p>
        </div>
        <div>
            <a href="{{ route('meetings.attachments.create', $meeting) }}" class="btn btn-primary">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                </svg>
                Upload File
            </a>
        </div>
    </div>
</div>

<div class="card">
    @if($attachments->count())
        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th>File Name</th>
                        <th>Type</th>
                        <th>Size</th>
                        <th>Uploaded By</th>
                        <th>Description</th>
                        <th style="text-align: right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($attachments as $attachment)
                    <tr>
                        <td>
                            <a href="{{ asset('storage/' . $attachment->file_path) }}" target="_blank" style="font-weight: 500; text-decoration: none; color: inherit;">
                                {{ $attachment->file_name }}
                            </a>
                        </td>
                        <td>{{ $attachment->file_type ?? 'N/A' }}</td>
                        <td>{{ $attachment->file_size ? number_format($attachment->file_size / 1024, 2) . ' KB' : 'N/A' }}</td>
                        <td>{{ $attachment->uploadedBy->name ?? 'N/A' }}</td>
                        <td>{{ $attachment->description ?? 'N/A' }}</td>
                        <td>
                            <div class="action-buttons" style="justify-content: flex-end;">
                                <a href="{{ asset('storage/' . $attachment->file_path) }}" target="_blank" class="action-btn" title="Download">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" />
                                    </svg>
                                </a>
                                <form action="{{ route('meetings.attachments.destroy', [$meeting, $attachment]) }}" method="POST" style="display: inline;" id="delete-attachment-form-{{ $attachment->id }}">
                                    @csrf
                                    @method('DELETE')
                                    <button type="button" class="action-btn action-btn-danger" title="Delete" onclick="if (confirm('Are you sure you want to delete this attachment?')) { document.getElementById('delete-attachment-form-{{ $attachment->id }}').submit(); }">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @if($attachments->hasPages())
        <div class="pagination">
            {{ $attachments->links() }}
        </div>
        @endif
    @else
        <div class="empty-state">
            <p style="margin: 0; color: var(--muted-foreground);">No attachments uploaded yet.</p>
        </div>
    @endif
</div>
@endsection
