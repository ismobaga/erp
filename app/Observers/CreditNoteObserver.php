<?php

namespace App\Observers;

use App\Models\CreditNote;
use App\Services\LedgerPostingService;
use Illuminate\Support\Facades\Log;
use Throwable;

class CreditNoteObserver
{
    public function __construct(
        private readonly LedgerPostingService $posting,
    ) {
    }

    /**
     * Auto-post a journal entry when a credit note is created with status
     * 'issued', or when it transitions to 'approved'.
     */
    public function created(CreditNote $creditNote): void
    {
        if (!in_array($creditNote->status, ['issued', 'approved'], true)) {
            return;
        }

        try {
            $this->posting->postCreditNote($creditNote, $creditNote->created_by);
        } catch (Throwable $e) {
            Log::error('CreditNoteObserver: failed to post journal entry on create', [
                'credit_note_id' => $creditNote->id,
                'exception' => $e->getMessage(),
            ]);
        }
    }

    public function updated(CreditNote $creditNote): void
    {
        if (!$creditNote->wasChanged('status')) {
            return;
        }

        if ($creditNote->status !== 'approved') {
            return;
        }

        // Only post if not already posted from 'created' (status was pending_approval or draft before)
        $wasPostedOnCreate = in_array($creditNote->getOriginal('status'), ['issued', 'approved'], true);

        if ($wasPostedOnCreate) {
            return;
        }

        try {
            $this->posting->postCreditNote($creditNote, $creditNote->updated_by);
        } catch (Throwable $e) {
            Log::error('CreditNoteObserver: failed to post journal entry on approve', [
                'credit_note_id' => $creditNote->id,
                'exception' => $e->getMessage(),
            ]);
        }
    }
}
