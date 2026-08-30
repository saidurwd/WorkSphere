<?php

namespace App\Models;

use HasinHayder\TyroDashboard\Concerns\HasCrud;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssetDisposal extends Model
{
    use HasCrud;

    protected $fillable = [
        'asset_id',
        'disposal_date',
        'book_value',
        'sale_value',
        'disposal_reason',
        'approved_by',
        'remarks',
    ];

    protected $resourceFieldOverrides = [
        'asset_id' => [
            'type' => 'select',
            'label' => 'Asset',
            'relationship' => 'asset',
            'option_label' => 'asset_name',
            'rules' => 'required',
            'searchable' => false,
            'sortable' => false,
        ],
        'disposal_date' => [
            'label' => 'Disposal Date',
            'rules' => 'nullable|date',
            'searchable' => false,
            'sortable' => true,
        ],
        'book_value' => [
            'label' => 'Book Value',
            'rules' => 'nullable|numeric|min:0',
            'searchable' => false,
            'sortable' => true,
        ],
        'sale_value' => [
            'label' => 'Sale Value',
            'rules' => 'nullable|numeric|min:0',
            'searchable' => false,
            'sortable' => true,
        ],
        'disposal_reason' => [
            'type' => 'select',
            'label' => 'Disposal Reason',
            'options' => [
                'Obsolete' => 'Obsolete',
                'Damaged' => 'Damaged',
                'Lost' => 'Lost',
                'Sold' => 'Sold',
                'Scrapped' => 'Scrapped',
            ],
            'rules' => 'nullable|in:Obsolete,Damaged,Lost,Sold,Scrapped',
            'searchable' => true,
            'sortable' => true,
        ],
        'approved_by' => [
            'type' => 'select',
            'label' => 'Approved By',
            'relationship' => 'approvedBy',
            'option_label' => 'name',
            'rules' => 'nullable',
            'searchable' => false,
            'sortable' => false,
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
            'disposal_date' => 'date',
            'book_value' => 'decimal:2',
            'sale_value' => 'decimal:2',
            'disposal_reason' => 'string',
        ];
    }

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
