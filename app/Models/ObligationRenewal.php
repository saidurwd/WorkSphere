<?php

namespace App\Models;

use HasinHayder\TyroDashboard\Concerns\HasCrud;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ObligationRenewal extends Model
{
    use HasCrud;

    protected $fillable = [
        'obligation_id',
        'previous_expiry_date',
        'new_start_date',
        'new_expiry_date',
        'renewal_date',
        'vendor_id',
        'cost',
        'currency',
        'purchase_reference',
        'invoice_reference',
        'remarks',
        'renewed_by',
    ];

    protected function casts(): array
    {
        return [
            'previous_expiry_date' => 'date',
            'new_start_date' => 'date',
            'new_expiry_date' => 'date',
            'renewal_date' => 'date',
            'cost' => 'decimal:2',
        ];
    }

    public function obligation(): BelongsTo
    {
        return $this->belongsTo(Obligation::class);
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function renewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'renewed_by');
    }
}
