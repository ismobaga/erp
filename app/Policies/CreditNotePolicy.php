<?php

namespace App\Policies;

use App\Models\CreditNote;
use App\Models\User;

class CreditNotePolicy
{
    public function view(User $user, CreditNote $creditNote): bool
    {
        $company = currentCompany();

        return $user->canAny(['credit_notes.view', 'reports.view'])
            && $company !== null
            && (int) $creditNote->company_id === (int) $company->id;
    }
}
