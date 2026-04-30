<?php

namespace App\Mail;

use App\Models\CompanySetting;
use App\Models\Invoice;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class InvoiceSentMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $companyName;

    public string $companyEmail;

    public string $formattedTotal;

    public string $formattedDueDate;

    public ?string $portalUrl;

    public function __construct(public readonly Invoice $invoice)
    {
        $company = CompanySetting::query()->first();
        $this->companyName = $company?->company_name ?? config('app.name', 'ERP');
        $this->companyEmail = $company?->email ?? config('mail.from.address', 'noreply@erp.local');
        $this->formattedTotal = 'FCFA '.number_format((float) $invoice->total, 0, '.', ' ');
        $this->formattedDueDate = $invoice->due_date?->format('d/m/Y') ?? '—';
        $this->portalUrl = $invoice->client?->portal_token
            ? route('portal.invoice', ['token' => $invoice->client->portal_token, 'invoice' => $invoice])
            : null;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            from: $this->companyEmail,
            subject: 'Facture '.$this->invoice->invoice_number.' – '.$this->formattedTotal,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.invoice-sent',
        );
    }
}
