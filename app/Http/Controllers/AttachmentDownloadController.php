<?php

namespace App\Http\Controllers;

use App\Models\Attachment;
use App\Services\AuditTrailService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AttachmentDownloadController
{
    public function __invoke(Request $request, Attachment $attachment): StreamedResponse
    {
        abort_unless(auth()->check(), 403);
        abort_unless($request->hasValidSignature(), 403);

        $user = auth()->user();

        // ── Company Scoping: Ensure attachment belongs to user's company ──
        $userCompany = currentCompany();
        abort_unless($userCompany && (int) $attachment->company_id === (int) $userCompany->id, 403);

        // ── Permission Check: documents.view OR user uploaded the file ─────
        $canDownload = ($user?->can('documents.view') ?? false) || ((int) $attachment->uploaded_by === (int) auth()->id());
        abort_unless($canDownload, 403);

        // ── Path Traversal Prevention ───────────────────────────────────
        $disk = (string) config('erp.documents.disk', 'local');
        $directory = trim((string) config('erp.documents.directory', 'attachments'), '/');
        $normalizedPath = ltrim((string) $attachment->file_path, '/');

        $realFilePath = realpath(Storage::disk($disk)->path($normalizedPath));
        $allowedDir = realpath(Storage::disk($disk)->path($directory));

        abort_unless(
            $realFilePath !== false
            && $allowedDir !== false
            && str_starts_with($realFilePath, $allowedDir . DIRECTORY_SEPARATOR),
            403
        );
        abort_unless(Storage::disk($disk)->exists($normalizedPath), 404);

        // ── Audit Trail ────────────────────────────────────────────────
        app(\App\Services\AuditTrailService::class)->log('document_downloaded', $attachment, [
            'disk' => $disk,
            'path' => $normalizedPath,
            'mime_type' => $attachment->mime_type,
            'user_id' => auth()->id(),
        ]);

        // ── Secure Download Response ───────────────────────────────────
        return response()->streamDownload(function () use ($disk, $normalizedPath): void {
            $stream = Storage::disk($disk)->readStream($normalizedPath);

            if ($stream === false) {
                return;
            }

            fpassthru($stream);
            fclose($stream);
        }, basename((string) $attachment->file_name), [
            'Content-Type' => $this->getSafeMimeType($attachment->mime_type),
            'X-Content-Type-Options' => 'nosniff',
            'Content-Disposition' => 'attachment; filename="' . addslashes(basename((string) $attachment->file_name)) . '"',
            'Cache-Control' => 'private, no-store, max-age=0',
            'Pragma' => 'no-cache',
        ]);
    }

    /**
     * Return a safe MIME type, defaulting to binary for unknown types.
     */
    private function getSafeMimeType(?string $mimeType): string
    {
        if (!$mimeType || !is_string($mimeType)) {
            return 'application/octet-stream';
        }

        // Whitelist of allowed MIME types
        $allowed = [
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'text/csv',
            'text/plain',
            'image/jpeg',
            'image/png',
            'application/zip',
        ];

        return in_array($mimeType, $allowed, true) ? $mimeType : 'application/octet-stream';
    }
}
