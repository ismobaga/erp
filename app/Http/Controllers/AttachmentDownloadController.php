<?php

namespace App\Http\Controllers;

use App\Models\Attachment;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class AttachmentDownloadController
{
    public function __invoke(Attachment $attachment): BinaryFileResponse
    {
        abort_unless(auth()->check(), 403);
        abort_unless(Storage::disk('local')->exists($attachment->file_path), 404);

        return response()->download(
            Storage::disk('local')->path($attachment->file_path),
            $attachment->file_name,
        );
    }
}
