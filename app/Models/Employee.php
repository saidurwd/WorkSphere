<?php

namespace App\Models;

use HasinHayder\TyroDashboard\Concerns\HasCrud;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Employee extends Model
{
    use HasCrud;

    protected $fillable = [
        'employee_code',
        'employee_name',
        'email',
        'phone',
        'designation',
        'department_id',
        'location_id',
        'joining_date',
        'status',
    ];

    protected $resourceFieldOverrides = [
        'phone' => [
            'hide_in_index' => true,
        ],
        'designation' => [
            'hide_in_index' => true,
        ],
        'department_id' => [
            'type' => 'select',
            'label' => 'Department',
            'relationship' => 'department',
            'option_label' => 'department_name',
            'hide_in_index' => true,
        ],
        'joining_date' => [
            'hide_in_index' => true,
        ],
        'location_id' => [
            'type' => 'select',
            'label' => 'Location',
            'relationship' => 'location',
            'option_label' => 'location_name',
        ],
        'status' => [
            'type' => 'select',
            'label' => 'Status',
            'options' => [
                'active' => 'Active',
                'inactive' => 'Inactive',
            ],
        ],
    ];

    protected function casts(): array
    {
        return [
            'joining_date' => 'date',
            'status' => 'string',
        ];
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }
}
