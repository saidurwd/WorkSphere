<?php

namespace App\Models;

use HasinHayder\TyroDashboard\Concerns\HasCrud;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Asset extends Model
{
    use HasCrud;

    protected $fillable = [
        'asset_tag',
        'asset_name',
        'category_id',
        'sub_category_id',
        'brand',
        'model',
        'serial_number',
        'service_tag',
        'purchase_date',
        'purchase_cost',
        'vendor_id',
        'warranty_start',
        'warranty_end',
        'location_id',
        'current_status',
        'condition_status',
        'depreciation_years',
        'remarks',
    ];

    protected function casts(): array
    {
        return [
            'purchase_date' => 'date',
            'purchase_cost' => 'decimal:2',
            'warranty_start' => 'date',
            'warranty_end' => 'date',
            'depreciation_years' => 'integer',
            'current_status' => 'string',
            'condition_status' => 'string',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Asset $asset) {
            if (filled($asset->asset_tag)) {
                return;
            }

            $locationCode = 'LOC';
            $categoryCode = 'CAT';

            if ($asset->location_id) {
                $locationCode = (string) Location::whereKey($asset->location_id)->value('location_code');
            }

            if ($asset->category_id) {
                $categoryCode = (string) AssetCategory::whereKey($asset->category_id)->value('category_code');
            }

            $locationCode = $locationCode ?: 'LOC';
            $categoryCode = $categoryCode ?: 'CAT';
            $prefix = strtoupper($locationCode.$categoryCode);

            $lastAssetTag = static::where('asset_tag', 'like', $prefix.'%')
                ->orderByDesc('asset_tag')
                ->first()?->asset_tag;

            $seq = 1;
            if ($lastAssetTag !== null) {
                $numPart = substr($lastAssetTag, strlen($prefix));
                if (ctype_digit((string) $numPart)) {
                    $seq = ((int) $numPart) + 1;
                } else {
                    $seq = static::where('asset_tag', 'like', $prefix.'%')->count() + 1;
                }
            }

            $asset->asset_tag = $prefix.str_pad((string) $seq, 6, '0', STR_PAD_LEFT);
        });
    }

    public $resourceFieldOverrides = [
        'asset_tag' => [
            'rules' => 'nullable|string|max:255',
            'searchable' => true,
            'sortable' => true,
            'hide_in_form' => true,
            'hide_in_create' => true,
            'hide_in_edit' => true,
        ],
        'asset_name' => [
            'rules' => 'required|string|max:255',
            'searchable' => true,
            'sortable' => true,
        ],
        'category_id' => [
            'type' => 'select',
            'label' => 'Category',
            'relationship' => 'category',
            'option_label' => 'category_name',
            'rules' => 'required',
            'searchable' => false,
            'sortable' => false,
        ],
        'sub_category_id' => [
            'type' => 'select',
            'label' => 'Sub Category',
            'relationship' => 'subCategory',
            'option_label' => 'sub_category_name',
            'rules' => 'nullable',
            'help_text' => 'Select a category first.',
            'searchable' => false,
            'sortable' => false,
        ],
        'vendor_id' => [
            'type' => 'select',
            'label' => 'Vendor',
            'relationship' => 'vendor',
            'option_label' => 'vendor_name',
            'rules' => 'nullable',
            'searchable' => false,
            'sortable' => false,
            'hide_in_index' => true,
        ],
        'location_id' => [
            'type' => 'select',
            'label' => 'Location',
            'relationship' => 'location',
            'option_label' => 'location_name',
            'rules' => 'nullable',
            'searchable' => false,
            'sortable' => false,
        ],
        'current_status' => [
            'type' => 'select',
            'label' => 'Current Status',
            'options' => [
                'In Stock' => 'In Stock',
                'Assigned' => 'Assigned',
                'Spare' => 'Spare',
                'Under Repair' => 'Under Repair',
                'Returned' => 'Returned',
                'Lost' => 'Lost',
                'Stolen' => 'Stolen',
                'Damaged' => 'Damaged',
                'Disposed' => 'Disposed',
                'Scrapped' => 'Scrapped',
                'Donated' => 'Donated',
                'Awaiting Disposal' => 'Awaiting Disposal',
            ],
            'rules' => 'required',
            'searchable' => true,
            'sortable' => true,
        ],
        'condition_status' => [
            'type' => 'select',
            'label' => 'Condition Status',
            'options' => [
                'New' => 'New',
                'Excellent' => 'Excellent',
                'Good' => 'Good',
                'Fair' => 'Fair',
                'Poor' => 'Poor',
                'Faulty' => 'Faulty',
                'Under Repair' => 'Under Repair',
                'Repaired' => 'Repaired',
                'Damaged' => 'Damaged',
                'Obsolete' => 'Obsolete',
                'End of Life (EOL)' => 'End of Life (EOL)',
                'Beyond Economic Repair (BER)' => 'Beyond Economic Repair (BER)',
                'Scrapped' => 'Scrapped',
                'Disposed' => 'Disposed',
                'Lost' => 'Lost',
                'Stolen' => 'Stolen',
                'Retired' => 'Retired',
            ],
            'rules' => 'required',
            'searchable' => true,
            'sortable' => true,
            'hide_in_index' => true,
        ],
        'serial_number' => [
            'type' => 'text',
            'rules' => 'nullable|string|max:255',
            'searchable' => true,
            'sortable' => true,
        ],
        'service_tag' => [
            'rules' => 'nullable|string|max:255',
            'searchable' => false,
            'sortable' => false,
            'hide_in_index' => true,
        ],
        'brand' => [
            'rules' => 'nullable|string|max:255',
            'searchable' => true,
            'sortable' => true,
            'hide_in_index' => true,
        ],
        'model' => [
            'rules' => 'nullable|string|max:255',
            'searchable' => true,
            'sortable' => true,
            'hide_in_index' => true,
        ],
        'purchase_date' => [
            'type' => 'date',
            'rules' => 'nullable|date',
            'searchable' => false,
            'sortable' => true,
            'hide_in_index' => true,
        ],
        'purchase_cost' => [
            'type' => 'number',
            'rules' => 'nullable|numeric|min:0',
            'searchable' => false,
            'sortable' => true,
            'hide_in_index' => true,
        ],
        'vendor_id' => [
            'type' => 'select',
            'label' => 'Vendor',
            'relationship' => 'vendor',
            'option_label' => 'vendor_name',
            'rules' => 'nullable',
            'searchable' => false,
            'sortable' => false,
            'hide_in_index' => true,
        ],
        'warranty_start' => [
            'type' => 'date',
            'rules' => 'nullable|date',
            'searchable' => false,
            'sortable' => true,
            'hide_in_index' => true,
        ],
        'warranty_end' => [
            'type' => 'date',
            'rules' => 'nullable|date',
            'searchable' => false,
            'sortable' => true,
            'hide_in_index' => true,
        ],
        'depreciation_years' => [
            'type' => 'number',
            'rules' => 'nullable|integer|min:0',
            'searchable' => false,
            'sortable' => true,
            'hide_in_index' => true,
        ],
        'remarks' => [
            'type' => 'textarea',
            'rules' => 'nullable|string',
            'searchable' => false,
            'sortable' => false,
        ],
        'assignments' => [
            'multiple' => false,
            'hide_in_form' => true,
            'hide_in_index' => true,
        ],
        'transfers' => [
            'multiple' => false,
            'hide_in_form' => true,
            'hide_in_index' => true,
        ],
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(AssetCategory::class, 'category_id');
    }

    public function subCategory(): BelongsTo
    {
        return $this->belongsTo(AssetSubCategory::class, 'sub_category_id');
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class, 'vendor_id');
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'location_id');
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(AssetAssignment::class, 'asset_id');
    }

    public function transfers(): HasMany
    {
        return $this->hasMany(AssetTransfer::class, 'asset_id');
    }

    public function getNameWithSerialAttribute(): string
    {
        $name = $this->asset_name ?? '';
        $serial = $this->serial_number ?? '';

        return $serial !== '' ? $name.' - '.$serial : $name;
    }
}
