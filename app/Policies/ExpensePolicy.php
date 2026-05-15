<?php

namespace App\Policies;

use App\Models\Expense;
use App\Models\User;

class ExpensePolicy
{
    public function view(User $user, Expense $expense): bool
    {
        $company = currentCompany();

        return $user->canAny(['expenses.view', 'reports.view'])
            && $company !== null
            && (int) $expense->company_id === (int) $company->id;
    }
}
