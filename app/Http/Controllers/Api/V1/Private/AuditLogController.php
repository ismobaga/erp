<?php

namespace App\Http\Controllers\Api\V1\Private;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $perPage = min(100, max(1, (int) $request->integer('per_page', 25)));

        $logs = ActivityLog::query()
            ->latest()
            ->paginate($perPage);

        return response()->json($logs);
    }
}
