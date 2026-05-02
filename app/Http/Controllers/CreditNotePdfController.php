<?php

namespace App\Http\Controllers;

use App\Models\CreditNote;
use App\Services\AuditTrailService;
use App\Support\ResolvesLogoDataUri;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CreditNotePdfController extends Controller
{
    use ResolvesLogoDataUri;

    public function __invoke(Request $request, CreditNote $creditNote): Response
    {
        abort_unless(auth()->user()?->canAny(['credit_notes.view', 'reports.view']), 403);

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
            return Pdf::loadView('credit-notes.pdf', $viewData)
                ->setOption([
                    'isHtml5ParserEnabled' => true,
                    'isRemoteEnabled' => true,
                    'dpi' => 120,
                    'defaultFont' => 'DejaVu Sans',
                ])
                ->setPaper('a4')
                ->download($creditNote->credit_number . '.pdf');
        }

        return response()->view('credit-notes.pdf', $viewData);
    }
}
