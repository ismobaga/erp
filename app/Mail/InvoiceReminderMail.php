<?php

namespace App\Mail;

use App\Models\CompanySetting;
use App\Models\Invoice;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class InvoiceReminderMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $companyName;
    public string $companyEmail;
    public string $formattedAmount;
    public string $formattedDueDate;

    public function __construct(public readonly Invoice $invoice)
    {
        $company = CompanySetting::query()->first();
        $this->companyName = $company?->company_name ?? config('app.name', 'ERP');
        $this->companyEmail = $company?->email ?? config('mail.from.address', 'noreply@erp.local');
        $this->formattedAmount = 'FCFA ' . number_format((float) $invoice->balance_due, 0, '.', ' ');
        $this->formattedDueDate = $invoice->due_date?->format('d/m/Y') ?? '—';
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            from: $this->companyEmail,
            subject: '[Rappel de paiement] Facture ' . $this->invoice->invoice_number . ' – ' . $this->formattedAmount,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.invoice-reminder',
        );
    }
}
