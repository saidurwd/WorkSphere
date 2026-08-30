<?php

namespace App\Models;

use HasinHayder\TyroDashboard\Concerns\HasCrud;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EstateDivision extends Model
{
    use HasCrud;

    protected $fillable = [
        'estate_id',
        'division_name_eng',
        'division_name_bn',
        'division_code',
    ];

    protected $resourceFieldOverrides = [
        'estate_id' => [
            'type' => 'select',
            'label' => 'Estate',
            'relationship' => 'estate',
            'option_label' => 'estate_name_eng',
            'rules' => 'required',
            'searchable' => true,
            'sortable' => true,
        ],
        'division_name_eng' => [
            'label' => 'Division Name (English)',
            'rules' => 'required|string|max:150',
            'searchable' => true,
            'sortable' => true,
            'placeholder' => 'e.g. North Division',
        ],
        'division_name_bn' => [
            'label' => 'Division Name (Bengali)',
            'rules' => 'nullable|string|max:150',
            'placeholder' => 'বিভাগের নাম',
        ],
        'division_code' => [
            'label' => 'Division Code',
            'rules' => 'required|string|max:20',
            'searchable' => true,
            'sortable' => true,
            'placeholder' => 'e.g. DIV-001',
            'help_text' => 'Unique code used to identify the division.',
        ],
    ];

    public function estate(): BelongsTo
    {
        return $this->belongsTo(Estate::class);
    }
}
