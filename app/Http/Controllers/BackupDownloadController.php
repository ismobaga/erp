<?php

namespace App\Http\Controllers;

use App\Services\AuditTrailService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BackupDownloadController extends Controller
{
    public function __invoke(Request $request): StreamedResponse
    {
        abort_unless(auth()->check(), 403);
        abort_unless($request->hasValidSignature(), 403);

        // Only admins / users with reports.delete permission may download raw backup archives.
        abort_unless(auth()->user()?->can('reports.delete') ?? false, 403);

        $encryptedPath = (string) $request->query('backup', '');
        abort_unless($encryptedPath !== '', 404);

        try {
            $path = decrypt($encryptedPath);
        } catch (\Throwable) {
            abort(403);
        }

        $disk = (string) config('erp.resilience.backups.disk', 'local');
        $directory = trim((string) config('erp.resilience.backups.directory', 'backups'), '/');

        // Path must stay inside the configured backups directory.
        abort_unless(is_string($path) && str_starts_with($path, $directory . '/'), 403);
        abort_unless(Storage::disk($disk)->exists($path), 404);

        $realFilePath = realpath(Storage::disk($disk)->path($path));
        $allowedDir = realpath(Storage::disk($disk)->path($directory));

        abort_unless(
            $realFilePath !== false
            && $allowedDir !== false
            && str_starts_with($realFilePath, $allowedDir . DIRECTORY_SEPARATOR),
            403
        );

        app(AuditTrailService::class)->log('system_backup_downloaded', null, [
            'disk' => $disk,
            'path' => $path,
            'name' => basename($path),
        ], auth()->id());

        return response()->streamDownload(function () use ($disk, $path): void {
            $stream = Storage::disk($disk)->readStream($path);

            if ($stream === false) {
                return;
            }

            fpassthru($stream);
            fclose($stream);
        }, basename($path), [
            'Content-Type' => 'application/json',
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, no-store, max-age=0',
        ]);
    }
}
