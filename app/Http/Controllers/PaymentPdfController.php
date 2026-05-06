<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Services\AuditTrailService;
use App\Support\ResolvesLogoDataUri;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PaymentPdfController extends Controller
{
    use ResolvesLogoDataUri;

    public function __invoke(Request $request, Payment $payment): Response
    {
        abort_unless(auth()->user()?->canAny(['payments.view', 'reports.view']), 403);

        $payment->loadMissing(['invoice.client', 'invoice.items']);

        app(AuditTrailService::class)->log('payment_receipt_accessed', $payment, [
            'reference' => $payment->reference ?: 'N/A',
            'amount' => $payment->amount,
            'download' => $request->boolean('download'),
        ], auth()->id());

        $company = currentCompany();

        $viewData = [
            'payment' => $payment,
            'company' => $company,
            'logoDataUri' => $this->resolveLogoDataUri($company?->logo_path),
            'isDownload' => $request->boolean('download'),
        ];

        $filename = 'recu-' . ($payment->reference ?: $payment->id) . '.pdf';

        if ($request->boolean('download')) {
            return Pdf::loadView('payments.pdf', $viewData)
                ->setOption([
                    'isHtml5ParserEnabled' => true,
                    'isRemoteEnabled' => true,
                    'dpi' => 120,
                    'defaultFont' => 'DejaVu Sans',

                ])
                ->setPaper('a4')
                ->download($filename);
        }

        return response()->view('payments.pdf', $viewData);
    }
}
