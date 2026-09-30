<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Obligations\Models\Obligation;

class Company extends Model
{
    protected $fillable = ['company_code', 'company_name', 'address', 'city', 'country', 'status'];

    protected function casts(): array
    {
        return [
            'status' => 'string',
        ];
    }

    public function obligations(): HasMany
    {
        return $this->hasMany(Obligation::class);
    }
}
