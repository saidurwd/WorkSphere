@extends('layouts.app')

@section('title', 'Database Backup')

@section('content')
    <x-page-header title="Database Backup" subtitle="Create and download professional MySQL database backups." icon="database">
        <form action="{{ route('dashboard.database-backups.store') }}" method="POST" class="d-inline">
            @csrf
            <input type="hidden" name="name" value="{{ config('database.connections.mysql.database') }}">
            <x-btn type="submit" icon="plus-lg">Create Backup</x-btn>
        </form>
    </x-page-header>

    @if(session('success'))
        <x-alert type="success" class="alert-dismissible fade show mb-3">{{ session('success') }}</x-alert>
    @endif

    <div class="card">
        @if(count($backups))
            <div class="card-body p-0">
                <x-datatable id="backups-table" :options="['pageLength' => 20, 'order' => [[0, 'desc']]]">
                    <thead>
                        <tr>
                            <th scope="col">Filename</th>
                            <th scope="col">Size</th>
                            <th scope="col">Created</th>
                            <th scope="col" class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($backups as $backup)
                            <tr>
                                <td><span class="fw-semibold">{{ $backup['filename'] }}</span></td>
                                <td>{{ $backup['human_size'] }}</td>
                                <td>{{ $backup['last_modified_human'] }}</td>
                                <td>
                                    <div class="d-flex justify-content-end gap-1">
                                        <a href="{{ route('dashboard.database-backups.download', $backup['filename']) }}" class="btn btn-sm btn-outline-primary" title="Download">
                                            <i class="bi bi-download"></i>
                                        </a>
                                        <form action="{{ route('dashboard.database-backups.destroy', $backup['filename']) }}" method="POST" class="d-inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger"
                                                    data-confirm="Delete backup {{ $backup['filename'] }}? This cannot be undone."
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
        @else
            <div class="card-body">
                <x-empty-state icon="database" title="No backups yet" description="Create your first database backup to keep your data safe.">
                    <form action="{{ route('dashboard.database-backups.store') }}" method="POST" class="d-inline">
                        @csrf
                        <input type="hidden" name="name" value="{{ config('database.connections.mysql.database') }}">
                        <x-btn type="submit" icon="plus-lg" size="sm">Create Backup</x-btn>
                    </form>
                </x-empty-state>
            </div>
        @endif
    </div>
@endsection
