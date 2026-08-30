<?php

namespace App\Models;

use HasinHayder\TyroDashboard\Concerns\HasCrud;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivityLog extends Model
{
    use HasCrud;

    protected $fillable = [
        'user_id',
        'module_name',
        'record_id',
        'action',
        'old_value',
        'new_value',
        'ip_address',
        'created_at',
    ];

    protected $resourceFields = [
        'user_id' => [
            'type' => 'select',
            'label' => 'User',
            'relationship' => 'user',
            'option_label' => 'name',
            'rules' => 'nullable',
            'searchable' => false,
            'sortable' => false,
        ],
        'module_name' => [
            'label' => 'Module',
            'type' => 'text',
            'rules' => 'nullable|string|max:255',
            'searchable' => true,
            'sortable' => true,
        ],
        'record_id' => [
            'label' => 'Record ID',
            'type' => 'number',
            'rules' => 'nullable|integer',
            'searchable' => false,
            'sortable' => true,
        ],
        'action' => [
            'label' => 'Action',
            'type' => 'text',
            'rules' => 'nullable|string|max:255',
            'searchable' => true,
            'sortable' => true,
        ],
        'old_value' => [
            'type' => 'textarea',
            'label' => 'Old Value',
            'rules' => 'nullable|string',
            'hide_in_index' => true,
        ],
        'new_value' => [
            'type' => 'textarea',
            'label' => 'New Value',
            'rules' => 'nullable|string',
            'hide_in_index' => true,
        ],
        'ip_address' => [
            'label' => 'IP Address',
            'type' => 'text',
            'rules' => 'nullable|string|max:45',
            'searchable' => true,
            'sortable' => false,
        ],
        'created_at' => [
            'label' => 'Logged At',
            'type' => 'datetime-local',
            'rules' => 'nullable|date',
            'searchable' => false,
            'sortable' => true,
            'hide_in_form' => true,
            'hide_in_create' => true,
            'hide_in_edit' => true,
        ],
    ];

    protected function casts(): array
    {
        return [
            'record_id' => 'integer',
            'created_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
