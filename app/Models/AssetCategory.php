<?php

namespace App\Models;

use HasinHayder\TyroDashboard\Concerns\HasCrud;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AssetCategory extends Model
{
    use HasCrud;

    protected $fillable = [
        'category_code',
        'category_name',
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
        'category_code' => [
            'rules' => 'nullable|string|max:50',
            'searchable' => true,
            'sortable' => true,
        ],
        'status' => [
            'type' => 'select',
            'options' => [
                'Active' => 'Active',
                'Inactive' => 'Inactive',
            ],
            'rules' => 'required',
        ],
        'subCategories' => [
            'multiple' => false,
            'hide_in_form' => true,
            'hide_in_index' => true,
        ],
        'assets' => [
            'multiple' => false,
            'hide_in_form' => true,
            'hide_in_index' => true,
        ],
    ];

    public function subCategories(): HasMany
    {
        return $this->hasMany(AssetSubCategory::class, 'category_id');
    }

    public function assets(): HasMany
    {
        return $this->hasMany(Asset::class, 'category_id');
    }

    public static function clearFieldCache(): void
    {
        parent::clearFieldCache();
    }
}
