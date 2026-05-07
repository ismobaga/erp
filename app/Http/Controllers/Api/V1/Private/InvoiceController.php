<?php

namespace App\Http\Controllers\Api\V1\Private;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InvoiceController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $perPage = min(100, max(1, (int) $request->integer('per_page', 25)));

        $invoices = Invoice::query()
            ->with('client:id,company_name,contact_name')
            ->orderByDesc('issue_date')
            ->paginate($perPage);

        return response()->json($invoices);
    }

    public function show(Invoice $invoice): JsonResponse
    {
        $invoice->load('client:id,company_name,contact_name');

        return response()->json([
            'data' => $invoice,
        ]);
    }
}
