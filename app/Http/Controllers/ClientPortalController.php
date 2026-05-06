<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Company;
use App\Models\Invoice;
use App\Support\ResolvesLogoDataUri;
use Barryvdh\DomPDF\Facade\Pdf;
use Symfony\Component\HttpFoundation\Response;

class ClientPortalController extends Controller
{
    use ResolvesLogoDataUri;

    public function index(string $token): Response
    {
        // withoutCompanyScope() is correct here: the portal is public, there
        // is no authenticated session, and portal tokens are globally unique.
        $client = Client::withoutCompanyScope()->where('portal_token', $token)->firstOrFail();

        $company = $this->resolveCompany($client);

        $invoices = Invoice::withoutCompanyScope()
            ->where('client_id', $client->id)
            ->with(['items'])
            ->orderByDesc('issue_date')
            ->get();

        return response()->view('portal.index', [
            'client' => $client,
            'company' => $company,
            'invoices' => $invoices,
            'token' => $token,
        ]);
    }

    public function showInvoice(string $token, Invoice $invoice): Response
    {
        $client = Client::withoutCompanyScope()->where('portal_token', $token)->firstOrFail();

        // Ensure this invoice belongs to the authenticated client.
        abort_unless((int) $invoice->client_id === (int) $client->id, 404);

        $invoice->loadMissing(['client', 'items.service', 'payments']);

        $company = $this->resolveCompany($client);
        $companyName = $company?->name ?: config('app.name');

        $viewData = [
            'invoice' => $invoice,
            'client' => $client,
            'company' => $company,
            'token' => $token,
            'bankDetails' => [
                'bank_name' => $company?->bank_name ?: null,
                'account_name' => $company?->bank_account_name ?: ($company?->legal_name ?: $companyName),
                'account_number' => $company?->bank_account_number ?: null,
                'swift_code' => $company?->bank_swift_code ?: null,
            ],
            'logoDataUri' => $this->resolveLogoDataUri($company?->logo_path),
        ];

        return response()->view('portal.invoice', $viewData);
    }

    public function downloadPdf(string $token, Invoice $invoice): Response
    {
        $client = Client::withoutCompanyScope()->where('portal_token', $token)->firstOrFail();
        abort_unless((int) $invoice->client_id === (int) $client->id, 404);

        $invoice->loadMissing(['client', 'items.service', 'quote']);

        $company = $this->resolveCompany($client);
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
            'isDownload' => true,
        ];

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

    /**
     * Resolve the Company record that owns this client.
     * Falls back to the first active company for single-tenant deployments.
     */
    protected function resolveCompany(Client $client): ?Company
    {
        if ($client->company_id !== null) {
            return Company::find($client->company_id);
        }

        return Company::query()->where('is_active', true)->first();
    }

}
