<?php

namespace App\Models;

use App\Models\Concerns\HasCompanyScope;
use App\Services\AuditTrailService;
use Crommix\Core\Contracts\HasTenantScope;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Validation\ValidationException;

#[Fillable([
    'invoice_id',
    'credit_number',
    'issue_date',
    'amount',
    'reason',
    'status',
    'created_by',
    'updated_by',
])]
class CreditNote extends Model implements HasTenantScope
{
    use HasCompanyScope;

    public function saveQuietly(array $options = []): bool
    {
        if ($this->isDirty(['company_id', 'invoice_id', 'issue_date', 'amount'])) {
            return $this->save($options);
        }

        return parent::saveQuietly($options);
    }

    public function noteRecords(): MorphMany
    {
        return $this->morphMany(Note::class, 'notable')->orderByDesc('noted_at')->orderByDesc('id');
    }

    protected function casts(): array
    {
        return [
            'issue_date' => 'date',
            'amount' => 'decimal:2',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (CreditNote $creditNote): void {
            FinancialPeriod::ensureDateIsOpen($creditNote->issue_date, 'credit note');

            if (blank($creditNote->credit_number)) {
                throw ValidationException::withMessages([
                    'credit_number' => 'A credit note number is required.',
                ]);
            }

            $duplicate = static::query()
                ->where('credit_number', $creditNote->credit_number)
                ->when($creditNote->exists, fn ($q) => $q->whereKeyNot($creditNote->getKey()))
                ->exists();

            if ($duplicate) {
                throw ValidationException::withMessages([
                    'credit_number' => 'This credit note number is already in use.',
                ]);
            }

            if ((float) $creditNote->amount <= 0) {
                throw ValidationException::withMessages([
                    'amount' => 'Credit note amount must be positive.',
                ]);
            }

            if (blank($creditNote->reason)) {
                throw ValidationException::withMessages([
                    'reason' => 'A credit note reason is required for auditability.',
                ]);
            }

            $creditNote->status = blank($creditNote->status) ? 'issued' : $creditNote->status;

            $invoice = $creditNote->invoice_id
                ? Invoice::withoutCompanyScope()->find($creditNote->invoice_id)
                : null;

            if (! $invoice) {
                return;
            }

            if (blank($creditNote->company_id)) {
                $creditNote->company_id = (int) $invoice->company_id;
            }

            if ((int) $invoice->company_id !== (int) $creditNote->company_id) {
                throw ValidationException::withMessages([
                    'invoice_id' => 'The selected invoice does not belong to this company.',
                ]);
            }

            if (app()->bound('currentCompany') && (int) app('currentCompany')->id !== (int) $creditNote->company_id) {
                throw ValidationException::withMessages([
                    'company_id' => 'The selected relations do not belong to the current company context.',
                ]);
            }

            if ($invoice->status === 'cancelled') {
                throw ValidationException::withMessages([
                    'invoice_id' => 'Cancelled invoices cannot receive credit notes.',
                ]);
            }

            if (
                $creditNote->issue_date !== null
                && $invoice->issue_date !== null
                && now()->parse((string) $creditNote->issue_date)->lt(now()->parse((string) $invoice->issue_date))
            ) {
                throw ValidationException::withMessages([
                    'issue_date' => 'Credit note date cannot be earlier than the related invoice issue date.',
                ]);
            }

            $autoIssueLimit = max(0, (float) config('erp.billing.credit_note_auto_issue_limit', 0));

            if ($autoIssueLimit > 0 && (float) $creditNote->amount > $autoIssueLimit && ! in_array($creditNote->status, ['approved', 'void'], true)) {
                $creditNote->status = 'pending_approval';
            }

            $otherCredits = (float) $invoice->creditNotes()
                ->when($creditNote->exists, fn ($query) => $query->whereKeyNot($creditNote->getKey()))
                ->sum('amount');

            $invoiceCap = max(
                (float) $invoice->total,
                (float) $invoice->subtotal + (float) $invoice->tax_total,
                (float) $invoice->balance_due,
            );

            if ($otherCredits + (float) $creditNote->amount > $invoiceCap) {
                throw ValidationException::withMessages([
                    'amount' => 'Credit notes cannot exceed the original invoice total.',
                ]);
            }
        });

        static::saved(function (CreditNote $creditNote): void {
            $creditNote->invoice?->refreshCreditBalance();

            if ($creditNote->wasRecentlyCreated) {
                $action = $creditNote->status === 'pending_approval'
                    ? 'credit_note_pending_approval'
                    : 'credit_note_issued';

                app(AuditTrailService::class)->log($action, $creditNote, [
                    'invoice_number' => $creditNote->invoice?->invoice_number,
                    'credit_number' => $creditNote->credit_number,
                    'amount' => (float) $creditNote->amount,
                    'reason' => $creditNote->reason,
                    'status' => $creditNote->status,
                ], $creditNote->created_by);
            }

            if ($creditNote->wasChanged('status') && $creditNote->status === 'approved') {
                app(AuditTrailService::class)->log('credit_note_approved', $creditNote, [
                    'invoice_number' => $creditNote->invoice?->invoice_number,
                    'credit_number' => $creditNote->credit_number,
                    'amount' => (float) $creditNote->amount,
                    'reason' => $creditNote->reason,
                    'status' => $creditNote->status,
                ], $creditNote->updated_by ?? $creditNote->created_by);
            }
        });

        static::deleted(function (CreditNote $creditNote): void {
            $creditNote->invoice?->refreshCreditBalance();
        });
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approve(?int $userId = null): void
    {
        $this->forceFill([
            'status' => 'approved',
            'updated_by' => $userId,
        ])->save();
    }
}
