<?php

namespace App\Http\Controllers;

use App\Models\Quote;
use App\Services\AuditTrailService;
use App\Services\Pdf\BusinessDocumentPdf;
use App\Support\ResolvesLogoDataUri;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class QuotePdfController extends Controller
{
    use ResolvesLogoDataUri;

    public function __invoke(Request $request, Quote $quote): Response
    {
        $this->authorize('view', $quote);

        $quote->loadMissing(['client', 'items.service', 'invoice']);

        app(AuditTrailService::class)->log('quote_pdf_accessed', $quote, [
            'quote_number' => $quote->quote_number,
            'download' => $request->boolean('download'),
        ], auth()->id());

        $company = currentCompany();

        $viewData = [
            'quote' => $quote,
            'company' => $company,
            'logoDataUri' => $this->resolveLogoDataUri($company?->logo_path),
            'isDownload' => $request->boolean('download'),
            'compact' => (bool) config('erp.pdf.compact_when_possible', true)
                && $quote->items->count() <= 6
                && mb_strlen((string) $quote->notes) < 500,
        ];

        if ($request->boolean('download')) {
            return app(BusinessDocumentPdf::class)
                ->make('quotes.pdf', $viewData)
                ->download($quote->quote_number.'.pdf');
        }

        return response()->view('quotes.pdf', $viewData);
    }
}
