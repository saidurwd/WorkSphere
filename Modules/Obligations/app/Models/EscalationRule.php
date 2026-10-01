<?php

namespace Modules\Obligations\Models;

use App\Models\Company;
use App\Models\Department;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EscalationRule extends Model
{
    protected $fillable = [
        'obligation_type_id',
        // Phase 8 GAP-031. A NULL value means the rule is global, which is what
        // every rule created before this column existed means today.
        'department_id',
        'company_id',
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

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Whether this rule applies to the given obligation's scope.
     *
     * Kept on the model so the UI can show a rule's reach without duplicating the
     * comparison, and so a test can assert it without a database round trip.
     */
    public function appliesToScope(?int $departmentId, ?int $companyId): bool
    {
        $departmentOk = $this->department_id === null || $this->department_id === $departmentId;
        $companyOk = $this->company_id === null || $this->company_id === $companyId;

        return $departmentOk && $companyOk;
    }
}
