<?php

namespace Database\Factories;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<AuditLog>
 *
 * The security trail, which is APPEND-ONLY: `AuditLog::delete()` throws and there
 * is no `updated_at`. The factory therefore has no state that could create an
 * updatable row, and no `ip_address`/`user_agent` are invented — Phase 2 added
 * those columns and a factory that filled them with junk would make the
 * "the audit row carries the request context" assertion pass on a fixture rather
 * than on the code that writes it.
 */
class AuditLogFactory extends Factory
{
    protected $model = AuditLog::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'event' => fake()->randomElement(['created', 'updated', 'deleted']),
            'auditable_type' => null,
            'auditable_id' => null,
            'old_values' => null,
            'new_values' => null,
            'metadata' => null,
        ];
    }

    /**
     * @param  array<string, mixed>|null  $old
     * @param  array<string, mixed>|null  $new
     */
    public function forEvent(string $event, ?object $auditable = null, ?array $old = null, ?array $new = null, ?User $actor = null): static
    {
        return $this->state(fn (): array => [
            'event' => $event,
            'user_id' => $actor?->id ?? User::factory(),
            'auditable_type' => $auditable === null ? null : $auditable::class,
            'auditable_id' => $auditable?->getKey(),
            'old_values' => $old,
            'new_values' => $new,
            'metadata' => ['request_id' => Str::random(8)],
        ]);
    }
}
