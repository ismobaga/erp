<?php

namespace App\Http\Controllers;

use App\Models\Attachment;
use App\Services\AuditTrailService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AttachmentDownloadController
{
    public function __invoke(Request $request, Attachment $attachment): StreamedResponse
    {
        abort_unless(auth()->check(), 403);
        abort_unless($request->hasValidSignature(), 403);

        $user = auth()->user();
        $canDownload = ($user?->can('documents.view') ?? false) || ((int) $attachment->uploaded_by === (int) auth()->id());
        abort_unless($canDownload, 403);

        $disk = (string) config('erp.documents.disk', 'local');
        $directory = trim((string) config('erp.documents.directory', 'attachments'), '/');
        $normalizedPath = ltrim((string) $attachment->file_path, '/');

        abort_unless(Str::startsWith($normalizedPath, $directory . '/'), 403);
        abort_unless(Storage::disk($disk)->exists($normalizedPath), 404);

        app(\App\Services\AuditTrailService::class)->log('document_downloaded', $attachment, [
            'disk' => $disk,
            'path' => $normalizedPath,
            'mime_type' => $attachment->mime_type,
        ]);

        return response()->streamDownload(function () use ($disk, $normalizedPath): void {
            $stream = Storage::disk($disk)->readStream($normalizedPath);

            if ($stream === false) {
                return;
            }

            fpassthru($stream);
            fclose($stream);
        }, basename((string) $attachment->file_name), [
            'Content-Type' => $attachment->mime_type ?: 'application/octet-stream',
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, no-store, max-age=0',
        ]);
    }
}
