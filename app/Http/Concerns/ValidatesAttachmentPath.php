<?php

namespace App\Http\Concerns;

use Illuminate\Support\Facades\Storage;

trait ValidatesAttachmentPath
{
    /**
     * Abort (403) if the given normalized path is not safely contained within the
     * configured document storage directory, supporting both local and cloud disks.
     *
     * For **local** disks, `realpath()` is used to resolve symlinks and collapse
     * `../` segments before comparing against the allowed directory root.
     *
     * For **cloud / non-local** disks (S3, MinIO, etc.) `realpath()` is not
     * available, so we validate that the logical path starts with the expected
     * prefix instead.
     */
    protected function abortUnlessSafePath(string $normalizedPath, string $disk, string $directory): void
    {
        $diskDriver = config("filesystems.disks.{$disk}.driver", 'local');

        if ($diskDriver === 'local') {
            $realFilePath = realpath(Storage::disk($disk)->path($normalizedPath));
            $allowedDir = realpath(Storage::disk($disk)->path($directory));

            abort_unless(
                $realFilePath !== false
                && $allowedDir !== false
                && str_starts_with($realFilePath, $allowedDir.DIRECTORY_SEPARATOR),
                403
            );
        } else {
            abort_unless(
                str_starts_with($normalizedPath, $directory.'/') || $normalizedPath === $directory,
                403
            );
        }
    }

    /**
     * Return a safe MIME type, falling back to binary octet-stream for any
     * type that is not on the allow-list.
     */
    protected function getSafeMimeType(?string $mimeType): string
    {
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

        return ($mimeType && in_array($mimeType, $allowed, true)) ? $mimeType : 'application/octet-stream';
    }
}
