<?php

namespace App\Http\Controllers;

use App\Models\Estate;
use App\Models\EstateDivision;
use App\Models\EstateStaff;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use HasinHayder\TyroDashboard\Http\Controllers\ResourceController;
use HasinHayder\TyroDashboard\Support\DashboardRoute;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class EstateStaffController extends ResourceController
{
    public function index($resource = 'estate_staff')
    {
        $request = request();

        $query = EstateStaff::query()->with(['estate', 'division']);

        $filters = [
            'search' => $request->string('search')->trim()->toString(),
            'estate_id' => $request->string('estate_id')->toString(),
            'division_id' => $request->string('division_id')->toString(),
        ];

        $query->when($filters['search'] !== '', function ($q) use ($filters) {
            $search = "%{$filters['search']}%";
            $q->where(function ($q) use ($search) {
                $q->where('staff_name', 'like', $search)
                    ->orWhere('pf_number', 'like', $search)
                    ->orWhere('quarter_number', 'like', $search)
                    ->orWhere('token_number', 'like', $search);
            });
        })->when($filters['estate_id'] !== '', function ($q) use ($filters) {
            $q->where('estate_id', $filters['estate_id']);
        })->when($filters['division_id'] !== '', function ($q) use ($filters) {
            $q->where('division_id', $filters['division_id']);
        });

        $estateStaffs = $query->paginate(15)->withQueryString();
        $estates = Estate::orderBy('estate_name_eng')->get(['id', 'estate_name_eng']);
        $divisions = EstateDivision::orderBy('estate_id')->orderBy('division_name_eng')->get(['id', 'estate_id', 'division_name_eng']);

        $estateDivisions = [];
        foreach ($divisions as $division) {
            $estateDivisions[$division->estate_id][] = $division;
        }

        return view('estate-staff.index', [
            'estateStaffs' => $estateStaffs,
            'filters' => $filters,
            'estates' => $estates,
            'divisions' => $divisions,
            'estateDivisions' => $estateDivisions,
        ]);
    }

    public function create($resource = 'estate_staff')
    {
        return parent::create($resource);
    }

    public function show($id, $resource = 'estate_staff')
    {
        return parent::show($resource, $id);
    }

    public function edit($id, $resource = 'estate_staff')
    {
        return parent::edit($resource, $id);
    }

    public function destroy($id, $resource = 'estate_staff')
    {
        return parent::destroy($resource, $id);
    }

    public function divisions(Request $request)
    {
        $estateId = $request->query('estate_id');

        $divisions = $estateId
            ? EstateDivision::where('estate_id', $estateId)->orderBy('division_name_eng')->get(['id', 'estate_id', 'division_name_eng'])
            : collect();

        return response()->json($divisions);
    }

    public function print(EstateStaff $estateStaff): View
    {
        $estateStaff->load(['estate', 'division', 'residenceType']);

        $qrText = implode("\n", array_filter([
            'PF NO: '.($estateStaff->pf_number ?? ''),
            $estateStaff->estate?->estate_name_eng ?? '',
            'Division: '.($estateStaff->division?->division_name_eng ?? ''),
            'Staff Quarter No: '.($estateStaff->quarter_code ?? ''),
        ]));

        $renderer = new ImageRenderer(new RendererStyle(512, 4), new SvgImageBackEnd);
        $writer = new Writer($renderer);
        $qrSvg = $writer->writeString($qrText);

        return view('estate-staff.print', [
            'staff' => $estateStaff,
            'qrSvg' => $qrSvg,
        ]);
    }

    protected function processUniqueRule(string $rule, string $field, string $id): string
    {
        if (is_string($rule) && Str::startsWith($rule, 'unique:')) {
            $parts = explode(',', substr($rule, 7));

            if (count($parts) == 1) {
                return "unique:{$parts[0]},{$field},{$id}";
            }

            if (count($parts) == 2) {
                return $rule.",{$id}";
            }
        }

        return $rule;
    }

    public function store(Request $request, $resource = 'estate_staff')
    {
        $config = $this->getResourceConfig($resource);

        if (! $this->hasAccess($config)) {
            abort(403, 'You do not have permission to view this resource.');
        }

        if ($this->isReadonly($config)) {
            abort(403, 'This resource is read-only for your role.');
        }

        $modelClass = $config['model'];

        $rules = [];
        foreach ($config['fields'] as $field => $fieldConfig) {
            if (! isset($fieldConfig['rules'])) {
                continue;
            }

            if ($field === 'pf_number') {
                $estateId = $request->input('estate_id');
                $divisionId = $request->input('division_id');
                $residenceTypeId = $request->input('estate_residence_type_id');
                $staffName = $request->input('staff_name');

                $uniqueRule = "unique:estate_staff,pf_number,NULL,id,estate_id,{$estateId},division_id,{$divisionId},estate_residence_type_id,{$residenceTypeId},staff_name,{$staffName}";
                $rules[$field] = $fieldConfig['rules'].'|'.$uniqueRule;

                continue;
            }

            $rules[$field] = $fieldConfig['rules'];
        }

        $validated = $request->validate($rules);

        $data = $request->only(array_keys($config['fields']));
        $data = array_merge($data, $validated);

        foreach ($config['fields'] as $field => $fieldConfig) {
            if ($fieldConfig['type'] === 'boolean' && ! isset($data[$field])) {
                $data[$field] = false;
            }
        }

        foreach ($config['fields'] as $field => $fieldConfig) {
            if ($fieldConfig['type'] === 'file' && $request->hasFile($field)) {
                $uploadDisk = $config['upload_disk'] ?? config('tyro-dashboard.uploads.disk', 'public');
                $uploadDirectory = $config['upload_directory'] ?? config('tyro-dashboard.uploads.directory', 'uploads');
                $path = $request->file($field)->store($uploadDirectory, $uploadDisk);
                $data[$field] = $path;
            }
        }

        $relationshipsToSync = [];
        foreach ($config['fields'] as $field => $fieldConfig) {
            $isMultipleRelationship = (
                $fieldConfig['type'] === 'multiselect' ||
                ($fieldConfig['type'] === 'checkbox' && isset($fieldConfig['relationship'])) ||
                ($fieldConfig['type'] === 'select' && ($fieldConfig['multiple'] ?? false))
            ) && isset($fieldConfig['relationship']);

            if ($isMultipleRelationship) {
                if (isset($data[$field])) {
                    $relationshipsToSync[$field] = $data[$field];
                }
                unset($data[$field]);
            }
        }

        try {
            $item = $modelClass::create($data);
        } catch (QueryException $e) {
            $errorCode = $e->errorInfo[1] ?? 0;
            $errorMessage = $e->getMessage();
            $field = null;

            if ($errorCode == 1048 && preg_match("/Column '([^']+)' cannot be null/", $errorMessage, $matches)) {
                $field = $matches[1];
            } elseif ($errorCode == 1364 && preg_match("/Field '([^']+)' doesn't have a default value/", $errorMessage, $matches)) {
                $field = $matches[1];
            } elseif (strpos($errorMessage, 'NOT NULL constraint failed') !== false) {
                if (preg_match("/NOT NULL constraint failed: .+\.([^\s]+)/", $errorMessage, $matches)) {
                    $field = $matches[1];
                }
            } elseif (strpos($errorMessage, 'violates not-null constraint') !== false) {
                if (preg_match('/null value in column "([^"]+)"/', $errorMessage, $matches)) {
                    $field = $matches[1];
                }
            }

            if ($field) {
                return back()->withInput()->withErrors([$field => "The {$field} field is required."]);
            }

            if ($errorCode == 1062 || strpos($errorMessage, 'constraint') !== false) {
                return back()->withInput()->with('error', 'A record with this Estate, Division, Residence Type, Staff Name, and PF Number combination already exists.');
            }

            throw $e;
        }

        foreach ($relationshipsToSync as $field => $values) {
            $fieldConfig = $config['fields'][$field];
            if (method_exists($item, $fieldConfig['relationship'])) {
                $item->{$fieldConfig['relationship']}()->sync($values);
            }
        }

        return redirect()->route(DashboardRoute::name('resources.create'), $resource)
            ->withInput($request->except(['_token', '_method']))
            ->with('success', $config['title'].' created successfully.');
    }

    public function update(Request $request, $id, $resource = 'estate_staff')
    {
        $config = $this->getResourceConfig($resource);

        if (! $this->hasAccess($config)) {
            abort(403, 'You do not have permission to view this resource.');
        }

        if ($this->isReadonly($config)) {
            abort(403, 'This resource is read-only for your role.');
        }

        $modelClass = $config['model'];

        $item = $modelClass::findOrFail($id);

        $rules = [];
        foreach ($config['fields'] as $field => $fieldConfig) {
            if (! isset($fieldConfig['rules'])) {
                continue;
            }

            $fieldRules = $fieldConfig['rules'];

            if ($field === 'pf_number') {
                $estateId = $request->input('estate_id');
                $divisionId = $request->input('division_id');
                $residenceTypeId = $request->input('estate_residence_type_id');
                $staffName = $request->input('staff_name');

                $uniqueRule = "unique:estate_staff,pf_number,{$id},id,estate_id,{$estateId},division_id,{$divisionId},estate_residence_type_id,{$residenceTypeId},staff_name,{$staffName}";
                $rules[$field] = $uniqueRule;

                continue;
            }

            $rules[$field] = $this->processUniqueRule($fieldRules, $field, $id);
        }

        $validated = $request->validate($rules);

        $data = $request->only(array_keys($config['fields']));
        $data = array_merge($data, $validated);

        foreach ($config['fields'] as $field => $fieldConfig) {
            if ($fieldConfig['type'] === 'boolean' && ! isset($data[$field])) {
                $data[$field] = false;
            }
            if ($fieldConfig['type'] === 'password' && empty($data[$field])) {
                unset($data[$field]);
            }
        }

        $uploadDisk = $config['upload_disk'] ?? config('tyro-dashboard.uploads.disk', 'public');
        $uploadDirectory = $config['upload_directory'] ?? config('tyro-dashboard.uploads.directory', 'uploads');

        foreach ($config['fields'] as $field => $fieldConfig) {
            if ($fieldConfig['type'] === 'file') {
                if ($request->hasFile($field)) {
                    if (! empty($item->$field)) {
                        Storage::disk($uploadDisk)->delete($item->$field);
                    }
                    $path = $request->file($field)->store($uploadDirectory, $uploadDisk);
                    $data[$field] = $path;
                } else {
                    unset($data[$field]);
                }
            }
        }

        $relationshipsToSync = [];
        foreach ($config['fields'] as $field => $fieldConfig) {
            $isMultipleRelationship = (
                $fieldConfig['type'] === 'multiselect' ||
                ($fieldConfig['type'] === 'checkbox' && isset($fieldConfig['relationship'])) ||
                ($fieldConfig['type'] === 'select' && ($fieldConfig['multiple'] ?? false))
            ) && isset($fieldConfig['relationship']);

            if ($isMultipleRelationship) {
                if (isset($data[$field])) {
                    $relationshipsToSync[$field] = $data[$field];
                } else {
                    $relationshipsToSync[$field] = [];
                }
                unset($data[$field]);
            }
        }

        try {
            $item->update($data);
        } catch (QueryException $e) {
            $errorCode = $e->errorInfo[1] ?? 0;
            $errorMessage = $e->getMessage();
            $field = null;

            if ($errorCode == 1048 && preg_match("/Column '([^']+)' cannot be null/", $errorMessage, $matches)) {
                $field = $matches[1];
            } elseif ($errorCode == 1364 && preg_match("/Field '([^']+)' doesn't have a default value/", $errorMessage, $matches)) {
                $field = $matches[1];
            } elseif (strpos($errorMessage, 'NOT NULL constraint failed') !== false) {
                if (preg_match("/NOT NULL constraint failed: .+\.([^\s]+)/", $errorMessage, $matches)) {
                    $field = $matches[1];
                }
            } elseif (strpos($errorMessage, 'violates not-null constraint') !== false) {
                if (preg_match('/null value in column "([^"]+)"/', $errorMessage, $matches)) {
                    $field = $matches[1];
                }
            }

            if ($field) {
                return back()->withInput()->withErrors([$field => "The {$field} field is required."]);
            }

            if ($errorCode == 1062 || strpos($errorMessage, 'constraint') !== false) {
                return back()->withInput()->with('error', 'A record with this Estate, Division, Residence Type, Staff Name, and PF Number combination already exists.');
            }

            throw $e;
        }

        foreach ($relationshipsToSync as $field => $values) {
            $fieldConfig = $config['fields'][$field];
            if (method_exists($item, $fieldConfig['relationship'])) {
                $item->{$fieldConfig['relationship']}()->sync($values);
            }
        }

        return redirect()->route(DashboardRoute::name('resources.index'), $resource)
            ->with('success', $config['title'].' updated successfully.');
    }
}
