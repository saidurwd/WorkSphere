<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

class AuditLog extends Model
{
    use HasFactory;

    protected $table = 'tyro_audit_logs';

    /**
     * An audit row is evidence. It has no `updated_at` and cannot be mutated or
     * deleted, so a compromised application account still cannot erase history.
     */
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'event',
        'auditable_type',
        'auditable_id',
        'old_values',
        'new_values',
        'metadata',
        'ip_address',
        'user_agent',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'auditable_id' => 'integer',
            'old_values' => 'array',
            'new_values' => 'array',
            'metadata' => 'array',
            'created_at' => 'datetime',
        ];
    }

    /**
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function forSubject(Builder $query, Model $subject): void
    {
        $query->where('auditable_type', $subject::class)
            ->where('auditable_id', $subject->getKey());
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * The audited model class, when the log still points at a resolvable type.
     */
    public function auditable(): ?object
    {
        if (! $this->auditable_type || ! class_exists($this->auditable_type)) {
            return null;
        }

        return $this->auditable_type::find($this->auditable_id);
    }

    /**
     * Human readable label for the audited record.
     */
    public function subjectLabel(): string
    {
        if (! $this->auditable_type) {
            return 'System';
        }

        $model = class_basename($this->auditable_type);

        return $model.' #'.$this->auditable_id;
    }

    /**
     * An audit row is append-only. Mutation and deletion are refused outright so
     * no code path — controller, tinker, queued job — can rewrite history.
     */
    public function delete(): void
    {
        throw new LogicException('Audit log rows are append-only and cannot be deleted.');
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function update(array $attributes = [], array $options = []): bool
    {
        throw new LogicException('Audit log rows are append-only and cannot be updated.');
    }

    /**
     * @param  array<int, mixed>  $ids
     */
    public static function destroy($ids): void
    {
        throw new LogicException('Audit log rows are append-only and cannot be deleted.');
    }

    /**
     * Model events do not fire for a mass `AuditLog::query()->delete()`, so the
     * builder is wrapped to refuse it too. Without this, `truncate()` or a raw
     * builder delete could erase the trail while every instance-level guard
     * reported the table as protected.
     *
     * @param  EloquentBuilder<self>  $query
     */
    public function newEloquentBuilder($query): EloquentBuilder
    {
        return new class($query) extends EloquentBuilder
        {
            /**
             * @param  int|null  $limit
             * @return int
             */
            public function delete($limit = null)
            {
                throw new LogicException('Audit log rows are append-only and cannot be deleted.');
            }

            public function truncate(): void
            {
                throw new LogicException('Audit log rows are append-only and cannot be truncated.');
            }

            public function forceDelete(): void
            {
                throw new LogicException('Audit log rows are append-only and cannot be deleted.');
            }
        };
    }

    /**
     * Removing the row in memory before it is ever persisted is equally refused.
     */
    protected static function boot(): void
    {
        parent::boot();

        static::deleting(fn () => throw new LogicException('Audit log rows are append-only and cannot be deleted.'));
    }
}
