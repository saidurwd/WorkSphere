<?php

namespace App\Models;

use HasinHayder\TyroDashboard\Concerns\HasCrud;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssetAuditDetail extends Model
{
    use HasCrud;

    protected $fillable = [
        'audit_id',
        'asset_id',
        'physical_status',
        'remarks',
    ];

    protected $resourceFieldOverrides = [
        'audit_id' => [
            'type' => 'select',
            'label' => 'Audit',
            'relationship' => 'audit',
            'option_label' => 'label',
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
        'physical_status' => [
            'type' => 'select',
            'label' => 'Physical Status',
            'options' => [
                'Found' => 'Found',
                'Missing' => 'Missing',
                'Damaged' => 'Damaged',
                'Replaced' => 'Replaced',
            ],
            'rules' => 'required|in:Found,Missing,Damaged,Replaced',
            'searchable' => true,
            'sortable' => true,
        ],
        'remarks' => [
            'type' => 'textarea',
            'label' => 'Remarks',
            'rules' => 'nullable|string',
            'hide_in_index' => true,
        ],
    ];

    protected function casts(): array
    {
        return [
            'physical_status' => 'string',
        ];
    }

    public function audit(): BelongsTo
    {
        return $this->belongsTo(AssetAudit::class, 'audit_id');
    }

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }
}
