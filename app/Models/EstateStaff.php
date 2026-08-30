<?php

namespace App\Models;

use HasinHayder\TyroDashboard\Concerns\HasCrud;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EstateStaff extends Model
{
    use HasCrud;

    public string $resourceKey = 'estate_staff';

    protected $fillable = [
        'estate_id',
        'division_id',
        'estate_residence_type_id',
        'staff_name',
        'staff_type',
        'designation',
        'pf_number',
        'quarter_number',
        'quarter_code',
        'token_number',
        'remarks',
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
        'division_id' => [
            'type' => 'select',
            'label' => 'Division',
            'relationship' => 'division',
            'option_label' => 'division_name_eng',
            'rules' => 'required',
            'searchable' => true,
            'sortable' => true,
        ],
        'estate_residence_type_id' => [
            'type' => 'select',
            'label' => 'Residence Type',
            'relationship' => 'residenceType',
            'option_label' => 'residence_type_eng',
            'rules' => 'required',
            'hide_in_index' => true,
        ],
        'staff_name' => [
            'label' => 'Staff Name',
            'rules' => 'required|string|max:150',
            'searchable' => true,
            'sortable' => true,
            'placeholder' => 'e.g. John Doe',
        ],
        'staff_type' => [
            'label' => 'Staff Type',
            'rules' => 'nullable|string|max:50',
            'hide_in_index' => true,
            'placeholder' => 'e.g. Officer',
        ],
        'designation' => [
            'label' => 'Designation',
            'rules' => 'nullable|string|max:100',
            'hide_in_index' => true,
            'placeholder' => 'e.g. Manager',
        ],
        'pf_number' => [
            'type' => 'text',
            'label' => 'PF Number',
            'rules' => 'required|string|max:30|regex:/^[A-Za-z0-9- ]+$/',
            'placeholder' => 'e.g. PF-12345',
            'help_text' => 'Letters, numbers, spaces, or hyphens only.',
        ],
        'quarter_number' => [
            'label' => 'Quarter Number',
            'rules' => 'nullable|string|max:30',
            'hide_in_index' => true,
            'placeholder' => 'e.g. Q-12',
        ],
        'quarter_code' => [
            'label' => 'Quarter Code',
            'rules' => 'nullable|string|max:30',
            'hide_in_create' => true,
            'help_text' => 'Auto-generated as {estate_code}{residence_type_code}{quarter_number padded to 3 digits}, e.g. AGSQ001.',
        ],
        'token_number' => [
            'type' => 'text',
            'label' => 'Token Number',
            'rules' => 'nullable|string|max:30|regex:/^[A-Za-z0-9-]+$/',
            'hide_in_index' => true,
            'placeholder' => 'e.g. TK-9981',
            'help_text' => 'Letters, numbers, or hyphens only.',
        ],
        'remarks' => [
            'type' => 'textarea',
            'label' => 'Remarks',
            'rules' => 'nullable|string',
            'hide_in_index' => true,
        ],
    ];

    public function estate(): BelongsTo
    {
        return $this->belongsTo(Estate::class);
    }

    public function division(): BelongsTo
    {
        return $this->belongsTo(EstateDivision::class);
    }

    public function residenceType(): BelongsTo
    {
        return $this->belongsTo(EstateResidenceType::class, 'estate_residence_type_id');
    }

    protected static function booted(): void
    {
        static::creating(function (EstateStaff $staff) {
            $staff->quarter_code = $staff->generateQuarterCode();
        });
    }

    protected function generateQuarterCode(): string
    {
        $estateCode = $this->estate?->estate_code ?? '';
        $typeCode = $this->residenceType?->residence_type_code ?? '';
        $quarter = str_pad((string) ($this->quarter_number ?? 0), 3, '0', STR_PAD_LEFT);

        return $estateCode.$typeCode.$quarter;
    }
}
