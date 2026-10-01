<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Throwable;

/**
 * Writes append-only rows to `tyro_audit_logs` for every change to a sensitive
 * model. This is the security audit trail: who changed what, when, from where.
 */
class AuditLogger
{
    /**
     * Attributes never written to the audit trail, because a value change there
     * is either meaningless or a security incident.
     *
     * @var list<string>
     */
    protected array $redacted = [
        'password',
        'password_hash',
        'remember_token',
        'two_factor_secret',
        'api_token',
        'token',
        'secret',
    ];

    public function record(string $event, ?Model $subject, ?array $oldValues = null, ?array $newValues = null, array $metadata = []): ?AuditLog
    {
        try {
            $request = request();

            return AuditLog::query()->create([
                'user_id' => Auth::id(),
                'event' => $event,
                'auditable_type' => $subject ? $subject::class : null,
                'auditable_id' => $subject?->getKey(),
                'old_values' => $this->sanitize($oldValues),
                'new_values' => $this->sanitize($newValues),
                'metadata' => $this->sanitize($metadata) ?? [],
                'ip_address' => $request->ip(),
                'user_agent' => Str::limit((string) $request->userAgent(), 255, ''),
                'created_at' => now(),
            ]);
        } catch (Throwable $e) {
            // An audit failure must never break the business operation it describes,
            // but it must be visible. Log the identifier only, never the payload.
            report($e);

            return null;
        }
    }

    public function created(Model $subject, array $attributes = []): ?AuditLog
    {
        return $this->record('created', $subject, null, $attributes);
    }

    public function updated(Model $subject, array $oldValues, array $newValues): ?AuditLog
    {
        return $this->record('updated', $subject, $oldValues, $newValues);
    }

    public function deleted(Model $subject, array $attributes = []): ?AuditLog
    {
        return $this->record('deleted', $subject, $attributes, null);
    }

    /**
     * Strip redacted keys and normalise values for JSON storage.
     */
    protected function sanitize(?array $values): ?array
    {
        if ($values === null) {
            return null;
        }

        $clean = [];

        foreach ($values as $key => $value) {
            if (in_array(strtolower((string) $key), $this->redacted, true)) {
                continue;
            }

            $clean[$key] = $this->normalize($value);
        }

        return $clean;
    }

    protected function normalize(mixed $value): mixed
    {
        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d H:i:s');
        }

        if (is_object($value)) {
            return method_exists($value, '__toString') ? (string) $value : $value::class;
        }

        if (is_array($value)) {
            return array_map(fn (mixed $item): mixed => $this->normalize($item), $value);
        }

        return $value;
    }
}
