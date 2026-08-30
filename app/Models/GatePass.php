<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'gate_pass_number', 'issue_date', 'name', 'purpose', 'address',
    'description', 'quantity', 'prepared_by', 'checked_by',
])]
#[Hidden(['pivot'])]
class GatePass extends Model
{
    protected function casts(): array
    {
        return [
            'issue_date' => 'date',
            'quantity' => 'integer',
        ];
    }

    public function isChecked(): bool
    {
        return ! empty($this->checked_by);
    }

    public function isToday(): bool
    {
        return $this->issue_date->isToday();
    }

    public function isUpcoming(): bool
    {
        return $this->issue_date->isFuture();
    }
}
