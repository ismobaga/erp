<?php

namespace App\Http\Controllers;

use App\Models\CompanySetting;
use App\Models\Invoice;
use App\Services\AuditTrailService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class InvoicePdfController extends Controller
{
    public function __invoke(Request $request, Invoice $invoice): Response
    {
        abort_unless(auth()->user()?->canAny(['invoices.view', 'reports.view']), 403);

        $invoice->loadMissing(['client', 'items.service', 'quote']);

        app(AuditTrailService::class)->log('invoice_pdf_accessed', $invoice, [
            'invoice_number' => $invoice->invoice_number,
            'download' => $request->boolean('download'),
        ], auth()->id());

        $company = CompanySetting::query()->first();
        $companyName = $company?->company_name ?: config('app.name');

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
        ];

        if ($request->boolean('download')) {
            return Pdf::loadView('invoices.pdf', $viewData)
                ->setOption([
                    'isHtml5ParserEnabled' => true,
                    'isRemoteEnabled' => true,
                    'dpi' => 120,
                    'defaultFont' => 'DejaVu Sans',
                ])
                ->setPaper('a4')
                ->download($invoice->invoice_number . '.pdf');
        }

        return response()->view('invoices.pdf', $viewData);
    }

    protected function resolveLogoDataUri(?string $path): ?string
    {
        if (blank($path)) {
            return null;
        }

        if (Str::startsWith($path, ['data:', 'http://', 'https://'])) {
            return $path;
        }

        $normalizedPath = ltrim($path, '/');
        $candidates = [
            storage_path('app/' . $normalizedPath),
            storage_path('app/public/' . Str::after($normalizedPath, 'public/')),
            public_path($normalizedPath),
        ];

        foreach ($candidates as $candidate) {
            if (!is_file($candidate)) {
                continue;
            }

            $mime = mime_content_type($candidate) ?: 'image/png';
            $content = file_get_contents($candidate);

            if ($content === false) {
                continue;
            }

            return 'data:' . $mime . ';base64,' . base64_encode($content);
        }

        return null;
    }
}
