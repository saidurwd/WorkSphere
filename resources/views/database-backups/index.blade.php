@extends('tyro-dashboard::layouts.admin')

@section('title', 'Database Backup')

@section('breadcrumb')
<a href="{{ route('dashboard.index') }}">Dashboard</a>
<span class="breadcrumb-separator">/</span>
<span>Database Backup</span>
@endsection

@section('content')
<div class="page-header">
    <div class="page-header-row">
        <div>
            <h1 class="page-title">Database Backup</h1>
            <p class="page-description">Create and download professional MySQL database backups.</p>
        </div>
        <div>
            <form action="{{ route('dashboard.database-backups.store') }}" method="POST" style="display: inline;">
                @csrf
                <input type="hidden" name="name" value="{{ config('database.connections.mysql.database') }}">
                <button type="submit" class="btn btn-primary">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 1rem; height: 1rem;">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                    </svg>
                    Create Backup
                </button>
            </form>
        </div>
    </div>
</div>

@if(session('success'))
<div class="alert alert-success" role="alert">
    {{ session('success') }}
</div>
@endif

<div class="card">
    <div class="card-header">
        <h3 class="card-title" style="font-size: 1.0625rem;">Available Backups</h3>
    </div>
    <div class="card-body">
        @if(count($backups))
        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th>Filename</th>
                        <th>Size</th>
                        <th>Created</th>
                        <th style="text-align: right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($backups as $backup)
                    <tr>
                        <td>
                            <span style="font-weight: 500;">{{ $backup['filename'] }}</span>
                        </td>
                        <td>{{ $backup['human_size'] }}</td>
                        <td>{{ $backup['last_modified_human'] }}</td>
                        <td>
                            <div class="action-buttons" style="justify-content: flex-end;">
                                <a href="{{ route('dashboard.database-backups.download', $backup['filename']) }}" class="action-btn" title="Download">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M7 10l5 5 5-5M12 15V3" />
                                    </svg>
                                </a>
                                <form action="{{ route('dashboard.database-backups.destroy', $backup['filename']) }}" method="POST" style="display: inline;" id="delete-backup-form-{{ $backup['filename'] }}">
                                    @csrf
                                    @method('DELETE')
                                    <button type="button" class="action-btn action-btn-danger" title="Delete" onclick="if (confirm('Are you sure you want to delete this backup? This action cannot be undone.')) { document.getElementById('delete-backup-form-{{ $backup['filename'] }}').submit(); }">
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
        @else
        <div class="empty-state">
            <svg class="empty-state-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M7 10l5 5 5-5M12 15V3" />
            </svg>
            <h3 class="empty-state-title">No backups yet</h3>
            <p class="empty-state-description">Create your first database backup to keep your data safe.</p>
            <form action="{{ route('dashboard.database-backups.store') }}" method="POST" style="display: inline;">
                @csrf
                <input type="hidden" name="name" value="{{ config('database.connections.mysql.database') }}">
                <button type="submit" class="btn btn-primary">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 1rem; height: 1rem;">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                    </svg>
                    Create Backup
                </button>
            </form>
        </div>
        @endif
    </div>
</div>
 @endsection
