<?php

namespace App\Http\Controllers;

use App\Services\OperationalResilienceService;
use Illuminate\Http\JsonResponse;

class HealthCheckController extends Controller
{
    public function status(OperationalResilienceService $service): JsonResponse
    {
        $health = $service->healthCheckStatus();
        $httpStatus = $health['status'] === 'ok' ? 200 : 503;

        return response()->json($health, $httpStatus);
    }

    public function diagnostics(OperationalResilienceService $service): JsonResponse
    {
        $health = $service->healthCheckStatus();
        $diagnostics = $service->systemDiagnostics();
        $httpStatus = $health['status'] === 'ok' ? 200 : 503;

        return response()->json([
            'status' => $health['status'],
            'timestamp' => $health['timestamp'],
            'diagnostics' => $diagnostics,
            'checks' => $health['checks'],
        ], $httpStatus);
    }
}
