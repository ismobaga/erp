<?php

namespace App\Policies;

use App\Models\Quote;
use App\Models\User;

class QuotePolicy
{
    public function view(User $user, Quote $quote): bool
    {
        $company = currentCompany();

        return company_feature_enabled('quotes', $company)
            && $user->canAny(['quotes.view', 'reports.view'])
            && $company !== null
            && (int) $quote->company_id === (int) $company->id;
    }
}
