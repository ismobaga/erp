<?php

namespace App\Http\Controllers\Api\V1\Private;

use App\Http\Controllers\Controller;
use App\Models\Quote;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class QuoteController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $perPage = min(100, max(1, (int) $request->integer('per_page', 25)));

        $quotes = Quote::query()
            ->with('client:id,company_name,contact_name')
            ->orderByDesc('issue_date')
            ->paginate($perPage);

        return response()->json($quotes);
    }

    public function show(Quote $quote): JsonResponse
    {
        $quote->load(['client:id,company_name,contact_name', 'items']);

        return response()->json([
            'data' => $quote,
        ]);
    }
}
