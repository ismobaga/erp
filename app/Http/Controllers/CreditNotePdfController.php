<?php

namespace App\Http\Controllers;

use App\Models\CreditNote;
use App\Services\AuditTrailService;
use App\Services\Pdf\BusinessDocumentPdf;
use App\Support\ResolvesLogoDataUri;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CreditNotePdfController extends Controller
{
    use ResolvesLogoDataUri;

    public function __invoke(Request $request, CreditNote $creditNote): Response
    {
        $this->authorize('view', $creditNote);

        $creditNote->loadMissing(['invoice.client']);

        app(AuditTrailService::class)->log('credit_note_pdf_accessed', $creditNote, [
            'credit_number' => $creditNote->credit_number,
            'download' => $request->boolean('download'),
        ], auth()->id());

        $company = currentCompany();

        $viewData = [
            'creditNote' => $creditNote,
            'company' => $company,
            'logoDataUri' => $this->resolveLogoDataUri($company?->logo_path),
            'isDownload' => $request->boolean('download'),
        ];

        if ($request->boolean('download')) {
            return app(BusinessDocumentPdf::class)
                ->make('credit-notes.pdf', $viewData)
                ->download($creditNote->credit_number.'.pdf');
        }

        return response()->view('credit-notes.pdf', $viewData);
    }
}
