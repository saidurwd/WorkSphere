<?php

namespace App\Models;

use HasinHayder\TyroDashboard\Concerns\HasCrud;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SoftwareInstallation extends Model
{
    use HasCrud;

    protected $fillable = [
        'license_id',
        'asset_id',
        'installed_date',
        'installed_by',
        'status',
    ];

    protected $resourceFieldOverrides = [
        'license_id' => [
            'type' => 'select',
            'label' => 'License',
            'relationship' => 'license',
            'option_label' => 'license_key',
            'rules' => 'required',
            'searchable' => false,
            'sortable' => false,
        ],
        'asset_id' => [
            'type' => 'select',
            'label' => 'Asset',
            'relationship' => 'asset',
            'option_label' => 'asset_name',
            'rules' => 'required',
            'searchable' => false,
            'sortable' => false,
        ],
        'installed_date' => [
            'label' => 'Installed Date',
            'rules' => 'nullable|date',
            'searchable' => false,
            'sortable' => true,
        ],
        'installed_by' => [
            'type' => 'select',
            'label' => 'Installed By',
            'relationship' => 'installedBy',
            'option_label' => 'name',
            'rules' => 'nullable',
            'searchable' => false,
            'sortable' => false,
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
    ];

    protected function casts(): array
    {
        return [
            'installed_date' => 'date',
            'status' => 'string',
        ];
    }

    public function license(): BelongsTo
    {
        return $this->belongsTo(SoftwareLicense::class, 'license_id');
    }

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }

    public function installedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'installed_by');
    }
}
