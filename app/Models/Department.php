<?php

namespace App\Models;

use HasinHayder\TyroDashboard\Concerns\HasCrud;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Department extends Model
{
    use HasCrud;

    protected $fillable = [
        'department_name',
        'department_code',
        'head_of_department_id',
        'status',
    ];

    protected $resourceFieldOverrides = [
        'employees' => [
            'type' => 'text',
            'hide_in_form' => true,
            'hide_in_index' => true,
        ],
        'head_of_department_id' => [
            'type' => 'select',
            'label' => 'Head Of Department',
            'relationship' => 'headOfDepartment',
            'option_label' => 'employee_name',
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
            'status' => 'string',
        ];
    }

    public function headOfDepartment(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'head_of_department_id');
    }

    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class);
    }
}
