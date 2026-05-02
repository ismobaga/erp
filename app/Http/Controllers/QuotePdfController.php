<?php

namespace App\Http\Controllers;

use App\Models\Quote;
use App\Services\AuditTrailService;
use App\Support\ResolvesLogoDataUri;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class QuotePdfController extends Controller
{
    use ResolvesLogoDataUri;

    public function __invoke(Request $request, Quote $quote): Response
    {
        abort_unless(auth()->user()?->canAny(['quotes.view', 'reports.view']), 403);

        $quote->loadMissing(['client', 'items.service', 'invoice']);

        app(AuditTrailService::class)->log('quote_pdf_accessed', $quote, [
            'quote_number' => $quote->quote_number,
            'download' => $request->boolean('download'),
        ], auth()->id());

        $company = currentCompany();
        $companyName = $company?->name ?: config('app.name');

        $viewData = [
            'quote' => $quote,
            'company' => $company,
            'logoDataUri' => $this->resolveLogoDataUri($company?->logo_path),
            'isDownload' => $request->boolean('download'),
        ];

        if ($request->boolean('download')) {
            return Pdf::loadView('quotes.pdf', $viewData)
                ->setOption([
                    'isHtml5ParserEnabled' => true,
                    'isRemoteEnabled' => true,
                    'dpi' => 120,
                    'defaultFont' => 'DejaVu Sans',
                ])
                ->setPaper('a4')
                ->download($quote->quote_number . '.pdf');
        }

        return response()->view('quotes.pdf', $viewData);
    }
}
