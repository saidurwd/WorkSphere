<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Location;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * One CRUD surface for every reference-data table — GAP-041.
 *
 * `employees`, `companies`, `departments` and `locations` all had models and all
 * had zero routes: reference data could only be changed with raw SQL, which is how
 * it ends up frozen at whatever the seeder wrote.
 *
 * The four are near-identical CRUD screens, so they share one controller driven by
 * a small descriptor rather than being four controllers with four copies of the
 * same index loop. The descriptor is what keeps them in step — a rule added to one
 * cannot be forgotten in the other.
 *
 * SECURITY: this is the highest-privilege surface in the application, so every
 * action is gated on `user.manage` through {@see Gate}, never on a role slug.
 */
class ReferenceDataController extends Controller
{
    /**
     * table => descriptor.
     *
     * @return array<string, array{model: class-string<Model>, label: string, singular: string, icon: string, fields: list<string>, rules: list<string>, searchable: list<string>}>
     */
    protected function descriptors(): array
    {
        return [
            'employees' => [
                'model' => Employee::class,
                'label' => 'Employees',
                'singular' => 'Employee',
                'icon' => 'person-badge',
                'searchable' => ['employee_code', 'employee_name', 'email', 'designation'],
                'fields' => ['employee_code', 'employee_name', 'email', 'phone', 'designation', 'department_id', 'location_id', 'joining_date', 'status'],
                'rules' => [
                    'employee_code' => ['required', 'string', 'max:50'],
                    'employee_name' => ['required', 'string', 'max:255'],
                    'email' => ['required', 'email', 'max:255'],
                    'phone' => ['nullable', 'string', 'max:50'],
                    'designation' => ['nullable', 'string', 'max:255'],
                    'department_id' => ['nullable', 'exists:departments,id'],
                    'location_id' => ['nullable', 'exists:locations,id'],
                    'joining_date' => ['nullable', 'date'],
                    'status' => ['required', Rule::in(['active', 'inactive'])],
                ],
            ],

            'companies' => [
                'model' => Company::class,
                'label' => 'Companies',
                'singular' => 'Company',
                'icon' => 'building',
                'searchable' => ['company_code', 'company_name', 'city'],
                'fields' => ['company_code', 'company_name', 'address', 'city', 'country', 'status'],
                'rules' => [
                    'company_code' => ['required', 'string', 'max:50'],
                    'company_name' => ['required', 'string', 'max:255'],
                    'address' => ['nullable', 'string', 'max:500'],
                    'city' => ['nullable', 'string', 'max:120'],
                    'country' => ['nullable', 'string', 'max:120'],
                    'status' => ['required', Rule::in(['active', 'inactive'])],
                ],
            ],

            'departments' => [
                'model' => Department::class,
                'label' => 'Departments',
                'singular' => 'Department',
                'icon' => 'diagram-3',
                'searchable' => ['department_code', 'department_name'],
                'fields' => ['department_code', 'department_name', 'head_of_department_id', 'status'],
                'rules' => [
                    'department_code' => ['required', 'string', 'max:50'],
                    'department_name' => ['required', 'string', 'max:255'],
                    // The head is an EMPLOYEE, not a user — Phase 8 GAP-040. The
                    // free-text `head_of_department` column was dropped in 2026_07_09.
                    'head_of_department_id' => ['nullable', 'exists:employees,id'],
                    'status' => ['required', Rule::in(['active', 'inactive'])],
                ],
            ],

            'locations' => [
                'model' => Location::class,
                'label' => 'Locations',
                'singular' => 'Location',
                'icon' => 'geo-alt',
                'searchable' => ['location_code', 'location_name', 'city'],
                'fields' => ['location_code', 'location_name', 'address', 'city', 'country', 'status'],
                'rules' => [
                    'location_code' => ['required', 'string', 'max:50'],
                    'location_name' => ['required', 'string', 'max:255'],
                    'address' => ['nullable', 'string', 'max:500'],
                    'city' => ['nullable', 'string', 'max:120'],
                    'country' => ['nullable', 'string', 'max:120'],
                    'status' => ['required', Rule::in(['active', 'inactive'])],
                ],
            ],
        ];
    }

