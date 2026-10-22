<?php

namespace App\Http\Controllers;

use App\Services\DatabaseBackups;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Download of a stored backup. Listing, creating and deleting are done on the
 * Database backups page; the file itself is a plain streamed response.
 */
class DatabaseBackupController extends Controller
{
    public function __construct(private readonly DatabaseBackups $backups) {}

    public function download(Request $request, string $filename): StreamedResponse
    {
        $this->authorize('super-admin-only');

        $plain = $this->backups->plain($filename, $request->user());

        return response()->streamDownload(
            function () use ($plain): void {
                echo $plain;
            },
            $this->backups->downloadName($filename),
            ['Content-Type' => 'application/sql'],
        );
    }
}
