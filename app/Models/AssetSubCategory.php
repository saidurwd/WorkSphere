<?php

namespace App\Models;

use HasinHayder\TyroDashboard\Concerns\HasCrud;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssetSubCategory extends Model
{
    use HasCrud;

    protected $fillable = [
        'category_id',
        'sub_category_name',
        'description',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'status' => 'string',
        ];
    }

    public $resourceFieldOverrides = [
        'category_id' => [
            'type' => 'select',
            'label' => 'Category',
            'relationship' => 'category',
            'option_label' => 'category_name',
            'rules' => 'required',
        ],
        'status' => [
            'type' => 'select',
            'options' => [
                'Active' => 'Active',
                'Inactive' => 'Inactive',
            ],
            'rules' => 'required',
        ],
    ];

    public static function clearFieldCache(): void
    {
        parent::clearFieldCache();
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(AssetCategory::class);
    }
}
