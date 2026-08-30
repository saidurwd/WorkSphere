<?php

namespace App\Models;

use HasinHayder\TyroDashboard\Concerns\HasCrud;
use Illuminate\Database\Eloquent\Model;

class EstateResidenceType extends Model
{
    use HasCrud;

    protected $fillable = [
        'residence_type_eng',
        'residence_type_bn',
        'residence_type_code',
    ];

    protected $resourceFieldOverrides = [
        'residence_type_eng' => [
            'label' => 'Residence Type (English)',
            'rules' => 'required|string|max:100',
            'searchable' => true,
            'sortable' => true,
            'placeholder' => 'e.g. Family Quarter',
        ],
        'residence_type_bn' => [
            'label' => 'Residence Type (Bengali)',
            'rules' => 'nullable|string|max:100',
            'placeholder' => 'বাসস্থানের ধরণ',
        ],
        'residence_type_code' => [
            'label' => 'Residence Type Code',
            'rules' => 'required|string|max:20|unique:estate_residence_types,residence_type_code',
            'searchable' => true,
            'sortable' => true,
            'placeholder' => 'e.g. RQ-001',
            'help_text' => 'Unique code used to identify the residence type.',
        ],
    ];
}
