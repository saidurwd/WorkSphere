<?php

namespace App\Models;

use HasinHayder\TyroDashboard\Concerns\HasCrud;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RolePermission extends Model
{
    use HasCrud;

    protected $fillable = [
        'role_id',
        'permission_id',
    ];

    protected $resourceFieldOverrides = [
        'role_id' => [
            'type' => 'select',
            'label' => 'Role',
            'relationship' => 'role',
            'option_label' => 'name',
            'rules' => 'required',
            'searchable' => false,
            'sortable' => false,
        ],
        'permission_id' => [
            'type' => 'select',
            'label' => 'Permission',
            'relationship' => 'permission',
            'option_label' => 'permission_name',
            'rules' => 'required',
            'searchable' => false,
            'sortable' => false,
        ],
    ];

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function permission(): BelongsTo
    {
        return $this->belongsTo(Permission::class);
    }
}
