<?php

namespace App\Http\Controllers;

use App\Services\MysqlDumpExport;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class DatabaseBackupController extends Controller
{
    private const DISK = 'local';

    private const DIRECTORY = 'backups';

    public function index(): View
    {
        $backups = $this->listBackups();

        return view('database-backups.index', [
            'backups' => $backups,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => ['nullable', 'string', 'max:255'],
        ]);

        $timestamp = now()->format('Y-m-d_His');
        $safeName = $request->input('name')
            ? $this->sanitize($request->input('name')).'_'.$timestamp
            : 'backup_'.$timestamp;

        $filename = $safeName.'.sql';
        $path = self::DIRECTORY.'/'.$filename;

        $dump = (new MysqlDumpExport)->dump();

        Storage::disk(self::DISK)->put($path, $dump);

        return redirect()
            ->route('dashboard.database-backups.index')
            ->with('success', 'Database backup created successfully.');
    }

    public function download(string $filename): BinaryFileResponse
    {
        $path = self::DIRECTORY.'/'.$this->sanitize($filename);

        if (! Storage::disk(self::DISK)->exists($path)) {
            abort(404);
        }

        $absolute = Storage::disk(self::DISK)->path($path);

        return response()->download($absolute, basename($absolute), [
            'Content-Type' => 'application/sql',
            'Content-Disposition' => 'attachment; filename="'.basename($absolute).'"',
        ])->deleteFileAfterSend(false);
    }

    public function destroy(string $filename): RedirectResponse
    {
        $path = self::DIRECTORY.'/'.$this->sanitize($filename);

        if (Storage::disk(self::DISK)->exists($path)) {
            Storage::disk(self::DISK)->delete($path);
        }

        return redirect()
            ->route('dashboard.database-backups.index')
            ->with('success', 'Backup deleted successfully.');
    }

    private function listBackups(): array
    {
        if (! Storage::disk(self::DISK)->exists(self::DIRECTORY)) {
            return [];
        }

        $files = Storage::disk(self::DISK)->files(self::DIRECTORY);

        $backups = array_map(function (string $file): array {
            $size = Storage::disk(self::DISK)->size($file);
            $lastModified = Storage::disk(self::DISK)->lastModified($file);

            return [
                'filename' => basename($file),
                'path' => $file,
                'size' => $size,
                'human_size' => $this->humanSize($size),
                'last_modified' => $lastModified,
                'last_modified_human' => now()->createFromTimestamp($lastModified)->format('M d, Y H:i:s'),
            ];
        }, $files);

        usort($backups, fn (array $a, array $b): int => $b['last_modified'] <=> $a['last_modified']);

        return $backups;
    }

    private function humanSize(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $factor = 0;

        while ($bytes >= 1024 && $factor < count($units) - 1) {
            $bytes /= 1024;
            $factor++;
        }

        return round($bytes, 2).' '.$units[$factor];
    }

    private function sanitize(string $value): string
    {
        return preg_replace('/[^a-zA-Z0-9_\-.]/', '_', $value);
    }
}
