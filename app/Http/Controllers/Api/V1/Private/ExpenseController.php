<?php

namespace App\Http\Controllers\Api\V1\Private;

use App\Http\Controllers\Controller;
use App\Models\Expense;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ExpenseController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $perPage = min(100, max(1, (int) $request->integer('per_page', 25)));

        $expenses = Expense::query()
            ->orderByDesc('expense_date')
            ->paginate($perPage);

        return response()->json($expenses);
    }

    public function show(Expense $expense): JsonResponse
    {
        return response()->json([
            'data' => $expense,
        ]);
    }
}
