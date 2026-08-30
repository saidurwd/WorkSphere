<?php

namespace App\Models;

use HasinHayder\TyroDashboard\Concerns\HasCrud;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssetTransfer extends Model
{
    use HasCrud;

    protected $fillable = [
        'asset_id',
        'from_location_id',
        'to_location_id',
        'transfer_date',
        'approved_by',
        'received_by',
        'transfer_reason',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'transfer_date' => 'date',
            'status' => 'string',
        ];
    }

    public $resourceFieldOverrides = [
        'asset_id' => [
            'type' => 'select',
            'label' => 'Asset',
            'relationship' => 'asset',
            'option_label' => 'name_with_serial',
            'rules' => 'required',
            'searchable' => false,
            'sortable' => false,
        ],
        'from_location_id' => [
            'type' => 'select',
            'label' => 'From Location',
            'relationship' => 'fromLocation',
            'option_label' => 'location_name',
            'rules' => 'required',
            'searchable' => false,
            'sortable' => false,
        ],
        'to_location_id' => [
            'type' => 'select',
            'label' => 'To Location',
            'relationship' => 'toLocation',
            'option_label' => 'location_name',
            'rules' => 'required',
            'searchable' => false,
            'sortable' => false,
        ],
        'transfer_date' => [
            'type' => 'date',
            'label' => 'Transfer Date',
            'rules' => 'nullable|date',
            'searchable' => false,
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
            'hide_in_index' => true,
        ],
        'received_by' => [
            'type' => 'select',
            'label' => 'Received By',
            'relationship' => 'receivedBy',
            'option_label' => 'name',
            'rules' => 'nullable',
            'searchable' => false,
            'sortable' => false,
            'hide_in_index' => true,
        ],
        'transfer_reason' => [
            'type' => 'textarea',
            'label' => 'Transfer Reason',
            'rules' => 'nullable|string',
            'searchable' => false,
            'sortable' => false,
        ],
        'status' => [
            'type' => 'select',
            'label' => 'Status',
            'options' => [
                'pending' => 'Pending',
                'approved' => 'Approved',
                'completed' => 'Completed',
                'rejected' => 'Rejected',
                'cancelled' => 'Cancelled',
            ],
            'rules' => 'required',
            'searchable' => true,
            'sortable' => true,
        ],
    ];

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }

    public function fromLocation(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'from_location_id');
    }

    public function toLocation(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'to_location_id');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function receivedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by');
    }
}
