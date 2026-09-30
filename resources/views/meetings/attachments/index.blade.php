@extends('layouts.app')

@section('title', 'Attachments')

@section('content')
    <x-page-header title="Attachments" subtitle="Manage files for {{ $meeting->title }}" icon="paperclip">
        <x-btn :href="route('meetings.attachments.create', $meeting)" icon="upload">Upload File</x-btn>
    </x-page-header>

    <div class="card">
        @if($attachments->count())
            <div class="card-body p-0">
                <x-datatable id="attachments-table" :options="['pageLength' => 20, 'order' => [[0, 'desc']]]">
                    <thead>
                        <tr>
                            <th scope="col">File Name</th>
                            <th scope="col">Type</th>
                            <th scope="col">Size</th>
                            <th scope="col">Uploaded By</th>
                            <th scope="col">Description</th>
                            <th scope="col" class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($attachments as $attachment)
                            <tr>
                                <td>
                                    <a href="{{ asset('storage/' . $attachment->file_path) }}" target="_blank" class="fw-semibold text-decoration-none">
                                        {{ $attachment->file_name }}
                                    </a>
                                </td>
                                <td>{{ $attachment->file_type ?? 'N/A' }}</td>
                                <td>{{ $attachment->file_size ? number_format($attachment->file_size / 1024, 2) . ' KB' : 'N/A' }}</td>
                                <td>{{ $attachment->uploadedBy->name ?? 'N/A' }}</td>
                                <td>{{ $attachment->description ?? 'N/A' }}</td>
                                <td>
                                    <div class="d-flex justify-content-end gap-1">
                                        <a href="{{ asset('storage/' . $attachment->file_path) }}" target="_blank" class="btn btn-sm btn-outline-primary" title="Download">
                                            <i class="bi bi-download"></i>
                                        </a>
                                        <form action="{{ route('meetings.attachments.destroy', [$meeting, $attachment]) }}" method="POST" class="d-inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger"
                                                    data-confirm="Delete this attachment?"
                                                    data-confirm-button="Delete" aria-label="Delete" title="Delete">
                                                <i class="bi bi-trash3"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </x-datatable>
            </div>

            @if ($attachments->hasPages())
                <div class="card-footer">
                    <x-pagination :paginator="$attachments" />
                </div>
            @endif
        @else
            <div class="card-body">
                <x-empty-state icon="paperclip" title="No attachments uploaded yet" description="Upload files to share with meeting participants." />
            </div>
        @endif
    </div>
@endsection
