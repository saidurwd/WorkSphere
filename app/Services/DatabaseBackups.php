<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

/**
 * Encrypted database backups kept on the local disk.
 *
 * Each backup is the plain dump encrypted with the application key, so a copied
 * file is useless without it. Only the newest {@see RETENTION} are kept, and every
 * create, download and delete is written to the activity log.
 */
class DatabaseBackups
{
    public const DIRECTORY = 'backups';

    public const RETENTION = 5;

    public function __construct(
        private readonly ActivityLogger $activity,
        private readonly MysqlDumpExport $exporter,
    ) {}

    /**
     * Existing backups, newest first, shaped for display.
     *
     * @return list<array{filename: string, human_size: string, last_modified_human: string, modified_at: int}>
     */
    public function list(): array
    {
        $disk = Storage::disk('local');
        $rows = [];

        foreach ($this->files() as $file) {
            $path = self::DIRECTORY.'/'.$file;

            $rows[] = [
                'filename' => $file,
                'human_size' => $this->humanSize($disk->size($path)),
                'last_modified_human' => Carbon::createFromTimestamp($disk->lastModified($path))->diffForHumans(),
                'modified_at' => $disk->lastModified($path),
            ];
        }

        usort($rows, static fn (array $a, array $b): int => $b['modified_at'] <=> $a['modified_at']);

        return $rows;
    }

    /**
     * Take a backup. Returns the file name, or null when the dump failed.
     */
    public function create(string $name, User $actor): ?string
    {
        $name = $this->sanitise($name);

        try {
            $plain = $this->exporter->dump();
        } catch (Throwable $e) {
            $this->activity->record('DatabaseBackup', null, 'backup_failed', null, ['error' => $e::class], $actor->id);

            return null;
        }

        $ciphertext = Crypt::encryptString($plain);
        $filename = ($name !== '' ? $name : 'backup').'_'.now()->format('Ymd_His').'.enc';

        Storage::disk('local')->put(self::DIRECTORY.'/'.$filename, $ciphertext);

        $this->prune();

        $this->activity->record(
            'DatabaseBackup',
            null,
            'backup_created',
            null,
            ['name' => $filename, 'plain_bytes' => strlen($plain), 'stored_bytes' => strlen($ciphertext)],
            $actor->id,
        );

        return $filename;
    }

    public function delete(string $filename, User $actor): void
    {
        $path = $this->pathFor($filename);

        if (! Storage::disk('local')->exists($path)) {
            return;
        }

        Storage::disk('local')->delete($path);

        $this->activity->record('DatabaseBackup', null, 'backup_deleted', ['name' => basename($filename)], null, $actor->id);
    }

    /**
     * The decrypted dump, for download. Aborts with 404 when the file is not a
     * backup under the backups directory.
     */
    public function plain(string $filename, User $actor): string
    {
        $path = $this->pathFor($filename);

        if (! Storage::disk('local')->exists($path)) {
            abort(404);
        }

        $this->activity->record('DatabaseBackup', null, 'backup_downloaded', null, ['name' => basename($filename)], $actor->id);

        return Crypt::decryptString(Storage::disk('local')->get($path));
    }

    public function downloadName(string $filename): string
    {
        return Str::replaceLast('.enc', '.sql', basename($filename));
    }

    public function humanSize(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        $power = $bytes > 0 ? (int) floor(log($bytes, 1024)) : 0;
        $power = min($power, count($units) - 1);

        $value = $bytes / (1024 ** $power);

        return ($power === 0 ? (string) (int) $value : number_format($value, 1)).' '.$units[$power];
    }

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
     * A path confined to the backups directory: anything that resolves outside it,
     * or does not exist, is a 404 rather than a read of some other file.
     */
    protected function pathFor(string $filename): string
    {
        $filename = basename($filename);

        $disk = Storage::disk('local');

        $path = self::DIRECTORY.'/'.$filename;

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
