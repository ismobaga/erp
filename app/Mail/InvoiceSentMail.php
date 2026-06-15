<?php

namespace App\Mail;

use App\Models\Company;
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

    public function __construct(public readonly Invoice $invoice, public readonly Company $company)
    {
        // $company = currentCompany();
        app()->instance('currentCompany', $company);
        $this->companyName = $company?->name ?? config('app.name', 'ERP');
        $this->companyEmail = $company?->email ?? config('mail.from.address', 'noreply@erp.local');
        $this->formattedTotal = 'FCFA ' . number_format((float) $invoice->total, 0, '.', ' ');
        $this->formattedDueDate = $invoice->due_date?->format('d/m/Y') ?? '—';


        $client = $invoice->client()
            ->withoutGlobalScopes()
            ->first();
        $this->portalUrl = $client
            ? route('portal.invoice', ['token' => $client->ensurePlainPortalToken(), 'invoice' => $invoice])
            : null;
    }
    public function __wakeup(): void
    {
        app()->instance('currentCompany', $this->company);
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            from: $this->companyEmail,
            subject: 'Facture ' . $this->invoice->invoice_number . ' – ' . $this->formattedTotal,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.invoice-sent',
        );
    }
}
