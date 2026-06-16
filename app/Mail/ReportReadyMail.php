<?php

namespace App\Mail;

use App\Models\Company;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

class ReportReadyMail extends Mailable
{
    use Queueable;
    use SerializesModels {
        __unserialize as restoreModels;
    }

    public string $companyName;
    public string $companyEmail;

    public function __construct(
        public readonly string $reportPath,
        public readonly string $generatedAt,
        public readonly Company $company,
    ) {
        app()->instance('currentCompany', $company);

        $this->companyName = $company->name ?? config('app.name', 'ERP');
        $this->companyEmail = $company->email ?? config('mail.from.address', 'noreply@erp.local');
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
            subject: "[{$this->companyName}] Votre rapport programmé est prêt",
        );
    }

    public function content(): Content
    {
        return new Content(view: 'mail.report-ready');
    }

    public function attachments(): array
    {
        if (! Storage::exists($this->reportPath)) {
            return [];
        }

        return [
            Attachment::fromStorageDisk('local', $this->reportPath)
                ->as(basename($this->reportPath)),
        ];
    }
}
