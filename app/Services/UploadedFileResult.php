<?php

namespace App\Services;

/**
 * The record of one stored upload.
 *
 * A value object rather than a bare path so nothing downstream can treat a
 * stored file as reachable by URL: the path alone is not sufficient to serve it,
 * and carrying the disk alongside it is what stops a caller assuming the wrong
 * disk.
 */
final class UploadedFileResult
{
    public function __construct(
        public readonly string $path,
        public readonly string $disk,
        public readonly string $originalName,
        public readonly string $mimeType,
        public readonly int $size,
        public readonly ?string $checksum,
    ) {}

    /**
     * Whether this object is an image, for the view to decide on preview.
     */
    public function isImage(): bool
    {
        return str_starts_with($this->mimeType, 'image/');
    }
}
