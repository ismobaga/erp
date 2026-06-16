<?php

namespace App\Mail;

use App\Models\Company;
use App\Models\Invoice;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class InvoiceReminderMail extends Mailable
{
    use Queueable;
    use SerializesModels {
        __unserialize as restoreModels;
    }

    public string $companyName;
    public string $companyEmail;
    public string $invoiceNumber;
    public string $formattedAmount;
    public string $formattedDueDate;
    public string $clientName;
    public ?string $invoiceNotes;

    public function __construct(public readonly Invoice $invoice, public readonly Company $company)
    {
        app()->instance('currentCompany', $company);

        $this->companyName = $company->name ?? config('app.name', 'ERP');
        $this->companyEmail = $company->email ?? config('mail.from.address', 'noreply@erp.local');
        $this->invoiceNumber = $invoice->invoice_number;
        $this->formattedAmount = 'FCFA ' . number_format((float) $invoice->balance_due, 0, '.', ' ');
        $this->formattedDueDate = $invoice->due_date?->format('d/m/Y') ?? '—';
        $this->invoiceNotes = $invoice->notes ?: null;

        $client = $invoice->client()->withoutGlobalScopes()->first();
        $this->clientName = $client?->contact_name ?? $client?->company_name ?? 'Client';
    }

    public function __unserialize(array $values): void
    {
        if (isset($values['company']) && ! ($values['company'] instanceof Company)) {
            $values['company'] = Company::find($values['company']->id);
        }

        if (! ($values['company'] instanceof Company)) {
            throw new \RuntimeException('Company no longer exists; mail job discarded.');
        }

        app()->instance('currentCompany', $values['company']);

        $this->restoreModels($values);
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            from: $this->companyEmail,
            subject: "[Rappel de paiement] Facture {$this->invoiceNumber} – {$this->formattedAmount}",
        );
    }

    public function content(): Content
    {
        return new Content(view: 'mail.invoice-reminder');
    }
}