    public function index(Request $request, string $resource): View
    {
        $this->authorize('viewAny', User::class);
        $descriptor = $this->descriptor($resource);

        $rows = $descriptor['model']::query()
            ->when($request->filled('search'), function ($query) use ($request, $descriptor): void {
                $term = '%'.trim((string) $request->input('search')).'%';

                $query->where(function ($inner) use ($term, $descriptor): void {
                    foreach ($descriptor['searchable'] as $column) {
                        $inner->orWhere($column, 'like', $term);
                    }
                });
            })
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->input('status')))
            ->orderBy($descriptor['fields'][1] ?? 'id')
            ->paginate(25)
            ->withQueryString();

        return view('admin.reference.index', [
            'resource' => $resource,
            'descriptor' => $descriptor,
            'rows' => $rows,
            'filters' => $request->only(['search', 'status']),
            'options' => $this->options(),
            'resources' => $this->resourceLabels(),
        ]);
    }

    public function create(string $resource): View
    {
        $this->authorize('create', User::class);
        $descriptor = $this->descriptor($resource);

        return view('admin.reference.create', [
            'resource' => $resource,
            'descriptor' => $descriptor,
            'row' => new $descriptor['model'],
            'options' => $this->options(),
        ]);
    }

    public function store(Request $request, string $resource): RedirectResponse
    {
        $this->authorize('create', User::class);
        $descriptor = $this->descriptor($resource);

        $validated = $request->validate($this->rules($descriptor));

        $row = $descriptor['model']::query()->create($validated);

        return redirect()
            ->route('admin.reference.index', $resource)
            ->with('success', $descriptor['singular'].' created.');
    }

    public function edit(string $resource, int $id): View
    {
        $this->authorize('updateAny', User::class);
        $descriptor = $this->descriptor($resource);

        return view('admin.reference.edit', [
            'resource' => $resource,
            'descriptor' => $descriptor,
            'row' => $descriptor['model']::query()->findOrFail($id),
            'options' => $this->options(),
        ]);
    }

    public function update(Request $request, string $resource, int $id): RedirectResponse
    {
        $this->authorize('updateAny', User::class);
        $descriptor = $this->descriptor($resource);

        $row = $descriptor['model']::query()->findOrFail($id);

        $row->update($request->validate($this->rules($descriptor, $row)));

        return redirect()
            ->route('admin.reference.index', $resource)
            ->with('success', $descriptor['singular'].' updated.');
    }

    public function destroy(string $resource, int $id): RedirectResponse
    {
        $this->authorize('deleteAny', User::class);
        $descriptor = $this->descriptor($resource);

        $row = $descriptor['model']::query()->findOrFail($id);

        // Reference data is referenced from everywhere. A hard delete would either
        // fail on a foreign key or cascade into unrelated records, so removal is
        // a status change: reversible, and invisible to every consumer that
        // filters on `status`.
        $row->update(['status' => 'inactive']);

        return back()->with('success', $descriptor['singular'].' deactivated.');
    }

    /**
     * Validation rules, with a unique check that knows the current row.
     *
     * @param  array<string, mixed>  $descriptor
     * @return array<string, mixed>
     */
    protected function rules(array $descriptor, ?Model $row = null): array
    {
        $rules = $descriptor['rules'];
        $table = (new $descriptor['model'])->getTable();

        // The code and email columns are the natural key for each of these
        // tables, so both are unique. On update the rule must ignore the row being
        // edited, or saving without changing the code would fail against itself.
        foreach ($rules as $field => $fieldRules) {
            if (! str_ends_with((string) $field, 'code') && ! str_ends_with((string) $field, 'email')) {
                continue;
            }

            $rules[$field] = array_merge($fieldRules, [
                Rule::unique($table)->ignore($row?->getKey()),
            ]);
        }

        return $rules;
    }

    /**
     * Resource => label, for the tab strip.
     *
     * @return array<string, string>
     */
    protected function resourceLabels(): array
    {
        $labels = [];

        foreach ($this->descriptors() as $key => $descriptor) {
            $labels[$key] = $descriptor['label'];
        }

        return $labels;
    }

    /**
     * Lookup lists for the foreign-key fields.
     *
     * @return array<string, Collection<int, object>>
     */
    protected function options(): array
    {
        return [
            'department_id' => Department::query()->orderBy('department_name')->get(['id', 'department_name']),
            'location_id' => Location::query()->orderBy('location_name')->get(['id', 'location_name']),
            'head_of_department_id' => Employee::query()->orderBy('employee_name')->get(['id', 'employee_name']),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function descriptor(string $resource): array
    {
        $descriptor = $this->descriptors()[$resource] ?? null;

        if ($descriptor === null) {
            abort(404, 'Unknown reference resource.');
        }

        return $descriptor;
    }
}
