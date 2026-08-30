<?php

namespace App\Models;

use HasinHayder\TyroDashboard\Concerns\HasCrud;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
        'assets' => [
            'type' => 'text',
            'hide_in_form' => true,
            'hide_in_index' => true,
            'hide_in_single_view' => true,
        ],
        'purchaseOrders' => [
            'type' => 'text',
            'hide_in_form' => true,
            'hide_in_index' => true,
            'hide_in_single_view' => true,
        ],
    ];

    protected function casts(): array
    {
        return [
            'status' => 'string',
        ];
    }

    public function assets(): HasMany
    {
        return $this->hasMany(Asset::class);
    }

    public function purchaseOrders(): HasMany
    {
        return $this->hasMany(PurchaseOrder::class);
    }
}
