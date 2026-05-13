<?php

namespace App\Http\Controllers\Api\V1\Private;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $perPage = min(100, max(1, (int) $request->integer('per_page', 25)));

        $payments = Payment::query()
            ->with(['client:id,company_name,contact_name', 'invoice:id,invoice_number'])
            ->orderByDesc('payment_date')
            ->paginate($perPage);

        return response()->json($payments);
    }

    public function show(Payment $payment): JsonResponse
    {
        $payment->load(['client:id,company_name,contact_name', 'invoice:id,invoice_number']);

        return response()->json([
            'data' => $payment,
        ]);
    }
}
