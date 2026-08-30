<?php

namespace App\Models;

use HasinHayder\TyroDashboard\Concerns\HasCrud;
use Illuminate\Database\Eloquent\Model;

class Estate extends Model
{
    use HasCrud;

    protected $fillable = [
        'estate_name_eng',
        'estate_name_bn',
        'estate_code',
    ];

    protected $resourceFieldOverrides = [
        'estate_name_eng' => [
            'label' => 'Estate Name (English)',
            'rules' => 'required|string|max:150',
            'searchable' => true,
            'sortable' => true,
            'placeholder' => 'e.g. Gulshan Housing Estate',
        ],
        'estate_name_bn' => [
            'label' => 'Estate Name (Bengali)',
            'rules' => 'nullable|string|max:150',
            'placeholder' => 'বাংলা নাম',
        ],
        'estate_code' => [
            'label' => 'Estate Code',
            'rules' => 'required|string|max:20|unique:estates,estate_code',
            'searchable' => true,
            'sortable' => true,
            'placeholder' => 'e.g. EST-001',
            'help_text' => 'Unique code used to identify the estate.',
        ],
    ];
}
