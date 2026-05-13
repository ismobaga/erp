<?php

namespace App\Http\Controllers\Api\V1\Private;

use App\Http\Controllers\Controller;
use App\Services\AnalyticsService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class KpiController extends Controller
{
    public function __construct(private readonly AnalyticsService $analytics) {}

    public function __invoke(Request $request): JsonResponse
    {
        $start = Carbon::parse($request->string('start_date', now()->startOfMonth()->toDateString()));
        $end = Carbon::parse($request->string('end_date', now()->toDateString()));

        // Clamp so start is never after end.
        if ($start->isAfter($end)) {
            $start = $end->copy()->startOfMonth();
        }

        $kpis = $this->analytics->kpis($start, $end);

        return response()->json([
            'period' => [
                'start' => $start->toDateString(),
                'end' => $end->toDateString(),
            ],
            'data' => $kpis,
        ]);
    }
}
