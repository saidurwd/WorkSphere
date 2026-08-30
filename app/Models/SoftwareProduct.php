<?php

namespace App\Models;

use HasinHayder\TyroDashboard\Concerns\HasCrud;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SoftwareProduct extends Model
{
    use HasCrud;

    protected $fillable = [
        'software_name',
        'version',
        'vendor',
        'license_type',
        'status',
    ];

    protected $resourceFieldOverrides = [
        'software_name' => [
            'label' => 'Software Name',
            'rules' => 'required|string|max:255',
            'placeholder' => 'e.g. Microsoft Office',
            'searchable' => true,
            'sortable' => true,
        ],
        'version' => [
            'label' => 'Version',
            'rules' => 'nullable|string|max:50',
            'placeholder' => 'e.g. 2024',
            'searchable' => true,
            'sortable' => true,
        ],
        'vendor' => [
            'label' => 'Vendor',
            'rules' => 'nullable|string|max:255',
            'placeholder' => 'e.g. Microsoft',
            'searchable' => true,
            'sortable' => true,
        ],
        'license_type' => [
            'label' => 'License Type',
            'type' => 'select',
            'options' => [
                'perpetual' => 'Perpetual',
                'subscription' => 'Subscription',
                'trial' => 'Trial',
                'open_source' => 'Open Source',
                'freeware' => 'Freeware',
                'volume' => 'Volume',
            ],
            'rules' => 'nullable|string|max:255',
            'searchable' => true,
            'sortable' => true,
        ],
        'status' => [
            'label' => 'Status',
            'type' => 'select',
            'options' => [
                'active' => 'Active',
                'inactive' => 'Inactive',
            ],
            'rules' => 'required|string|in:active,inactive',
            'searchable' => true,
            'sortable' => true,
        ],
        'licenses' => [
            'type' => 'text',
            'hide_in_form' => true,
            'hide_in_single_view' => true,
        ],
    ];

    protected function casts(): array
    {
        return [
            'status' => 'string',
        ];
    }

    public function licenses(): HasMany
    {
        return $this->hasMany(SoftwareLicense::class);
    }
}
