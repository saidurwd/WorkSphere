@extends('layouts.app')

@section('title', 'Documents')

@section('content')
    <x-page-header title="Documents" subtitle="All documents uploaded across obligations." icon="folder" />

    <div class="card mb-4">
        <div class="card-body">
            <form action="{{ route('obligations.documents') }}" method="GET" id="filter-form">
                <div class="row g-2 align-items-end">
                    <div class="col-12 col-md-6 col-lg-3">
                        <label for="document-search" class="form-label">Search</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-search"></i></span>
                            <input id="document-search" type="search" name="search" class="form-control"
                                   placeholder="Search documents or obligations..." value="{{ request('search') }}">
                        </div>
                    </div>

                    <div class="col-6 col-md-4 col-lg-2">
                        <label for="type-filter" class="form-label">Type</label>
                        <select id="type-filter" name="document_type" class="form-select" onchange="document.getElementById('filter-form').submit()">
                            <option value="">All</option>
                            @foreach($documentTypes as $type)
                                <option value="{{ $type }}" {{ request('document_type') === $type ? 'selected' : '' }}>{{ $type }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-12 d-flex gap-2">
                        <button type="submit" class="btn btn-primary"><i class="bi bi-funnel me-1"></i>Apply</button>
                        @if(request()->hasAny(['search', 'document_type']))
                            <a href="{{ route('obligations.documents') }}" class="btn btn-outline-secondary"><i class="bi bi-x-lg me-1"></i>Clear</a>
                        @endif
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        @if($documents->count())
            <div class="card-body p-0">
                <x-datatable id="documents-table" :options="['pageLength' => 20, 'order' => [[0, 'desc']]]">
                    <thead>
                        <tr>
                            <th scope="col">Obligation</th>
                            <th scope="col">Document Type</th>
                            <th scope="col">File Name</th>
                            <th scope="col">Uploaded By</th>
                            <th scope="col">Uploaded At</th>
                            <th scope="col" class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($documents as $document)
                            <tr>
                                <td>
                                    @if($document->obligation)
                                        <a href="{{ route('obligations.show', $document->obligation) }}" class="fw-semibold text-decoration-none">
                                            {{ $document->obligation->obligation_no }} - {{ $document->obligation->title }}
                                        </a>
                                    @else
                                        <span class="text-body-secondary">N/A</span>
                                    @endif
                                </td>
                                <td>
                                    <x-badge variant="secondary">{{ $document->document_type }}</x-badge>
                                </td>
                                <td>{{ $document->file_name }}</td>
                                <td>{{ $document->uploader->name ?? 'N/A' }}</td>
                                <td>{{ $document->created_at->format('M d, Y') }}</td>
                                <td>
                                    <div class="d-flex justify-content-end gap-1">
                                        <a href="{{ Storage::url($document->file_path) }}" target="_blank" class="btn btn-sm btn-outline-primary" title="Download">
                                            <i class="bi bi-download"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </x-datatable>
            </div>

            @if ($documents->hasPages())
                <div class="card-footer">
                    <x-pagination :paginator="$documents" />
                </div>
            @endif
        @else
            <div class="card-body">
                <x-empty-state icon="folder" title="No documents found" description="No documents have been uploaded yet." />
            </div>
        @endif
    </div>
@endsection
