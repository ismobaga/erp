<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Attachment;
use App\Models\Client;
use App\Models\Company;
use App\Models\Invoice;
use App\Models\PortalTicket;
use App\Models\Project;
use App\Models\Quote;
use App\Models\WhatsappConversation;
use App\Services\AuditTrailService;
use App\Support\ResolvesLogoDataUri;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class ClientPortalController extends Controller
{
    use ResolvesLogoDataUri;

    /** Supported locale codes for the portal language switcher. */
    private const SUPPORTED_LOCALES = ['fr', 'en'];

    public function index(string $token): Response
    {
        $this->applyPortalLocale();
        $client = $this->resolveClientByToken($token);

        $company = $this->resolveCompany($client);

        $invoices = Invoice::withoutCompanyScope()
            ->where('client_id', $client->id)
            ->with(['items'])
            ->orderByDesc('issue_date')
            ->get();

        return response()->view('portal.index', [
            'client'  => $client,
            'company' => $company,
            'invoices' => $invoices,
            'token'   => $token,
        ]);
    }

    // ── Invoices ───────────────────────────────────────────────────────────────

    public function showInvoice(string $token, Invoice $invoice): Response
    {
        $this->applyPortalLocale();
        $client = $this->resolveClientByToken($token);

        // Ensure this invoice belongs to the authenticated client.
        abort_unless((int) $invoice->client_id === (int) $client->id, 404);

        $invoice->loadMissing(['client', 'items.service', 'payments']);

        $company = $this->resolveCompany($client);
        $companyName = $company?->name ?: config('app.name');

        $viewData = [
            'invoice' => $invoice,
            'client'  => $client,
            'company' => $company,
            'token'   => $token,
            'bankDetails' => [
                'bank_name'      => $company?->bank_name ?: null,
                'account_name'   => $company?->bank_account_name ?: ($company?->legal_name ?: $companyName),
                'account_number' => $company?->bank_account_number ?: null,
                'swift_code'     => $company?->bank_swift_code ?: null,
            ],
            'logoDataUri' => $this->resolveLogoDataUri($company?->logo_path),
        ];

        return response()->view('portal.invoice', $viewData);
    }

    public function downloadPdf(string $token, Invoice $invoice): Response
    {
        $client = $this->resolveClientByToken($token);
        abort_unless((int) $invoice->client_id === (int) $client->id, 404);

        $invoice->loadMissing(['client', 'items.service', 'quote']);

        $company = $this->resolveCompany($client);
        $companyName = $company?->name ?: config('app.name');

        $viewData = [
            'invoice' => $invoice,
            'company' => $company,
            'bankDetails' => [
                'bank_name'      => $company?->bank_name ?: null,
                'account_name'   => $company?->bank_account_name ?: ($company?->legal_name ?: $companyName),
                'account_number' => $company?->bank_account_number ?: null,
                'swift_code'     => $company?->bank_swift_code ?: null,
            ],
            'logoDataUri' => $this->resolveLogoDataUri($company?->logo_path),
            'isDownload'  => true,
        ];

        return Pdf::loadView('invoices.pdf', $viewData)
            ->setOption([
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled'      => true,
                'dpi'                  => 120,
                'defaultFont'          => 'DejaVu Sans',
            ])
            ->setPaper('a4')
            ->download($invoice->invoice_number . '.pdf');
    }

    // ── Quotes ─────────────────────────────────────────────────────────────────

    public function quotes(string $token): Response
    {
        $this->applyPortalLocale();
        $client  = $this->resolveClientByToken($token);
        $company = $this->resolveCompany($client);

        $quotes = Quote::withoutCompanyScope()
            ->where('client_id', $client->id)
            ->orderByDesc('issue_date')
            ->get();

        return response()->view('portal.quotes', [
            'client'  => $client,
            'company' => $company,
            'quotes'  => $quotes,
            'token'   => $token,
        ]);
    }

    public function showQuote(string $token, Quote $quote): Response
    {
        $this->applyPortalLocale();
        $client = $this->resolveClientByToken($token);
        abort_unless((int) $quote->client_id === (int) $client->id, 404);

        $quote->loadMissing(['client', 'items.service', 'invoice']);
        $company = $this->resolveCompany($client);

        return response()->view('portal.quote', [
            'client'  => $client,
            'company' => $company,
            'quote'   => $quote,
            'token'   => $token,
        ]);
    }

    public function approveQuote(string $token, Quote $quote): RedirectResponse
    {
        $client = $this->resolveClientByToken($token);
        abort_unless((int) $quote->client_id === (int) $client->id, 404);
        abort_unless($quote->canBeAccepted(), 422, 'This quote cannot be approved in its current status.');

        $quote->convertToInvoice();

        app(AuditTrailService::class)->log('portal_quote_approved', $quote, [
            'quote_number' => $quote->quote_number,
            'client_id'    => $client->id,
        ]);

        return redirect()->route('portal.quotes', ['token' => $token])
            ->with('portal_success', __('erp.portal.quote_approved'));
    }

    public function rejectQuote(string $token, Quote $quote, Request $request): RedirectResponse
    {
        $client = $this->resolveClientByToken($token);
        abort_unless((int) $quote->client_id === (int) $client->id, 404);
        abort_unless(in_array($quote->status, ['draft', 'sent', 'expired'], true), 422, 'This quote cannot be rejected in its current status.');

        $quote->forceFill(['status' => 'rejected'])->save();

        app(AuditTrailService::class)->log('portal_quote_rejected', $quote, [
            'quote_number' => $quote->quote_number,
            'client_id'    => $client->id,
            'reason'       => $request->input('reason'),
        ]);

        return redirect()->route('portal.quotes', ['token' => $token])
            ->with('portal_success', __('erp.portal.quote_rejected'));
    }

    // ── Documents ──────────────────────────────────────────────────────────────

    public function documents(string $token): Response
    {
        $this->applyPortalLocale();
        $client  = $this->resolveClientByToken($token);
        $company = $this->resolveCompany($client);

        // Collect documents attached directly to the client.
        $clientDocs = Attachment::withoutCompanyScope()
            ->where('attachable_type', Client::class)
            ->where('attachable_id', $client->id)
            ->orderByDesc('created_at')
            ->get();

        // Documents on projects belonging to this client.
        $projectIds = Project::withoutCompanyScope()
            ->where('client_id', $client->id)
            ->pluck('id');

        $projectDocs = Attachment::withoutCompanyScope()
            ->where('attachable_type', Project::class)
            ->whereIn('attachable_id', $projectIds)
            ->orderByDesc('created_at')
            ->get();

        // Documents on invoices belonging to this client.
        $invoiceIds = Invoice::withoutCompanyScope()
            ->where('client_id', $client->id)
            ->pluck('id');

        $invoiceDocs = Attachment::withoutCompanyScope()
            ->where('attachable_type', Invoice::class)
            ->whereIn('attachable_id', $invoiceIds)
            ->orderByDesc('created_at')
            ->get();

        return response()->view('portal.documents', [
            'client'      => $client,
            'company'     => $company,
            'clientDocs'  => $clientDocs,
            'projectDocs' => $projectDocs,
            'invoiceDocs' => $invoiceDocs,
            'token'       => $token,
        ]);
    }

    // ── Projects ───────────────────────────────────────────────────────────────

    public function projects(string $token): Response
    {
        $this->applyPortalLocale();
        $client  = $this->resolveClientByToken($token);
        $company = $this->resolveCompany($client);

        $projects = Project::withoutCompanyScope()
            ->where('client_id', $client->id)
            ->with(['assignee', 'service'])
            ->orderByDesc('created_at')
            ->get();

        return response()->view('portal.projects', [
            'client'   => $client,
            'company'  => $company,
            'projects' => $projects,
            'token'    => $token,
        ]);
    }

    // ── Support Tickets ────────────────────────────────────────────────────────

    public function tickets(string $token): Response
    {
        $this->applyPortalLocale();
        $client  = $this->resolveClientByToken($token);
        $company = $this->resolveCompany($client);

        $tickets = PortalTicket::withoutCompanyScope()
            ->where('client_id', $client->id)
            ->orderByDesc('created_at')
            ->get();

        return response()->view('portal.tickets', [
            'client'  => $client,
            'company' => $company,
            'tickets' => $tickets,
            'token'   => $token,
        ]);
    }

    public function submitTicket(string $token, Request $request): RedirectResponse
    {
        $client = $this->resolveClientByToken($token);

        $validated = $request->validate([
            'subject'  => ['required', 'string', 'max:255'],
            'body'     => ['required', 'string', 'max:5000'],
            'priority' => ['nullable', 'in:normal,urgent'],
        ]);

        $company = $this->resolveCompany($client);

        PortalTicket::create([
            'company_id' => $company?->id ?? $client->company_id,
            'client_id'  => $client->id,
            'subject'    => $validated['subject'],
            'body'       => $validated['body'],
            'priority'   => $validated['priority'] ?? 'normal',
            'status'     => 'open',
        ]);

        return redirect()->route('portal.tickets', ['token' => $token])
            ->with('portal_success', __('erp.portal.ticket_submitted'));
    }

    // ── Activity History ───────────────────────────────────────────────────────

    public function activity(string $token): Response
    {
        $this->applyPortalLocale();
        $client  = $this->resolveClientByToken($token);
        $company = $this->resolveCompany($client);

        // Gather activity logs related to the client and their records.
        $invoiceIds = Invoice::withoutCompanyScope()
            ->where('client_id', $client->id)
            ->pluck('id');

        $quoteIds = Quote::withoutCompanyScope()
            ->where('client_id', $client->id)
            ->pluck('id');

        $activityLogs = ActivityLog::withoutCompanyScope()
            ->where(function ($query) use ($client, $invoiceIds, $quoteIds): void {
                $query->where(function ($q) use ($client): void {
                    $q->where('subject_type', Client::class)
                      ->where('subject_id', $client->id);
                })->orWhere(function ($q) use ($invoiceIds): void {
                    $q->where('subject_type', Invoice::class)
                      ->whereIn('subject_id', $invoiceIds);
                })->orWhere(function ($q) use ($quoteIds): void {
                    $q->where('subject_type', Quote::class)
                      ->whereIn('subject_id', $quoteIds);
                });
            })
            ->orderByDesc('created_at')
            ->limit(100)
            ->get();

        return response()->view('portal.activity', [
            'client'       => $client,
            'company'      => $company,
            'activityLogs' => $activityLogs,
            'token'        => $token,
        ]);
    }

    // ── WhatsApp Conversations ─────────────────────────────────────────────────

    public function conversations(string $token): Response
    {
        $this->applyPortalLocale();
        $client  = $this->resolveClientByToken($token);
        $company = $this->resolveCompany($client);

        $conversations = WhatsappConversation::withoutCompanyScope()
            ->where('client_id', $client->id)
            ->with(['messages'])
            ->orderByDesc('last_message_at')
            ->get();

        return response()->view('portal.conversations', [
            'client'        => $client,
            'company'       => $company,
            'conversations' => $conversations,
            'token'         => $token,
        ]);
    }

    // ── Language Switcher ──────────────────────────────────────────────────────

    public function setLanguage(string $token, Request $request): RedirectResponse
    {
        // Resolve client just to validate the token.
        $this->resolveClientByToken($token);

        $locale = $request->input('locale', 'fr');
        if (!in_array($locale, self::SUPPORTED_LOCALES, true)) {
            $locale = 'fr';
        }

        session(['portal_locale' => $locale]);

        // Redirect back to the referring page, or fall back to the portal index.
        $referer = $request->headers->get('referer');

        return $referer
            ? redirect($referer)
            : redirect()->route('portal.index', ['token' => $token]);
    }

    // ── Helpers ────────────────────────────────────────────────────────────────

    /**
     * Resolve the Company record that owns this client.
     * Falls back to the first active company for single-tenant deployments.
     */
    protected function resolveCompany(Client $client): ?Company
    {
        if ($client->company_id !== null) {
            return Company::find($client->company_id);
        }

        return Cache::remember('portal.active_company', now()->addMinutes(5), static function (): ?Company {
            return Company::query()->where('is_active', true)->first();
        });
    }

    protected function resolveClientByToken(string $token): Client
    {
        // portal_token is stored as a plain-text UUID (the random token is
        // already unforgeable; see migration 2026_05_06_200002).
        return Client::withoutCompanyScope()
            ->where('portal_token', $token)
            ->firstOrFail();
    }

    protected function applyPortalLocale(): void
    {
        $locale = session('portal_locale', 'fr');
        app()->setLocale(in_array($locale, self::SUPPORTED_LOCALES, true) ? $locale : 'fr');
    }
}
