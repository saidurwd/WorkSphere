<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\User;

class AuditLog extends Model
{
    protected $table = 'tyro_audit_logs';

    protected $fillable = [
        'user_id',
        'event',
        'auditable_type',
        'auditable_id',
        'old_values',
        'new_values',
        'metadata',
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
}
