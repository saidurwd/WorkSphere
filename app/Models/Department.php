<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Department extends Model
{
    use HasFactory;

    protected $fillable = [
        'department_name',
        'department_code',
        'head_of_department_id',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'status' => 'string',
        ];
    }

    public function headOfDepartment(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'head_of_department_id');
    }

    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class);
    }
}
