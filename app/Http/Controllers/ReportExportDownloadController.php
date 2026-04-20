<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
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

        abort_unless(is_string($path) && Str::startsWith($path, 'reports/'), 403);
        abort_unless(Storage::disk('local')->exists($path), 404);

        return response()->download(storage_path('app/private/' . $path), basename($path));
    }
}
