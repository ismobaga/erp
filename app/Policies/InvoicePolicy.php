<?php

namespace App\Policies;

use App\Models\Invoice;
use App\Models\User;

class InvoicePolicy
{
    public function view(User $user, Invoice $invoice): bool
    {
        $company = currentCompany();

        return $user->canAny(['invoices.view', 'reports.view'])
            && $company !== null
            && (int) $invoice->company_id === (int) $company->id;
    }
}
