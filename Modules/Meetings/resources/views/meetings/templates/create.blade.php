@extends('layouts.app')

@section('title', 'New Meeting Template')

@section('content')
    <x-page-header title="New template" subtitle="A reusable meeting shape with a standing agenda." icon="clipboard" />

    <div class="card">
        <div class="card-body">
            <form action="{{ route('meetings.templates.store') }}" method="POST">
                @csrf
                @include('meetings.templates._form', ['template' => $template, 'types' => $types])

                <div class="d-flex gap-2 mt-4">
                    <button type="submit" class="btn btn-primary">Create template</button>
                    <a href="{{ route('meetings.templates.index') }}" class="btn btn-outline-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
@endsection
