<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class StaffInviteMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $companyName;
    public string $companyEmail;
    public string $loginUrl;

    public function __construct(
        public readonly User $user,
        public readonly string $temporaryPassword,
        public readonly string $roleLabel,
    ) {
        $company = currentCompany();
        $this->companyName = $company?->name ?? config('app.name', 'ERP');
        $this->companyEmail = $company?->email ?? config('mail.from.address', 'noreply@erp.local');
        $this->loginUrl = url('/admin/login');
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            from: $this->companyEmail,
            subject: 'Bienvenue dans ' . $this->companyName . ' – Votre accès collaborateur',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.staff-invite',
        );
    }
}
