<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

/**
 * Shared attachment — DATABASE-ARCHITECTURE.md §4.6.
 *
 * The default disk is `local`, which is private and has no public URL. There is
 * deliberately no `url()` accessor here that would hand out a direct link:
 * downloads must go through an authorised controller, otherwise any file's
 * reachability depends only on knowing (or guessing) its path.
 *
 * This class does not delete the underlying file — that is the upload service's
 * job (Phase 5), so the two concerns stay separable and the disk name is never
 * guessed here.
 */
class Attachment extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'attachable_type',
        'attachable_id',
        'disk',
        'path',
        'original_name',
        'mime_type',
        'size',
        'checksum',
        'uploaded_by',
    ];

    protected function casts(): array
    {
        return [
            'size' => 'integer',
        ];
    }

    public function attachable(): MorphTo
    {
        return $this->morphTo();
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    /**
     * Absolute path on the configured disk. Intended for the download controller,
     * never for a view.
     */
    public function absolutePath(): string
    {
        return Storage::disk($this->disk)->path($this->path);
    }

    public function existsOnDisk(): bool
    {
        return Storage::disk($this->disk)->exists($this->path);
    }

    /**
     * @param  Builder<self>  $query
     */
    public function scopeForSubject(Builder $query, string $type, int $id): void
    {
        $query->where('attachable_type', $type)->where('attachable_id', $id);
    }

    /**
     * @param  Builder<self>  $query
     */
    public function scopeWithChecksum(Builder $query, string $checksum): void
    {
        $query->where('checksum', $checksum);
    }
}
