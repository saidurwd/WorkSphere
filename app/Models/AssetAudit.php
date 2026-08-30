<?php

namespace App\Models;

use HasinHayder\TyroDashboard\Concerns\HasCrud;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AssetAudit extends Model
{
    use HasCrud;

    protected $fillable = [
        'audit_date',
        'location_id',
        'auditor_name',
        'remarks',
    ];

    protected $resourceFieldOverrides = [
        'audit_date' => [
            'label' => 'Audit Date',
            'rules' => 'nullable|date',
            'searchable' => false,
            'sortable' => true,
        ],
        'location_id' => [
            'type' => 'select',
            'label' => 'Location',
            'relationship' => 'location',
            'option_label' => 'location_name',
            'rules' => 'required',
            'searchable' => false,
            'sortable' => false,
        ],
        'auditor_name' => [
            'type' => 'select',
            'label' => 'Auditor',
            'relationship' => 'auditor',
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
        'details' => [
            'type' => 'text',
            'hide_in_form' => true,
            'hide_in_index' => true,
            'hide_in_single_view' => true,
        ],
    ];

    protected function casts(): array
    {
        return [
            'audit_date' => 'date',
        ];
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function auditor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'auditor_name');
    }

    public function details(): HasMany
    {
        return $this->hasMany(AssetAuditDetail::class, 'audit_id');
    }

    public function getLabelAttribute(): string
    {
        $location = $this->location?->location_name;
        $date = $this->audit_date?->format('Y-m-d');

        return 'Audit #'.$this->id
            .($location ? ' · '.$location : '')
            .($date ? ' · '.$date : '');
    }
}
