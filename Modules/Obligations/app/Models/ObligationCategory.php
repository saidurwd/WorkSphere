<?php

namespace Modules\Obligations\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ObligationCategory extends Model
{
    use HasFactory;

    protected $fillable = ['category_name', 'description', 'active'];

    protected function casts(): array
    {
        return [
            'active' => 'boolean',
        ];
    }

    public function obligations(): HasMany
    {
        return $this->hasMany(Obligation::class);
    }
}
