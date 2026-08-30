@extends('tyro-dashboard::layouts.admin')

@section('title', 'Documents')

@section('breadcrumb')
<a href="{{ route(\HasinHayder\TyroDashboard\Support\DashboardRoute::name('index')) }}">Dashboard</a>
<span class="breadcrumb-separator">/</span>
<a href="{{ route('obligations.dashboard') }}">Obligations</a>
<span class="breadcrumb-separator">/</span>
<span>Documents</span>
@endsection

@section('content')
<div class="page-header">
    <div class="page-header-row">
        <div>
            <h1 class="page-title">Obligation Documents</h1>
            <p class="page-description">All documents uploaded across obligations.</p>
        </div>
    </div>
</div>

<div class="card" style="margin-bottom: 1rem;">
    <div class="card-body">
        <form action="{{ route('obligations.documents') }}" method="GET" id="filter-form">
            <div class="filters-bar">
                <div class="search-box">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                    <input type="search" name="search" class="form-input" placeholder="Search documents or obligations..." value="{{ $filters['search'] ?? '' }}">
                </div>

                <div class="filter-group">
                    <label class="filter-label">Type:</label>
                    <select name="document_type" class="form-select" style="min-width: 180px;" onchange="document.getElementById('filter-form').submit()">
                        <option value="">All Types</option>
                        @foreach($documentTypes as $type)
                            <option value="{{ $type }}" {{ ($filters['document_type'] ?? '') === $type ? 'selected' : '' }}>
                                {{ $type }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <button type="submit" class="btn btn-secondary">Search</button>

                @if(!empty(array_filter($filters)))
                    <a href="{{ route('obligations.documents') }}" class="btn btn-ghost">Clear</a>
                @endif
            </div>
        </form>
    </div>
</div>

<div class="card">
    @if($documents->count())
        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th>Obligation</th>
                        <th>Document Type</th>
                        <th>File Name</th>
                        <th>Uploaded By</th>
                        <th>Uploaded At</th>
                        <th style="text-align: right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($documents as $document)
                    <tr>
                        <td>
                            @if($document->obligation)
                                <a href="{{ route('obligations.show', $document->obligation) }}" style="text-decoration: none; color: inherit; font-weight: 500;">
                                    {{ $document->obligation->obligation_no }} - {{ $document->obligation->title }}
                                </a>
                            @else
                                <span style="color: var(--muted-foreground);">N/A</span>
                            @endif
                        </td>
                        <td><span class="badge badge-secondary">{{ $document->document_type }}</span></td>
                        <td>{{ $document->file_name }}</td>
                        <td>{{ $document->uploader->name ?? 'N/A' }}</td>
                        <td>{{ $document->created_at->format('M d, Y') }}</td>
                        <td style="text-align: right;">
                            <a href="{{ Storage::url($document->file_path) }}" class="action-btn" title="Download" target="_blank">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                                </svg>
                            </a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @if($documents->hasPages())
        <div class="pagination">
            {{ $documents->links() }}
        </div>
        @endif
    @else
        <div class="empty-state">
            <svg class="empty-state-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
            <h3 class="empty-state-title">No documents found</h3>
            <p class="empty-state-description">No documents have been uploaded yet.</p>
        </div>
    @endif
</div>
@endsection
