@extends('layouts.app')

@section('title', 'New To-Do')

@section('content')
    <x-page-header title="New To-Do" subtitle="A title is all that is required." icon="plus-circle" />

    <div class="card">
        <div class="card-body">
            <form action="{{ route('todos.store') }}" method="POST">
                @csrf
                @include('todos._form', ['todo' => $todo, 'users' => $users, 'departments' => $departments])

                <div class="d-flex gap-2 mt-4">
                    <button type="submit" class="btn btn-primary">
                        <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                        Create To-Do
                    </button>
                    <a href="{{ route('todos.index') }}" class="btn btn-outline-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
@endsection
