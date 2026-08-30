<?php

namespace App\Models;

use HasinHayder\TyroDashboard\Concerns\HasCrud;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ObligationCategory extends Model
{
    use HasCrud;

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
