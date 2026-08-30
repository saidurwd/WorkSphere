<?php

namespace App\Models;

use HasinHayder\TyroDashboard\Concerns\HasCrud;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Obligation extends Model
{
    use HasCrud;

    protected $fillable = [
        'obligation_no',
        'title',
        'description',
        'obligation_type_id',
        'category_id',
        'company_id',
        'department_id',
        'location_id',
        'vendor_id',
        'owner_user_id',
        'backup_user_id',
        'reviewer_user_id',
        'approver_user_id',
        'start_date',
        'expiry_date',
        'renewal_required',
        'auto_renew',
        'recurrence_type',
        'recurrence_interval',
        'priority',
        'risk_level',
        'estimated_cost',
        'currency',
        'status',
        'notes',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'expiry_date' => 'date',
            'renewal_required' => 'boolean',
            'auto_renew' => 'boolean',
            'estimated_cost' => 'decimal:2',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    public function type(): BelongsTo
    {
        return $this->belongsTo(ObligationType::class, 'obligation_type_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ObligationCategory::class, 'category_id');
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_user_id');
    }

    public function backupUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'backup_user_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewer_user_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approver_user_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function responsibilities(): HasMany
    {
        return $this->hasMany(ObligationResponsibility::class);
    }

    public function renewals(): HasMany
    {
        return $this->hasMany(ObligationRenewal::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(ObligationDocument::class);
    }

    public function activityLogs(): HasMany
    {
        return $this->hasMany(ObligationActivityLog::class);
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    public function notificationLogs(): HasMany
    {
        return $this->hasMany(NotificationLog::class);
    }

    public function latestRenewal(): HasOne
    {
        return $this->hasOne(ObligationRenewal::class)->latestOfMany();
    }
}
