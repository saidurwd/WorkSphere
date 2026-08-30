<?php

namespace App\Models;

use HasinHayder\TyroDashboard\Concerns\HasCrud;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Location extends Model
{
    use HasCrud;

    protected $fillable = [
        'location_name',
        'location_code',
        'address',
        'city',
        'country',
        'status',
    ];

    protected $resourceFieldOverrides = [
        'employees' => [
            'type' => 'text',
            'hide_in_form' => true,
            'hide_in_index' => true,
        ],
        'assets' => [
            'type' => 'text',
            'hide_in_form' => true,
            'hide_in_index' => true,
        ],
        'country' => [
            'type' => 'text',
            'label' => 'Country',
            'rules' => 'nullable|string|max:255',
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

    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class);
    }

    public function assets(): HasMany
    {
        return $this->hasMany(Asset::class);
    }
}
