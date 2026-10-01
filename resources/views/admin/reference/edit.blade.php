@extends('layouts.app')

@section('title', 'Edit ' . $descriptor['singular'])

@section('content')
    <x-page-header :title="$row->{$descriptor['fields'][1]} ?? ('Edit ' . $descriptor['singular'])"
                   :icon="$descriptor['icon']" />

    <div class="card">
        <div class="card-body">
            <form action="{{ route('admin.reference.update', [$resource, $row->getKey()]) }}" method="POST">
                @csrf
                @method('PUT')
                @include('admin.reference._form', ['descriptor' => $descriptor, 'row' => $row, 'options' => $options])

                <div class="d-flex gap-2 mt-4">
                    <button type="submit" class="btn btn-primary">Save changes</button>
                    <a href="{{ route('admin.reference.index', $resource) }}" class="btn btn-outline-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
@endsection
