@extends('layouts.app')

@section('title', 'Edit Meeting Template')

@section('content')
    <x-page-header :title="$template->name" subtitle="Edit this template." icon="pencil" />

    <div class="card">
        <div class="card-body">
            <form action="{{ route('meetings.templates.update', $template) }}" method="POST">
                @csrf
                @method('PUT')
                @include('meetings.templates._form', ['template' => $template, 'types' => $types])

                <div class="d-flex gap-2 mt-4">
                    <button type="submit" class="btn btn-primary">Save changes</button>
                    <a href="{{ route('meetings.templates.show', $template) }}" class="btn btn-outline-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
@endsection
