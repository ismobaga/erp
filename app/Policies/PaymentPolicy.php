<?php

namespace App\Policies;

use App\Models\Payment;
use App\Models\User;

class PaymentPolicy
{
    public function view(User $user, Payment $payment): bool
    {
        $company = currentCompany();

        return $user->canAny(['payments.view', 'reports.view'])
            && $company !== null
            && (int) $payment->company_id === (int) $company->id;
    }
}
