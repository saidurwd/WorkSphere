<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

/**
 * An Obligation.
 *
 * The vendor and cost fields are included because a compliance client needs them
 * to render a renewal, and both are already visible on the web detail page to
 * anyone who passes `ObligationPolicy::view`. The rule is uniform: the API shows
 * exactly what the policy allows on the web, never more.
 */
class ObligationResource extends ApiResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => (int) $this->id,
            'obligation_no' => $this->obligation_no,
            'title' => (string) $this->title,
            'description' => $this->description,
            'notes' => $this->notes,
            'status' => $this->status,
            'priority' => $this->priority,
            'risk_level' => $this->risk_level,
            'obligation_type_id' => $this->obligation_type_id,
            'category_id' => $this->category_id,
            'company_id' => $this->company_id,
            'department_id' => $this->department_id,
            'location_id' => $this->location_id,
            'vendor_id' => $this->vendor_id,
            'owner_user_id' => $this->owner_user_id,
            'backup_user_id' => $this->backup_user_id,
            'reviewer_user_id' => $this->reviewer_user_id,
            'approver_user_id' => $this->approver_user_id,
            'owner' => $this->whenLoaded(
                'owner',
                fn (): ?array => $this->person($this->owner),
            ),
            'start_date' => $this->start_date?->toDateString(),
            'expiry_date' => $this->expiry_date?->toDateString(),
            'renewal_required' => (bool) $this->renewal_required,
            'auto_renew' => (bool) $this->auto_renew,
            'estimated_cost' => $this->estimated_cost === null ? null : (string) $this->estimated_cost,
            'currency' => $this->currency,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }

    /**
     * @return array{id: int, name: string}|null
     */
    protected function person(?object $user): ?array
    {
        return $user === null
            ? null
            : ['id' => (int) $user->id, 'name' => (string) $user->name];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public static function schema(): array
    {
        return [
            'id' => ['type' => 'integer'],
            'obligation_no' => self::nullableString(),
            'title' => ['type' => 'string'],
            'description' => self::nullableString(),
            'notes' => self::nullableString(),
            'status' => self::nullableString(),
            'priority' => self::nullableString(),
            'risk_level' => self::nullableString(),
            'obligation_type_id' => self::nullableInt(),
            'category_id' => self::nullableInt(),
            'company_id' => self::nullableInt(),
            'department_id' => self::nullableInt(),
            'location_id' => self::nullableInt(),
            'vendor_id' => self::nullableInt(),
            'owner_user_id' => self::nullableInt(),
            'backup_user_id' => self::nullableInt(),
            'reviewer_user_id' => self::nullableInt(),
            'approver_user_id' => self::nullableInt(),
            'owner' => ['type' => ['object', 'null'], 'ref' => PersonResource::class],
            'start_date' => self::date(),
            'expiry_date' => self::date(),
            'renewal_required' => ['type' => 'boolean'],
            'auto_renew' => ['type' => 'boolean'],
            'estimated_cost' => self::nullableString(),
            'currency' => self::nullableString(),
            'created_at' => self::dateTime(),
            'updated_at' => self::dateTime(),
        ];
    }
}
