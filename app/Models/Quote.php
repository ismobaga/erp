<?php

namespace App\Models;

use App\Actions\ConvertQuoteToInvoiceAction;
use App\Models\Concerns\HasCompanyScope;
use App\ValueObjects\Money;
use Carbon\CarbonInterface;
use Crommix\Core\Contracts\HasTenantScope;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Validation\ValidationException;

#[Fillable([
    'quote_number',
    'client_id',
    'issue_date',
    'valid_until',
    'status',
    'subtotal',
    'discount_total',
    'tax_total',
    'total',
    'notes',
    'created_by',
    'updated_by',
])]
class Quote extends Model implements HasTenantScope
{
    use HasCompanyScope;

    protected static function booted(): void
    {
        static::saving(function (Quote $quote): void {
            $client = $quote->client_id
                ? Client::withoutCompanyScope()->find($quote->client_id)
                : null;

            if ($client && blank($quote->company_id)) {
                $quote->company_id = (int) $client->company_id;
            }

            if ($client && filled($quote->company_id) && (int) $client->company_id !== (int) $quote->company_id) {
                throw ValidationException::withMessages([
                    'client_id' => 'The selected client does not belong to this company.',
                ]);
            }

            if ($client && app()->bound('currentCompany') && filled($quote->company_id) && (int) app('currentCompany')->id !== (int) $quote->company_id) {
                throw ValidationException::withMessages([
                    'company_id' => 'The selected relations do not belong to the current company context.',
                ]);
            }
        });
    }

    public function saveQuietly(array $options = []): bool
    {
        if ($this->isDirty(['company_id', 'client_id', 'issue_date', 'valid_until', 'subtotal', 'discount_total', 'tax_total', 'total'])) {
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
            'valid_until' => 'date',
            'subtotal' => 'decimal:2',
            'discount_total' => 'decimal:2',
            'tax_total' => 'decimal:2',
            'total' => 'decimal:2',
        ];
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(QuoteItem::class);
    }

    public function invoice(): HasOne
    {
        return $this->hasOne(Invoice::class);
    }

    public function convertToInvoice(?int $createdBy = null): Invoice
    {
        return app(ConvertQuoteToInvoiceAction::class)->execute($this, $createdBy);
    }

    public function recalculateTotals(): void
    {
        // Use BCMath to avoid float precision accumulation on quote totals.
        $subtotal = Money::of((string) $this->items()->sum('line_total'));
        $discount = Money::of((string) $this->discount_total);
        $tax = Money::of((string) $this->tax_total);
        $total = Money::zero()->max($subtotal->subtract($discount)->add($tax));

        $this->forceFill([
            'subtotal' => $subtotal->toString(),
            'total' => $total->toString(),
        ])->saveQuietly();
    }

    public function canBeAccepted(?CarbonInterface $at = null): bool
    {
        // Only draft/sent quotes can be accepted; rejected, cancelled, and
        // already-accepted quotes must not pass through this gate.
        if (! in_array($this->status, ['draft', 'sent', 'expired'], true)) {
            return false;
        }

        if ($this->status !== 'expired') {
            return true;
        }

        if ($this->valid_until === null) {
            return false;
        }

        $graceDays = max(0, (int) config('erp.quotes.expired_acceptance_grace_days', 0));
        $acceptedUntil = now()->parse($this->valid_until)->endOfDay()->addDays($graceDays);

        return ($at ?? now())->lessThanOrEqualTo($acceptedUntil);
    }
}
