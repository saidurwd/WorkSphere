<?php

namespace App\Models;

use HasinHayder\TyroDashboard\Concerns\HasCrud;
use Illuminate\Database\Eloquent\Model;

class Vendor extends Model
{
    use HasCrud;

    protected $fillable = [
        'vendor_name',
        'contact_person',
        'email',
        'phone',
        'address',
        'website',
        'status',
    ];

    protected $resourceFieldOverrides = [
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
}
