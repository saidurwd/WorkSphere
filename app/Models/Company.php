<?php

namespace App\Models;

use HasinHayder\TyroDashboard\Concerns\HasCrud;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Company extends Model
{
    use HasCrud;

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
