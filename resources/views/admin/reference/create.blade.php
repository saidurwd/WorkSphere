@extends('layouts.app')

@section('title', 'New ' . $descriptor['singular'])

@section('content')
    <x-page-header :title="'New ' . $descriptor['singular']" :icon="$descriptor['icon']" />

    <div class="card">
        <div class="card-body">
            <form action="{{ route('admin.reference.store', $resource) }}" method="POST">
                @csrf
                @include('admin.reference._form', ['descriptor' => $descriptor, 'row' => $row, 'options' => $options])

                <div class="d-flex gap-2 mt-4">
                    <button type="submit" class="btn btn-primary">Create</button>
                    <a href="{{ route('admin.reference.index', $resource) }}" class="btn btn-outline-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
@endsection
