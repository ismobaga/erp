<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Services\AuditTrailService;
use App\Services\Pdf\BusinessDocumentPdf;
use App\Support\ResolvesLogoDataUri;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class InvoicePdfController extends Controller
{
    use ResolvesLogoDataUri;

    public function __invoke(Request $request, Invoice $invoice): Response
    {
        $this->authorize('view', $invoice);

        $invoice->loadMissing(['client', 'items.service', 'quote']);

        app(AuditTrailService::class)->log('invoice_pdf_accessed', $invoice, [
            'invoice_number' => $invoice->invoice_number,
            'download' => $request->boolean('download'),
        ], auth()->id());

        $company = currentCompany();
        $companyName = $company?->name ?: config('app.name');

        $viewData = [
            'invoice' => $invoice,
            'company' => $company,
            'bankDetails' => [
                'bank_name' => $company?->bank_name ?: null,
                'account_name' => $company?->bank_account_name ?: ($company?->legal_name ?: $companyName),
                'account_number' => $company?->bank_account_number ?: null,
                'swift_code' => $company?->bank_swift_code ?: null,
            ],
            'logoDataUri' => $this->resolveLogoDataUri($company?->logo_path),
            'isDownload' => $request->boolean('download'),
            'compact' => (bool) config('erp.pdf.compact_when_possible', true)
                && $invoice->items->count() <= 6
                && mb_strlen((string) $invoice->notes) < 500,
        ];

        if ($request->boolean('download')) {
            return app(BusinessDocumentPdf::class)
                ->make('invoices.pdf', $viewData)
                ->download($invoice->invoice_number.'.pdf');
        }

        return response()->view('invoices.pdf', $viewData);
    }
}
