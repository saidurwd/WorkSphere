<?php

namespace App\Models;

use HasinHayder\TyroDashboard\Concerns\HasCrud;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Permission extends Model
{
    use HasCrud;

    protected $fillable = [
        'permission_name',
    ];

    protected $resourceFieldOverrides = [
        'permission_name' => [
            'label' => 'Permission Name',
            'rules' => 'required|string|max:255',
            'placeholder' => 'e.g. view-assets',
            'searchable' => true,
            'sortable' => true,
        ],
        'rolePermissions' => [
            'type' => 'text',
            'hide_in_form' => true,
            'hide_in_single_view' => true,
        ],
    ];

    public function rolePermissions(): HasMany
    {
        return $this->hasMany(RolePermission::class);
    }
}
