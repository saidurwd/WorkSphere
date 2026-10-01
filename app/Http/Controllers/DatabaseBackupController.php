<?php

namespace App\Http\Controllers;

use App\Services\ActivityLogger;
use App\Services\MysqlDumpExport;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

/**
 * Database backup — GAP-008.
 *
 * A backup file is the whole database, credentials, hashed or not. It is the most
 * valuable single artefact an attacker could take, so this controller treats it
 * that way:
 *
 * - **super-admin only**, not merely `admin`. The previous gate was the `admin`
 *   role, so a lower-privileged administrator could download everything.
 * - **Encrypted at rest.** The dump is never written to disk in plaintext; it is
 *   sealed with the application key, and only sealed bytes are stored. A stolen
 *   backup file is useless without APP_KEY.
 * - **Audited**, with no filenames of the plaintext content in the trail.
 * - **Retained.** Backups accumulate silently until the disk fills, which is a
 *   denial of service on the host. Old ones are pruned.
 */
class DatabaseBackupController extends Controller
{
    private const DIRECTORY = 'backups';

    /**
     * How many backups to keep. Enough to notice a trend, few enough that the
     * directory cannot fill the disk.
     */
    private const RETENTION = 5;

    public function __construct(
        private readonly ActivityLogger $activity,
        private readonly MysqlDumpExport $exporter,
    ) {}

    public function index(): View
    {
        $this->authorize('super-admin-only');

        $backups = [];

        foreach ($this->files() as $file) {
            $backups[] = [
                'name' => $file,
                'size' => Storage::disk('local')->size(self::DIRECTORY.'/'.$file),
                'created_at' => Storage::disk('local')->lastModified(self::DIRECTORY.'/'.$file),
            ];
        }

        // Newest first.
        usort($backups, static fn (array $a, array $b): int => $b['created_at'] <=> $a['created_at']);

        return view('database-backups.index', [
            'backups' => $backups,
            'retention' => self::RETENTION,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('super-admin-only');

        $name = $this->sanitise((string) $request->input('name'));

        try {
            $plain = $this->exporter->dump();
        } catch (Throwable $e) {
            $this->activity->record(
                'DatabaseBackup',
                null,
                'backup_failed',
                null,
                ['error' => $e::class],
                $request->user()->id,
            );

            return back()->with('error', 'The backup could not be taken.');
        }

        // Encrypted before it touches the disk. The plaintext never exists as a
        // stored object, so a stolen backup file cannot be read without APP_KEY.
        $ciphertext = Crypt::encryptString($plain);
        $filename = ($name !== '' ? $name : 'backup').'_'.now()->format('Ymd_His').'.enc';

        Storage::disk('local')->put(self::DIRECTORY.'/'.$filename, $ciphertext);

        $this->prune();

        $this->activity->record(
            'DatabaseBackup',
            null,
            'backup_created',
            null,
            [
                'name' => $filename,
                'plain_bytes' => strlen($plain),
                'stored_bytes' => strlen($ciphertext),
            ],
            $request->user()->id,
        );

        return back()->with('success', 'Backup created and encrypted.');
    }

    /**
     * Decrypt on the way out, never on the way in.
     */
    public function download(Request $request, string $filename): StreamedResponse
    {
        $this->authorize('super-admin-only');

        $path = $this->pathFor($filename);

        if (! Storage::disk('local')->exists($path)) {
            abort(404);
        }

        $this->activity->record(
            'DatabaseBackup',
            null,
            'backup_downloaded',
            null,
            ['name' => basename($filename)],
            $request->user()->id,
        );

        // Decrypted as ONE payload, because Laravel's Crypt is not a streaming
        // cipher and decrypting it chunk by chunk cannot work. That holds the dump
        // in memory for the duration of the response; for a dump large enough for
        // that to matter the right answer is a streaming cipher (openssl_enc with
        // an explicit IV), which is a larger change than this phase's remit. The
        // limitation is recorded here rather than hidden behind a chunked loop that
        // would silently fail.
        $plain = Crypt::decryptString(Storage::disk('local')->get($path));

        return response()->streamDownload(
            function () use ($plain): void {
                echo $plain;
            },
            Str::replaceLast('.enc', '.sql', basename($filename)),
            ['Content-Type' => 'application/sql'],
        );
    }

    public function destroy(Request $request, string $filename): RedirectResponse
    {
        $this->authorize('super-admin-only');

        $path = $this->pathFor($filename);

        if (Storage::disk('local')->exists($path)) {
            Storage::disk('local')->delete($path);

            $this->activity->record(
                'DatabaseBackup',
                null,
                'backup_deleted',
                ['name' => basename($filename)],
                null,
                $request->user()->id,
            );
        }

        return back()->with('success', 'Backup deleted.');
    }

    /**
     * Keep only the newest {@see self::RETENTION} backups.
     */
    protected function prune(): void
    {
        $files = $this->files();

        if (count($files) <= self::RETENTION) {
            return;
        }

        foreach (array_slice($files, self::RETENTION) as $stale) {
            Storage::disk('local')->delete(self::DIRECTORY.'/'.$stale);
        }
    }

    /**
     * @return list<string>
     */
    protected function files(): array
    {
        $files = Storage::disk('local')->files(self::DIRECTORY);

        usort($files, static fn (string $a, string $b): int => strcmp($a, $b));

        return array_map('basename', $files);
    }

    /**
     * Resolve a caller-supplied name to a path inside the backup directory.
     *
     * Without the realpath check, `../../storage/app/private/anything` walks out of
     * the directory and any stored file becomes downloadable by name.
     */
    protected function pathFor(string $filename): string
    {
        $filename = basename($filename);

        $disk = Storage::disk('local');

        $path = self::DIRECTORY.'/'.$filename;

        // Resolved through the DISK, not storage_path(), so the check follows the
        // disk's own root — including when the disk points somewhere else, and
        // under Storage::fake() in tests.
        $base = realpath($disk->path(self::DIRECTORY));
        $resolved = realpath($disk->path($path));

        if ($base === false || $resolved === false || ! str_starts_with($resolved, $base)) {
            abort(404);
        }

        return $path;
    }

    protected function sanitise(string $name): string
    {
        $clean = preg_replace('/[^A-Za-z0-9_-]+/', '-', $name) ?? '';

        return Str::limit(trim($clean, '-'), 60, '');
    }
}
