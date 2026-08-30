<?php

namespace App\Models;

use HasinHayder\TyroDashboard\Concerns\HasCrud;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EscalationRule extends Model
{
    use HasCrud;

    protected $fillable = [
        'obligation_type_id',
        'days_before_expiry',
        'days_after_expiry',
        'escalation_level',
        'recipient_type',
        'channel',
        'active',
    ];

    protected function casts(): array
    {
        return [
            'days_before_expiry' => 'integer',
            'days_after_expiry' => 'integer',
            'active' => 'boolean',
        ];
    }

    public function obligationType(): BelongsTo
    {
        return $this->belongsTo(ObligationType::class);
    }
}
