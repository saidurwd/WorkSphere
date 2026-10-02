@extends('layouts.app')

@section('title', 'UI Kit')

@section('content')
<x-page-header title="UI Kit" subtitle="Every shared component in light and dark mode." />

<div class="row g-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header"><h2 class="card-title mb-0">Buttons &amp; badges</h2></div>
            <div class="card-body d-flex flex-wrap align-items-center gap-2">
                <x-btn variant="primary" icon="plus">Primary</x-btn>
                <x-btn variant="secondary" icon="download">Secondary</x-btn>
                <x-btn variant="success" icon="check2">Success</x-btn>
                <x-btn variant="warning" icon="exclamation-triangle">Warning</x-btn>
                <x-btn variant="danger" icon="trash3">Danger</x-btn>
                <x-btn variant="outline-secondary" icon="gear">Outline</x-btn>
                <x-btn variant="primary" size="sm" icon="plus">Small</x-btn>
                <x-btn variant="danger" size="sm" data-confirm="Delete this record?" data-confirm-button="Delete">
                    Confirm dialog
                </x-btn>
            </div>
        </div>
    </div>

    <div class="col-12">
        <div class="card">
            <div class="card-header"><h2 class="card-title mb-0">Stat cards</h2></div>
            <div class="card-body">
                <div class="row row-cols-1 row-cols-md-3 g-3 mb-0">
                    <x-stat class="col" title="Total Tasks" :value="128" icon="check2-square" variant="primary" description="Across all owners" />
                    <x-stat class="col" title="In Progress" :value="42" icon="arrow-repeat" variant="info" />
                    <x-stat class="col" title="Overdue" :value="7" icon="exclamation-triangle" variant="danger" />
                    <x-stat class="col" title="Completed" :value="79" icon="check2-all" variant="success" />
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header"><h2 class="card-title mb-0">Form controls</h2></div>
            <div class="card-body">
                <form>
                    <x-form.input name="ui_text" label="Text input" placeholder="Type something" required />
                    <x-form.input name="ui_date" label="Native date" type="date" />
                    <x-form.textarea name="ui_textarea" label="Textarea" :rows="3" help="Supporting text under a field." />
                    <x-form.select
                        name="ui_select"
                        label="Select"
                        :options="['active' => 'Active', 'inactive' => 'Inactive']"
                        value="active"
                        placeholder="Choose a status"
                    />
                    <x-form.input name="ui_checkbox" label="Checkbox" type="checkbox" />
                    <x-flatpickr name="ui_flatpickr" label="Date picker (flatpickr)" />
                    <x-tom-select name="ui_tom" label="Tom Select" placeholder="Pick people">
                        <option value="1">Ada Lovelace</option>
                        <option value="2">Grace Hopper</option>
                    </x-tom-select>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header"><h2 class="card-title mb-0">Alerts, empty state &amp; details</h2></div>
            <div class="card-body">
                <x-alert type="success" message="Saved successfully." dismissible />
                <x-alert type="info" message="Informational message." dismissible />
                <x-alert type="warning" message="Check the highlighted fields." dismissible />
                <x-alert type="danger" message="Something went wrong." dismissible />

                <div class="border rounded p-3 my-3">
                    <x-detail-list :items="[
                        ['label' => 'Reference', 'value' => 'REF-2026-0001'],
                        ['label' => 'Owner', 'value' => 'Operations'],
                        ['label' => 'Status', 'value' => null],
                    ]" />
                </div>

                <x-empty-state
                    icon="inbox"
                    title="Nothing here yet"
                    description="Records will appear once they are created."
                >
                    <x-btn variant="primary" icon="plus" size="sm">Create the first one</x-btn>
                </x-empty-state>
            </div>
        </div>
    </div>

    <div class="col-12">
        <div class="card">
            <div class="card-header"><h2 class="card-title mb-0">Table &amp; pagination</h2></div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped align-middle w-100">
                        <thead>
                            <tr>
                                <th scope="col">Reference</th>
                                <th scope="col">Title</th>
                                <th scope="col">Status</th>
                                <th scope="col" class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach (['REF-0001', 'REF-0002', 'REF-0003'] as $index => $reference)
                                <tr>
                                    <td>{{ $reference }}</td>
                                    <td>Sample record {{ $index + 1 }}</td>
                                    <td><x-badge :variant="$index === 2 ? 'danger' : 'success'">{{ $index === 2 ? 'Overdue' : 'Active' }}</x-badge></td>
                                    <td class="text-end">
                                        <div class="d-flex align-items-center gap-1 justify-content-end">
                                            <button type="button" class="btn btn-sm btn-outline-secondary d-inline-flex align-items-center justify-content-center" aria-label="Edit" title="Edit"><i class="bi bi-pencil"></i></button>
                                            <button type="button" class="btn btn-sm btn-outline-danger d-inline-flex align-items-center justify-content-center" aria-label="Delete" title="Delete" data-confirm="Delete {{ $reference }}?"><i class="bi bi-trash3"></i></button>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <x-pagination :paginator="new \Illuminate\Pagination\LengthAwarePaginator([], 0, 15, 1)" />
            </div>
        </div>
    </div>

    <div class="col-12">
        <div class="card">
            <div class="card-header"><h2 class="card-title mb-0">DataTables (server side ready)</h2></div>
            <div class="card-body">
                <x-datatable id="ui-kit-table" :options="['pageLength' => 5, 'order' => [[0, 'asc']]]">
                    <thead>
                        <tr>
                            <th scope="col">Reference</th>
                            <th scope="col">Title</th>
                            <th scope="col">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach (range(1, 12) as $row)
                            <tr>
                                <td>REF-{{ str_pad((string) $row, 4, '0', STR_PAD_LEFT) }}</td>
                                <td>Sample record {{ $row }}</td>
                                <td>{{ $row % 3 === 0 ? 'Overdue' : 'Active' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </x-datatable>
            </div>
        </div>
    </div>

    <div class="col-12">
        <div class="card">
            <div class="card-header"><h2 class="card-title mb-0">Modal</h2></div>
            <div class="card-body">
                <x-btn variant="secondary" icon="window" data-bs-toggle="modal" data-bs-target="#ui-kit-modal">Open modal</x-btn>

                <x-modal name="ui-kit-modal" title="Modal title">
                    <p class="mb-0">Modals are provided by Bootstrap 5.3 and shown through <code>data-bs-toggle="modal"</code>.</p>

                    <x-slot:actions>
                        <x-btn variant="secondary" data-bs-dismiss="modal">Close</x-btn>
                        <x-btn variant="primary">Save</x-btn>
                    </x-slot:actions>
                </x-modal>
            </div>
        </div>
    </div>
</div>
@endsection
