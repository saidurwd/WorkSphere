<?php

namespace App\Models;

use HasinHayder\TyroDashboard\Concerns\HasCrud;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ObligationType extends Model
{
    use HasCrud;

    protected $fillable = [
        'type_name',
        'description',
        'default_reminder_days',
        'default_priority',
        'default_recurrence_type',
        'default_recurrence_interval',
        'default_risk_level',
        'approval_required',
        'renewal_required',
        'active',
    ];

    protected function casts(): array
    {
        return [
            'default_reminder_days' => 'array',
            'approval_required' => 'boolean',
            'renewal_required' => 'boolean',
            'active' => 'boolean',
            'default_recurrence_interval' => 'integer',
        ];
    }

    public function obligations(): HasMany
    {
        return $this->hasMany(Obligation::class);
    }

    public function notificationRules(): HasMany
    {
        return $this->hasMany(NotificationRule::class);
    }

    public function escalationRules(): HasMany
    {
        return $this->hasMany(EscalationRule::class);
    }
}
