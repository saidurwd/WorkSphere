<?php

namespace App\Models;

use HasinHayder\TyroDashboard\Concerns\HasCrud;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssetAssignment extends Model
{
    use HasCrud;

    protected $fillable = [
        'asset_id',
        'employee_id',
        'assigned_date',
        'expected_return_date',
        'returned_date',
        'assigned_by',
        'received_by',
        'assignment_note',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'assigned_date' => 'date',
            'expected_return_date' => 'date',
            'returned_date' => 'date',
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
        'employee_id' => [
            'type' => 'select',
            'label' => 'Employee',
            'relationship' => 'employee',
            'option_label' => 'employee_name',
            'rules' => 'required',
            'searchable' => false,
            'sortable' => false,
        ],
        'assigned_date' => [
            'type' => 'date',
            'label' => 'Assigned Date',
            'rules' => 'nullable|date',
            'searchable' => false,
            'sortable' => true,
        ],
        'expected_return_date' => [
            'type' => 'date',
            'label' => 'Expected Return',
            'rules' => 'nullable|date',
            'searchable' => false,
            'sortable' => false,
            'hide_in_index' => true,
        ],
        'returned_date' => [
            'type' => 'date',
            'label' => 'Returned Date',
            'rules' => 'nullable|date',
            'searchable' => false,
            'sortable' => false,
            'hide_in_index' => true,
        ],
        'assigned_by' => [
            'type' => 'select',
            'label' => 'Assigned By',
            'relationship' => 'assignedBy',
            'option_label' => 'name',
            'rules' => 'nullable',
            'searchable' => false,
            'sortable' => false,
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
        'assignment_note' => [
            'type' => 'textarea',
            'label' => 'Assignment Note',
            'rules' => 'nullable|string',
            'searchable' => false,
            'sortable' => false,
        ],
        'status' => [
            'type' => 'select',
            'label' => 'Status',
            'options' => [
                'Assigned' => 'Assigned',
                'Returned' => 'Returned',
                'Lost' => 'Lost',
                'Transferred' => 'Transferred',
            ],
            'rules' => 'required',
            'searchable' => true,
            'sortable' => true,
        ],
    ];

    protected static function booted(): void
    {
        static::saved(function (AssetAssignment $assignment) {
            if ($assignment->status !== 'Assigned') {
                return;
            }

            $asset = $assignment->asset;

            if ($asset && $asset->current_status !== 'Assigned') {
                $asset->current_status = 'Assigned';
                $asset->saveQuietly();
            }
        });
    }

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    public function receivedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by');
    }
}
