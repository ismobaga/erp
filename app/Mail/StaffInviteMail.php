<?php

namespace App\Mail;

use App\Models\Company;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class StaffInviteMail extends Mailable
{
    use Queueable;
    use SerializesModels {
        __wakeup as restoreModels;
    }

    public string $companyName;
    public string $companyEmail;
    public string $userName;
    public string $userEmail;
    public string $loginUrl;

    public function __construct(
        public readonly User $user,
        public readonly Company $company,
        public readonly string $temporaryPassword,
        public readonly string $roleLabel,
    ) {
        app()->instance('currentCompany', $company);

        $this->companyName = $company->name ?? config('app.name', 'ERP');
        $this->companyEmail = $company->email ?? config('mail.from.address', 'noreply@erp.local');
        $this->userName = $user->name;
        $this->userEmail = $user->email;
        $this->loginUrl = url('/admin/login');
    }

    public function __wakeup(): void
    {
        // Company has no tenant scope — restore it first, then set context,
        // then restore User via the trait.
        if (! $this->company instanceof Company) {
            $this->company = Company::find($this->company->id);
        }

        app()->instance('currentCompany', $this->company);

        $this->restoreModels();
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
        return new Content(view: 'mail.staff-invite');
    }
}
