<?php

namespace App\Observers;

use App\Models\Expense;
use App\Services\LedgerPostingService;
use Illuminate\Support\Facades\Log;
use Throwable;

class ExpenseObserver
{
    public function __construct(
        private readonly LedgerPostingService $posting,
    ) {}

    /**
     * Auto-post a journal entry when an expense is approved.
     */
    public function updated(Expense $expense): void
    {
        if (!$expense->wasChanged('approval_status')) {
            return;
        }

        if ($expense->approval_status !== 'approved') {
            return;
        }

        try {
            $this->posting->postExpense($expense, $expense->approved_by);
        } catch (Throwable $e) {
            // Best-effort
            Log::error('ExpenseObserver: failed to post journal entry', [
                'expense_id' => $expense->id,
                'exception' => $e->getMessage(),
            ]);
        }
    }
}
