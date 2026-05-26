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
use App\Services\Pdf\BusinessDocumentPdf;
use App\Support\ResolvesLogoDataUri;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ClientPortalController extends Controller
{
    use ResolvesLogoDataUri;

    /** Supported locale codes for the portal language switcher. */
    private const SUPPORTED_LOCALES = ['fr', 'en'];

    private const PORTAL_PAGE_SIZE = 25;

    public function index(string $token): Response
    {
        $this->applyPortalLocale();
        $client = $this->resolveClientByToken($token);

        $company = $this->resolveCompany($client);

        $invoices = Invoice::withoutCompanyScope()
            ->where('company_id', $client->company_id)
            ->where('client_id', $client->id)
            ->with(['items'])
            ->orderByDesc('issue_date')
            ->simplePaginate(self::PORTAL_PAGE_SIZE);

        return response()->view('portal.index', [
            'client' => $client,
            'company' => $company,
            'invoices' => $invoices,
            'token' => $token,
        ]);
    }

    // ── Invoices ───────────────────────────────────────────────────────────────

    public function showInvoice(string $token, int $invoice): Response
    {
        $this->applyPortalLocale();
        $client = $this->resolveClientByToken($token);

        $invoice = $this->resolvePortalInvoice($client, $invoice);

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

    public function downloadPdf(string $token, int $invoice): Response
    {
        $client = $this->resolveClientByToken($token);
        $invoice = $this->resolvePortalInvoice($client, $invoice);

        $invoice->loadMissing(['client', 'items.service', 'quote']);

        $pdf = app(BusinessDocumentPdf::class);
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
            'compact' => $pdf->shouldUseCompactForInvoice($invoice),
        ];

        return $pdf
            ->make('invoices.pdf', $viewData)
            ->download($invoice->invoice_number.'.pdf');
    }

    // ── Quotes ─────────────────────────────────────────────────────────────────

    public function quotes(string $token): Response
    {
        $this->applyPortalLocale();
        $client = $this->resolveClientByToken($token);
        $company = $this->resolveCompany($client);

        $quotes = Quote::withoutCompanyScope()
            ->where('company_id', $client->company_id)
            ->where('client_id', $client->id)
            ->orderByDesc('issue_date')
            ->simplePaginate(self::PORTAL_PAGE_SIZE);

        return response()->view('portal.quotes', [
            'client' => $client,
            'company' => $company,
            'quotes' => $quotes,
            'token' => $token,
        ]);
    }

    public function showQuote(string $token, int $quote): Response
    {
        $this->applyPortalLocale();
        $client = $this->resolveClientByToken($token);
        $quote = $this->resolvePortalQuote($client, $quote);

        $quote->loadMissing(['client', 'items.service', 'invoice']);
        $company = $this->resolveCompany($client);

        return response()->view('portal.quote', [
            'client' => $client,
            'company' => $company,
            'quote' => $quote,
            'token' => $token,
        ]);
    }

    public function approveQuote(string $token, int $quote): RedirectResponse
    {
        $client = $this->resolveClientByToken($token);
        $quote = $this->resolvePortalQuote($client, $quote);
        abort_unless($quote->canBeAccepted(), 422, 'This quote cannot be approved in its current status.');

        $quote->convertToInvoice();

        app(AuditTrailService::class)->log('portal_quote_approved', $quote, [
            'quote_number' => $quote->quote_number,
            'client_id' => $client->id,
        ]);

        return redirect()->route('portal.quotes', ['token' => $token])
            ->with('portal_success', __('erp.portal.quote_approved'));
    }

    public function rejectQuote(string $token, int $quote, Request $request): RedirectResponse
    {
        $client = $this->resolveClientByToken($token);
        $quote = $this->resolvePortalQuote($client, $quote);
        abort_unless(in_array($quote->status, ['draft', 'sent', 'expired'], true), 422, 'This quote cannot be rejected in its current status.');
        $validated = $request->validate([
            'reason' => ['nullable', 'string', 'max:1000'],
        ]);

        $quote->forceFill(['status' => 'rejected'])->save();

        app(AuditTrailService::class)->log('portal_quote_rejected', $quote, [
            'quote_number' => $quote->quote_number,
            'client_id' => $client->id,
            'reason' => $validated['reason'] ?? null,
        ]);

        return redirect()->route('portal.quotes', ['token' => $token])
            ->with('portal_success', __('erp.portal.quote_rejected'));
    }

    // ── Documents ──────────────────────────────────────────────────────────────

    public function documents(string $token): Response
    {
        $this->applyPortalLocale();
        $client = $this->resolveClientByToken($token);
        $company = $this->resolveCompany($client);

        // Collect documents attached directly to the client.
        $clientDocs = Attachment::withoutCompanyScope()
            ->where('company_id', $client->company_id)
            ->where('attachable_type', Client::class)
            ->where('attachable_id', $client->id)
            ->orderByDesc('created_at')
            ->simplePaginate(self::PORTAL_PAGE_SIZE, ['*'], 'client_docs_page');

        // Documents on projects belonging to this client.
        $projectIds = Project::withoutCompanyScope()
            ->where('company_id', $client->company_id)
            ->where('client_id', $client->id)
            ->pluck('id');

        $projectDocs = Attachment::withoutCompanyScope()
            ->where('company_id', $client->company_id)
            ->where('attachable_type', Project::class)
            ->whereIn('attachable_id', $projectIds)
            ->orderByDesc('created_at')
            ->simplePaginate(self::PORTAL_PAGE_SIZE, ['*'], 'project_docs_page');

        // Documents on invoices belonging to this client.
        $invoiceIds = Invoice::withoutCompanyScope()
            ->where('company_id', $client->company_id)
            ->where('client_id', $client->id)
            ->pluck('id');

        $invoiceDocs = Attachment::withoutCompanyScope()
            ->where('company_id', $client->company_id)
            ->where('attachable_type', Invoice::class)
            ->whereIn('attachable_id', $invoiceIds)
            ->orderByDesc('created_at')
            ->simplePaginate(self::PORTAL_PAGE_SIZE, ['*'], 'invoice_docs_page');

        return response()->view('portal.documents', [
            'client' => $client,
            'company' => $company,
            'clientDocs' => $clientDocs,
            'projectDocs' => $projectDocs,
            'invoiceDocs' => $invoiceDocs,
            'token' => $token,
        ]);
    }

    // ── Projects ───────────────────────────────────────────────────────────────

    public function downloadDocument(string $token, int $attachment): StreamedResponse
    {
        $this->applyPortalLocale();
        $client = $this->resolveClientByToken($token);

        // Resolve the attachment, ensuring it belongs to the client's company.
        $attachmentModel = Attachment::withoutCompanyScope()
            ->whereKey($attachment)
            ->where('company_id', $client->company_id)
            ->firstOrFail();

        // Verify ownership: the attachment must be directly linked to the client,
        // one of the client's projects, or one of the client's invoices.
        $projectIds = Project::withoutCompanyScope()
            ->where('company_id', $client->company_id)
            ->where('client_id', $client->id)
            ->pluck('id');

        $invoiceIds = Invoice::withoutCompanyScope()
            ->where('company_id', $client->company_id)
            ->where('client_id', $client->id)
            ->pluck('id');

        $isClientDoc = $attachmentModel->attachable_type === Client::class
            && (int) $attachmentModel->attachable_id === $client->id;

        $isProjectDoc = $attachmentModel->attachable_type === Project::class
            && $projectIds->contains((int) $attachmentModel->attachable_id);

        $isInvoiceDoc = $attachmentModel->attachable_type === Invoice::class
            && $invoiceIds->contains((int) $attachmentModel->attachable_id);

        abort_unless($isClientDoc || $isProjectDoc || $isInvoiceDoc, 403);

        $disk = (string) config('erp.documents.disk', 'local');
        $directory = trim((string) config('erp.documents.directory', 'attachments'), '/');
        $normalizedPath = ltrim((string) $attachmentModel->file_path, '/');

        // Path safety check.
        $diskDriver = config("filesystems.disks.{$disk}.driver", 'local');
        if ($diskDriver === 'local') {
            $realFilePath = realpath(Storage::disk($disk)->path($normalizedPath));
            $allowedDir = realpath(Storage::disk($disk)->path($directory));
            abort_unless(
                $realFilePath !== false
                && $allowedDir !== false
                && str_starts_with($realFilePath, $allowedDir.DIRECTORY_SEPARATOR),
                403
            );
        } else {
            abort_unless(
                str_starts_with($normalizedPath, $directory.'/') || $normalizedPath === $directory,
                403
            );
        }

        abort_unless(Storage::disk($disk)->exists($normalizedPath), 404);

        app(AuditTrailService::class)->log('portal_document_downloaded', $attachmentModel, [
            'client_id' => $client->id,
            'disk' => $disk,
            'path' => $normalizedPath,
        ]);

        $safeMime = $this->portalSafeMimeType($attachmentModel->mime_type);

        return response()->streamDownload(function () use ($disk, $normalizedPath): void {
            $stream = Storage::disk($disk)->readStream($normalizedPath);

            if ($stream === false) {
                return;
            }

            fpassthru($stream);
            fclose($stream);
        }, basename((string) $attachmentModel->file_name), [
            'Content-Type' => $safeMime,
            'X-Content-Type-Options' => 'nosniff',
            'Content-Disposition' => 'attachment; filename="'.addslashes(basename((string) $attachmentModel->file_name)).'"',
            'Cache-Control' => 'private, no-store, max-age=0',
            'Pragma' => 'no-cache',
        ]);
    }

    public function projects(string $token): Response
    {
        $this->applyPortalLocale();
        $client = $this->resolveClientByToken($token);
        $company = $this->resolveCompany($client);

        $projects = Project::withoutCompanyScope()
            ->where('company_id', $client->company_id)
            ->where('client_id', $client->id)
            ->with(['assignee', 'service'])
            ->orderByDesc('created_at')
            ->simplePaginate(self::PORTAL_PAGE_SIZE);

        return response()->view('portal.projects', [
            'client' => $client,
            'company' => $company,
            'projects' => $projects,
            'token' => $token,
        ]);
    }

    // ── Support Tickets ────────────────────────────────────────────────────────

    public function tickets(string $token): Response
    {
        $this->applyPortalLocale();
        $client = $this->resolveClientByToken($token);
        $company = $this->resolveCompany($client);

        $tickets = PortalTicket::withoutCompanyScope()
            ->where('company_id', $client->company_id)
            ->where('client_id', $client->id)
            ->orderByDesc('created_at')
            ->simplePaginate(self::PORTAL_PAGE_SIZE);

        return response()->view('portal.tickets', [
            'client' => $client,
            'company' => $company,
            'tickets' => $tickets,
            'token' => $token,
        ]);
    }

    public function submitTicket(string $token, Request $request): RedirectResponse
    {
        $client = $this->resolveClientByToken($token);

        $validated = $request->validate([
            'subject' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string', 'max:5000'],
            'priority' => ['nullable', 'in:normal,urgent'],
        ]);

        $company = $this->resolveCompany($client);

        PortalTicket::create([
            'company_id' => $company?->id ?? $client->company_id,
            'client_id' => $client->id,
            'subject' => $validated['subject'],
            'body' => $validated['body'],
            'priority' => $validated['priority'] ?? 'normal',
            'status' => 'open',
        ]);

        return redirect()->route('portal.tickets', ['token' => $token])
            ->with('portal_success', __('erp.portal.ticket_submitted'));
    }

    // ── Activity History ───────────────────────────────────────────────────────

    public function activity(string $token): Response
    {
        $this->applyPortalLocale();
        $client = $this->resolveClientByToken($token);
        $company = $this->resolveCompany($client);

        // Gather activity logs related to the client and their records.
        $invoiceIds = Invoice::withoutCompanyScope()
            ->where('company_id', $client->company_id)
            ->where('client_id', $client->id)
            ->pluck('id');

        $quoteIds = Quote::withoutCompanyScope()
            ->where('company_id', $client->company_id)
            ->where('client_id', $client->id)
            ->pluck('id');

        $activityLogs = ActivityLog::withoutCompanyScope()
            ->where('company_id', $client->company_id)
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
            'client' => $client,
            'company' => $company,
            'activityLogs' => $activityLogs,
            'token' => $token,
        ]);
    }

    // ── WhatsApp Conversations ─────────────────────────────────────────────────

    public function conversations(string $token): Response
    {
        $this->applyPortalLocale();
        $client = $this->resolveClientByToken($token);
        $company = $this->resolveCompany($client);

        $conversations = WhatsappConversation::withoutCompanyScope()
            ->where('company_id', $client->company_id)
            ->where('client_id', $client->id)
            // Keep portal payload bounded by loading the latest 25 messages per conversation (sent_at DESC).
            // The view re-sorts this bounded set in chronological order for chat-style display.
            ->with(['messages' => static function ($query): void {
                $query->orderByDesc('sent_at')
                    ->limit(self::PORTAL_PAGE_SIZE);
            }])
            ->orderByDesc('last_message_at')
            ->simplePaginate(self::PORTAL_PAGE_SIZE);

        return response()->view('portal.conversations', [
            'client' => $client,
            'company' => $company,
            'conversations' => $conversations,
            'token' => $token,
        ]);
    }

    // ── Language Switcher ──────────────────────────────────────────────────────

    public function setLanguage(string $token, Request $request): RedirectResponse
    {
        // Resolve client just to validate the token.
        $this->resolveClientByToken($token);

        $locale = $request->input('locale', 'fr');
        if (! in_array($locale, self::SUPPORTED_LOCALES, true)) {
            $locale = 'fr';
        }

        session(['portal_locale' => $locale]);

        // Redirect back to the referring page, or fall back to the portal index.
        // Only follow the Referer if it points to this application to prevent
        // open-redirect attacks (CWE-601).
        $referer = $request->headers->get('referer');
        $safe = $referer && str_starts_with($referer, url('/')) ? $referer : null;

        return $safe
            ? redirect($safe)
            : redirect()->route('portal.index', ['token' => $token]);
    }

    // ── Helpers ────────────────────────────────────────────────────────────────

    /**
     * Resolve the Company record that owns this client.
     * Aborts with 404 if the client has no company_id set.
     */
    protected function resolveCompany(Client $client): Company
    {
        if ($client->company_id === null) {
            abort(404);
        }

        return Company::findOrFail($client->company_id);
    }

    protected function resolveClientByToken(string $token): Client
    {
        $client = Client::withoutCompanyScope()
            ->where('portal_token_hash', hash('sha256', $token))
            ->whereNull('portal_token_revoked_at')
            ->where(function ($query): void {
                $query->whereNull('portal_token_expires_at')
                    ->orWhere('portal_token_expires_at', '>', now());
            })
            ->firstOrFail();

        if ($client->portal_token_last_used_at === null || $client->portal_token_last_used_at->lt(now()->subMinutes(5))) {
            $client->forceFill(['portal_token_last_used_at' => now()])->saveQuietly();
        }

        return $client;
    }

    protected function applyPortalLocale(): void
    {
        $locale = session('portal_locale', 'fr');
        app()->setLocale(in_array($locale, self::SUPPORTED_LOCALES, true) ? $locale : 'fr');
    }

    protected function resolvePortalInvoice(Client $client, int $invoiceId): Invoice
    {
        return Invoice::withoutCompanyScope()
            ->whereKey($invoiceId)
            ->where('company_id', $client->company_id)
            ->where('client_id', $client->id)
            ->firstOrFail();
    }

    protected function resolvePortalQuote(Client $client, int $quoteId): Quote
    {
        return Quote::withoutCompanyScope()
            ->whereKey($quoteId)
            ->where('company_id', $client->company_id)
            ->where('client_id', $client->id)
            ->firstOrFail();
    }

    private function portalSafeMimeType(?string $mimeType): string
    {
        $allowed = [
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'text/csv',
            'text/plain',
            'image/jpeg',
            'image/png',
            'application/zip',
        ];

        return ($mimeType && in_array($mimeType, $allowed, true)) ? $mimeType : 'application/octet-stream';
    }
}
