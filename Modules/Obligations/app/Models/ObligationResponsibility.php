<?php

namespace Modules\Obligations\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\User;
use Modules\Obligations\Models\Obligation;

class ObligationResponsibility extends Model
{
    protected $fillable = ['obligation_id', 'user_id', 'responsibility_type', 'escalation_level', 'active'];

    protected function casts(): array
    {
        return [
            'escalation_level' => 'integer',
            'active' => 'boolean',
        ];
    }

    public function obligation(): BelongsTo
    {
        return $this->belongsTo(Obligation::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
