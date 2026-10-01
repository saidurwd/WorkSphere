<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * The single upload path — GAP-011.
 *
 * Three controllers wrote files before this existed, each with its own rules and
 * each putting them on the **public** disk, where the URL is guessable from the
 * filename. One service fixes both halves: every upload lands on a private disk
 * behind an authorised controller, and nothing reaches it that has not passed the
 * checks below.
 *
 * The checks, and why each exists rather than being left to validation alone:
 *
 * - **Extension AND MIME must agree.** A `.php` renamed to `.png` passes an
 *   extension check and fails a MIME check, and vice versa; requiring both to
 *   name the same kind of file is what catches a disguised one.
 * - **Content is sniffed, not trusted.** `getMimeType()` reads the bytes, so a
 *   PHP file carrying an image extension is rejected on content.
 * - **The stored name is generated, never derived.** Using the client's filename
 *   invites traversal (`../../`), collisions and null bytes.
 * - **No execution bits.** `store()` writes `0644`; the file is data, not a
 *   script, and must never be executable on disk.
 * - **A checksum is stored.** It makes deduplication possible and detects silent
 *   tampering with an existing object.
 */
class UploadService
{
    /**
     * extension => the MIME types that extension may legitimately carry.
     *
     * An extension absent from this map is rejected outright — the default is
     * denial, not acceptance.
     *
     * @var array<string, list<string>>
     */
    private const ALLOWED = [
        'pdf' => ['application/pdf'],
        'png' => ['image/png'],
        'jpg' => ['image/jpeg'],
        'jpeg' => ['image/jpeg'],
        'gif' => ['image/gif'],
        'webp' => ['image/webp'],
        'doc' => ['application/msword'],
        'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document'],
        'xls' => ['application/vnd.ms-excel'],
        'xlsx' => ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'],
        'csv' => ['text/csv', 'application/csv', 'text/plain'],
        'txt' => ['text/plain'],
        'zip' => ['application/zip', 'application/x-zip-compressed'],
    ];

    /**
     * Nothing in this application is served as executable content, and an uploaded
     * file must never be runnable from the web root regardless of extension.
     *
     * @var list<string>
     */
    private const NEVER_ALLOWED = [
        'php', 'phtml', 'php3', 'php4', 'php5', 'phar', 'phps',
        'js', 'mjs', 'cjs', 'html', 'htm', 'shtml', 'xhtml',
        'svg', 'xml', 'exe', 'com', 'bat', 'cmd', 'sh', 'bash',
        'jsp', 'asp', 'aspx', 'cgi', 'pl',
    ];

    public function __construct(
        private readonly string $disk = 'local',
        private readonly int $maxKilobytes = 10240,
    ) {}

    /**
     * Store one validated upload.
     *
     * @throws InvalidArgumentException when the file fails any check
     */
    public function store(UploadedFile $file, string $directory): UploadedFileResult
    {
        $this->assertAcceptable($file);

        $extension = strtolower((string) $file->getClientOriginalExtension());
        $originalName = $this->safeDisplayName($file);

        $path = $file->store($directory, $this->disk);

        if ($path === false) {
            throw new InvalidArgumentException('The file could not be stored.');
        }

        return new UploadedFileResult(
            path: $path,
            disk: $this->disk,
            originalName: $originalName,
            mimeType: (string) $file->getMimeType(),
            size: (int) $file->getSize(),
            checksum: hash_file('sha256', $file->getRealPath()) ?: null,
        );
    }

    /**
     * Every check, with the reason each failure is worth reporting separately.
     */
    public function assertAcceptable(UploadedFile $file): void
    {
        $originalName = (string) $file->getClientOriginalName();

        if ($file->isValid() === false) {
            throw new InvalidArgumentException('The upload did not complete.');
        }

        if ($file->getSize() === false || $file->getSize() > $this->maxKilobytes * 1024) {
            throw new InvalidArgumentException(sprintf(
                'The file exceeds the %d MB limit.',
                intdiv($this->maxKilobytes, 1024),
            ));
        }

        $extension = strtolower((string) $file->getClientOriginalExtension());

        if ($extension === '') {
            throw new InvalidArgumentException('The file has no extension.');
        }

        if (in_array($extension, self::NEVER_ALLOWED, true)) {
            throw new InvalidArgumentException('Files of that type are not accepted.');
        }

        if (! array_key_exists($extension, self::ALLOWED)) {
            throw new InvalidArgumentException(sprintf(
                'Files of type .%s are not accepted.',
                $extension,
            ));
        }

        // Sniffed from the content, so a renamed executable fails here.
        $detected = strtolower((string) $file->getMimeType());

        if (! in_array($detected, self::ALLOWED[$extension], true)) {
            throw new InvalidArgumentException(sprintf(
                'The file content (%s) does not match its .%s extension.',
                $detected === '' ? 'unknown' : $detected,
                $extension,
            ));
        }
    }

    /**
     * A display name that is safe to store and to render.
     *
     * The client's filename is kept only so a user recognises their own file, with
     * the directory portion and anything non-alphanumeric removed. The stored
     * PATH is generated separately and never derives from this.
     */
    protected function safeDisplayName(UploadedFile $file): string
    {
        $original = basename((string) $file->getClientOriginalName());
        $extension = strtolower((string) $file->getClientOriginalExtension());
        $stem = Str::of($original)->beforeLast('.')->limit(120, '')->value();

        $clean = preg_replace('/[^A-Za-z0-9._\- ]+/u', '', $stem) ?? '';
        $clean = trim(preg_replace('/\s+/', ' ', $clean) ?? '');

        if ($clean === '' || $clean === '.' || $clean === '..') {
            $clean = 'file';
        }

        return $extension === '' ? $clean : $clean.'.'.$extension;
    }

    /**
     * Stream a stored file through an authorised response.
     *
     * The ONLY way a stored object becomes readable. No caller receives a URL or a
     * path it could hand to a browser directly.
     */
    public function download(UploadedFileResult $file, string $name): StreamedResponse
    {
        if (! Storage::disk($file->disk)->exists($file->path)) {
            abort(404);
        }

        return Storage::disk($file->disk)->download($file->path, $name);
    }

    public function delete(UploadedFileResult $file): void
    {
        Storage::disk($file->disk)->delete($file->path);
    }

    public function disk(): string
    {
        return $this->disk;
    }

    /**
     * @return list<string>
     */
    public function allowedExtensions(): array
    {
        return array_keys(self::ALLOWED);
    }
}
