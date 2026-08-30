<?php

namespace App\Http\Controllers;

use HasinHayder\TyroDashboard\Http\Controllers\ResourceController;
use HasinHayder\TyroDashboard\Support\DashboardRoute;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class EstateDivisionController extends ResourceController
{
    protected function getDivisionCodeRule(Request $request, string $resource, ?string $id = null): string
    {
        $estateId = $request->input('estate_id');

        if ($id) {
            return "unique:estate_divisions,division_code,{$id},id,estate_id,{$estateId}";
        }

        return "unique:estate_divisions,division_code,NULL,id,estate_id,{$estateId}";
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

    public function index($resource = 'estate_divisions')
    {
        return parent::index($resource);
    }

    public function create($resource = 'estate_divisions')
    {
        return parent::create($resource);
    }

    public function show($id, $resource = 'estate_divisions')
    {
        return parent::show($resource, $id);
    }

    public function edit($id, $resource = 'estate_divisions')
    {
        return parent::edit($resource, $id);
    }

    public function destroy($id, $resource = 'estate_divisions')
    {
        return parent::destroy($resource, $id);
    }

    public function store(Request $request, $resource = 'estate_divisions')
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

            if ($field === 'division_code') {
                $rules[$field] = $this->getDivisionCodeRule($request, $resource);

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
                return back()->withInput()->with('error', 'A division with this code already exists for the selected estate.');
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
            ->with('success', $config['title'].' created successfully.');
    }

    public function update(Request $request, $id, $resource = 'estate_divisions')
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

            if ($field === 'division_code') {
                $rules[$field] = $this->getDivisionCodeRule($request, $resource, $id);

                continue;
            }

            $fieldRules = $fieldConfig['rules'];
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
                return back()->withInput()->with('error', 'A division with this code already exists for the selected estate.');
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
