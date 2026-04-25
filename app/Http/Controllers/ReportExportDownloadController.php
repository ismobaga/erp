<?php

namespace App\Http\Controllers;

use App\Services\AuditTrailService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ReportExportDownloadController extends Controller
{
    public function __invoke(Request $request): BinaryFileResponse
    {
        abort_unless($request->hasValidSignature(), 403);
        abort_unless(auth()->user()?->can('reports.view'), 403);

        $encryptedPath = (string) $request->query('report', '');
        abort_unless($encryptedPath !== '', 404);

        $path = decrypt($encryptedPath);

        abort_unless(is_string($path) && str_starts_with($path, 'reports/'), 403);
        abort_unless(Storage::disk('local')->exists($path), 404);

        $realFilePath = realpath(storage_path('app/private/' . $path));
        $allowedDir = realpath(storage_path('app/private/reports'));

        abort_unless(
            $realFilePath !== false
            && $allowedDir !== false
            && str_starts_with($realFilePath, $allowedDir . DIRECTORY_SEPARATOR),
            403
        );

        app(\App\Services\AuditTrailService::class)->log('report_downloaded', null, [
            'path' => $path,
            'name' => basename($path),
        ]);

        return response()->download(storage_path('app/private/' . $path), basename($path));
    }
}
