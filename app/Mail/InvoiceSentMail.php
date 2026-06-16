<?php

namespace App\Mail;

use App\Models\Company;
use App\Models\Invoice;
use App\Services\Pdf\BusinessDocumentPdf;
use App\Support\ResolvesLogoDataUri;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class InvoiceSentMail extends Mailable
{
    use Queueable;
    use ResolvesLogoDataUri;
    use SerializesModels {
        __unserialize as restoreModels;
    }

    public string $companyName;
    public string $companyEmail;
    public string $invoiceNumber;
    public string $formattedTotal;
    public string $formattedDueDate;
    public string $clientName;
    public ?string $portalUrl;

    public function __construct(public readonly Invoice $invoice, public readonly Company $company)
    {
        app()->instance('currentCompany', $company);

        $this->companyName = $company->name ?? config('app.name', 'ERP');
        $this->companyEmail = $company->email ?? config('mail.from.address', 'noreply@erp.local');
        $this->invoiceNumber = $invoice->invoice_number;
        $this->formattedTotal = 'FCFA ' . number_format((float) $invoice->total, 0, '.', ' ');
        $this->formattedDueDate = $invoice->due_date?->format('d/m/Y') ?? '—';

        $client = $invoice->client()->withoutGlobalScopes()->first();
        $this->clientName = $client?->contact_name ?? $client?->company_name ?? 'Client';
        $this->portalUrl = $client
            ? route('portal.invoice', ['token' => $client->ensurePlainPortalToken(), 'invoice' => $invoice])
            : null;
    }

    public function __unserialize(array $values): void
    {
        // Company has no tenant scope — restore it first so context can be established
        // before SerializesModels restores Invoice (which uses HasCompanyScope).
        if (isset($values['company']) && ! ($values['company'] instanceof Company)) {
            $values['company'] = Company::find($values['company']->id);
        }

        app()->instance('currentCompany', $values['company']);

        // Restore remaining ModelIdentifiers (Invoice) with company context in place.
        $this->restoreModels($values);
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            from: $this->companyEmail,
            subject: "Facture {$this->invoiceNumber} – {$this->formattedTotal}",
        );
    }

    public function content(): Content
    {
        return new Content(view: 'mail.invoice-sent');
    }

    public function attachments(): array
    {
        $this->invoice->loadMissing(['client', 'items.service', 'quote']);

        $pdfBuilder = app(BusinessDocumentPdf::class);
        $companyName = $this->company->name ?: config('app.name');

        $viewData = [
            'invoice'     => $this->invoice,
            'company'     => $this->company,
            'bankDetails' => [
                'bank_name'      => $this->company->bank_name,
                'account_name'   => $this->company->bank_account_name ?: ($this->company->legal_name ?: $companyName),
                'account_number' => $this->company->bank_account_number,
                'swift_code'     => $this->company->bank_swift_code,
            ],
            'logoDataUri' => $this->resolveLogoDataUri($this->company->logo_path),
            'isDownload'  => true,
            'compact'     => $pdfBuilder->shouldUseCompactForInvoice($this->invoice),
        ];

        $pdfContent = $pdfBuilder->make('invoices.pdf', $viewData)->output();

        return [
            Attachment::fromData(fn () => $pdfContent, $this->invoiceNumber . '.pdf')
                ->withMime('application/pdf'),
        ];
    }
}
