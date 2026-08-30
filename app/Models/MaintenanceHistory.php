<?php

namespace App\Models;

use HasinHayder\TyroDashboard\Concerns\HasCrud;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MaintenanceHistory extends Model
{
    use HasCrud;

    protected $table = 'maintenance_history';

    public $resourceKey = 'maintenance_history';

    protected $fillable = [
        'maintenance_request_id',
        'vendor_id',
        'repair_date',
        'repair_cost',
        'resolution',
        'downtime_hours',
        'completed_by',
        'checked_by',
        'verified_by',
    ];

    protected $resourceFieldOverrides = [
        'maintenance_request_id' => [
            'type' => 'select',
            'label' => 'Maintenance Request',
            'relationship' => 'maintenanceRequest',
            'option_label' => 'request_label',
            'rules' => 'required',
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
        ],
        'repair_date' => [
            'label' => 'Repair Date',
            'rules' => 'nullable|date',
            'searchable' => false,
            'sortable' => true,
        ],
        'repair_cost' => [
            'label' => 'Repair Cost',
            'rules' => 'nullable|numeric|min:0',
            'searchable' => false,
            'sortable' => true,
            'hide_in_index' => true,
        ],
        'resolution' => [
            'type' => 'textarea',
            'label' => 'Resolution',
            'rules' => 'nullable|string',
            'hide_in_index' => true,
        ],
        'downtime_hours' => [
            'type' => 'number',
            'label' => 'Downtime (Hours)',
            'rules' => 'nullable|numeric|min:0',
            'searchable' => false,
            'sortable' => true,
            'hide_in_index' => true,
        ],
        'completed_by' => [
            'type' => 'select',
            'label' => 'Completed By',
            'relationship' => 'completedBy',
            'option_label' => 'name',
            'rules' => 'nullable',
            'searchable' => false,
            'sortable' => false,
        ],
        'checked_by' => [
            'type' => 'select',
            'label' => 'Checked By',
            'relationship' => 'checkedBy',
            'option_label' => 'name',
            'rules' => 'nullable',
            'searchable' => false,
            'sortable' => false,
            'hide_in_index' => true,
        ],
        'verified_by' => [
            'type' => 'select',
            'label' => 'Verified By',
            'relationship' => 'verifiedBy',
            'option_label' => 'name',
            'rules' => 'nullable',
            'searchable' => false,
            'sortable' => false,
            'hide_in_index' => true,
        ],
    ];

    protected function casts(): array
    {
        return [
            'repair_date' => 'date',
            'repair_cost' => 'decimal:2',
            'downtime_hours' => 'decimal:2',
        ];
    }

    protected static function booted(): void
    {
        static::saved(function (MaintenanceHistory $history) {
            if (blank($history->completed_by)) {
                return;
            }

            $request = $history->maintenanceRequest;

            if ($request && $request->status !== 'resolved') {
                $request->status = 'resolved';
                $request->saveQuietly();
            }
        });
    }

    public function maintenanceRequest(): BelongsTo
    {
        return $this->belongsTo(MaintenanceRequest::class);
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function completedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'completed_by');
    }

    public function checkedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'checked_by');
    }

    public function verifiedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }
}
