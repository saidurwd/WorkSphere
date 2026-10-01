@extends('layouts.app')

@section('title', 'Edit To-Do')

@section('content')
    <x-page-header :title="$todo->title" subtitle="Edit this To-Do." icon="pencil" />

    <div class="card">
        <div class="card-body">
            <form action="{{ route('todos.update', $todo) }}" method="POST">
                @csrf
                @method('PUT')
                @include('todos._form', ['todo' => $todo, 'users' => $users, 'departments' => $departments])

                <div class="d-flex gap-2 mt-4">
                    <button type="submit" class="btn btn-primary">
                        <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                        Save changes
                    </button>
                    <a href="{{ route('todos.show', $todo) }}" class="btn btn-outline-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
@endsection
