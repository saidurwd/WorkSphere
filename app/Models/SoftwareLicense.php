<?php

namespace App\Models;

use HasinHayder\TyroDashboard\Concerns\HasCrud;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SoftwareLicense extends Model
{
    use HasCrud;

    protected $fillable = [
        'software_product_id',
        'license_key',
        'purchase_date',
        'expiry_date',
        'quantity',
        'cost',
        'vendor_id',
        'status',
    ];

    protected $resourceFieldOverrides = [
        'software_product_id' => [
            'type' => 'select',
            'label' => 'Software Product',
            'relationship' => 'softwareProduct',
            'option_label' => 'software_name',
            'rules' => 'required',
            'searchable' => false,
            'sortable' => false,
        ],
        'license_key' => [
            'label' => 'License Key',
            'rules' => 'nullable|string|max:255',
            'searchable' => true,
            'sortable' => true,
        ],
        'purchase_date' => [
            'label' => 'Purchase Date',
            'rules' => 'nullable|date',
            'searchable' => false,
            'sortable' => true,
        ],
        'expiry_date' => [
            'label' => 'Expiry Date',
            'rules' => 'nullable|date',
            'searchable' => false,
            'sortable' => true,
        ],
        'quantity' => [
            'label' => 'Quantity',
            'rules' => 'nullable|integer|min:0',
            'searchable' => false,
            'sortable' => true,
        ],
        'cost' => [
            'label' => 'Cost',
            'rules' => 'nullable|numeric|min:0',
            'searchable' => false,
            'sortable' => true,
        ],
        'vendor_id' => [
            'type' => 'select',
            'label' => 'Vendor',
            'relationship' => 'vendor',
            'option_label' => 'vendor_name',
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
        'installations' => [
            'type' => 'text',
            'hide_in_form' => true,
            'hide_in_single_view' => true,
        ],
    ];

    protected function casts(): array
    {
        return [
            'purchase_date' => 'date',
            'expiry_date' => 'date',
            'quantity' => 'integer',
            'cost' => 'decimal:2',
            'status' => 'string',
        ];
    }

    public function softwareProduct(): BelongsTo
    {
        return $this->belongsTo(SoftwareProduct::class);
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function installations(): HasMany
    {
        return $this->hasMany(SoftwareInstallation::class, 'license_id');
    }
}
