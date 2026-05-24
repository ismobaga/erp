<?php

namespace App\Models;

use App\Models\Concerns\HasCompanyScope;
use App\Services\InvoiceNumberService;
use App\Services\InvoiceService;
use Crommix\Core\Contracts\HasTenantScope;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Validation\ValidationException;

#[Fillable([
    'invoice_number',
    'client_id',
    'quote_id',
    'recurring_invoice_id',
    'issue_date',
    'due_date',
    'status',
    'subtotal',
    'discount_total',
    'tax_total',
    'total',
    'credit_total',
    'paid_total',
    'balance_due',
    'notes',
    'created_by',
    'updated_by',
])]
class Invoice extends Model implements HasTenantScope
{
    use HasCompanyScope;

    public function noteRecords(): MorphMany
    {
        return $this->morphMany(Note::class, 'notable')->orderByDesc('noted_at')->orderByDesc('id');
    }

    protected function casts(): array
    {
        return [
            'issue_date' => 'date',
            'due_date' => 'date',
            'subtotal' => 'decimal:2',
            'discount_total' => 'decimal:2',
            'tax_total' => 'decimal:2',
            'total' => 'decimal:2',
            'credit_total' => 'decimal:2',
            'paid_total' => 'decimal:2',
            'balance_due' => 'decimal:2',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (Invoice $invoice): void {
            FinancialPeriod::ensureDateIsOpen($invoice->issue_date, 'invoice');
            $invoice->ensureCompanyRelationsAreValid();

            if ($invoice->exists && $invoice->isDirty('invoice_number')) {
                throw ValidationException::withMessages([
                    'invoice_number' => 'Invoice numbers are immutable once assigned.',
                ]);
            }
        });

        static::creating(function (Invoice $invoice): void {
            if (blank($invoice->invoice_number)) {
                $invoice->invoice_number = app(InvoiceNumberService::class)->generateForCompany(
                    $invoice->issue_date,
                    $invoice->company_id,
                );
            }
        });

        static::deleting(function (Invoice $invoice): void {
            FinancialPeriod::ensureDateIsOpen($invoice->issue_date, 'invoice');
        });
    }

    public function saveQuietly(array $options = []): bool
    {
        if ($this->isDirty(['company_id', 'client_id', 'quote_id', 'issue_date', 'due_date', 'subtotal', 'discount_total', 'tax_total', 'total'])) {
            return $this->save($options);
        }

        return parent::saveQuietly($options);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function quote(): BelongsTo
    {
        return $this->belongsTo(Quote::class);
    }

    public function recurringInvoice(): BelongsTo
    {
        return $this->belongsTo(RecurringInvoice::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function creditNotes(): HasMany
    {
        return $this->hasMany(CreditNote::class);
    }

    public function dunningLogs(): HasMany
    {
        return $this->hasMany(DunningLog::class);
    }

    public function recalculateTotals(): void
    {
        app(InvoiceService::class)->recalculateTotals($this);
    }

    public function refreshCreditBalance(): void
    {
        app(InvoiceService::class)->refreshCreditBalance($this);
    }

    public function refreshFinancials(): void
    {
        app(InvoiceService::class)->refreshFinancials($this);
    }

    private function ensureCompanyRelationsAreValid(): void
    {
        $client = $this->client_id
            ? Client::withoutCompanyScope()->find($this->client_id)
            : null;
        $quote = $this->quote_id
            ? Quote::withoutCompanyScope()->find($this->quote_id)
            : null;
        $resolvedCompanyId = $this->company_id
            ?? $client?->company_id
            ?? $quote?->company_id;

        if ($resolvedCompanyId !== null && blank($this->company_id)) {
            $this->company_id = (int) $resolvedCompanyId;
        }

        if (app()->bound('currentCompany') && $resolvedCompanyId !== null && (int) app('currentCompany')->id !== (int) $resolvedCompanyId) {
            throw ValidationException::withMessages([
                'company_id' => 'The selected relations do not belong to the current company context.',
            ]);
        }

        if ($client && $resolvedCompanyId !== null && (int) $client->company_id !== (int) $resolvedCompanyId) {
            throw ValidationException::withMessages([
                'client_id' => 'The selected client does not belong to this company.',
            ]);
        }

        if ($quote && $resolvedCompanyId !== null && (int) $quote->company_id !== (int) $resolvedCompanyId) {
            throw ValidationException::withMessages([
                'quote_id' => 'The selected quote does not belong to this company.',
            ]);
        }

        if ($quote && (int) $quote->client_id !== (int) $this->client_id) {
            throw ValidationException::withMessages([
                'quote_id' => 'The selected quote does not belong to the selected client.',
            ]);
        }
    }
}
