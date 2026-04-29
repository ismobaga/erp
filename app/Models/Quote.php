<?php

namespace App\Models;

use App\Services\AuditTrailService;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Facades\DB;

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
class Quote extends Model
{
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
        return DB::transaction(function () use ($createdBy): Invoice {
            // Lock the quote row so that two concurrent requests cannot both
            // pass the existence check and create duplicate invoices.
            // Eager-load items here so the subsequent loop does not trigger
            // an additional query per conversion.
            $fresh = static::query()->with('items')->whereKey($this->getKey())->lockForUpdate()->first();

            if ($fresh->invoice()->exists()) {
                return $fresh->invoice;
            }

            $invoice = Invoice::create([
                'client_id'      => $fresh->client_id,
                'quote_id'       => $fresh->getKey(),
                'issue_date'     => now()->toDateString(),
                'due_date'       => now()->addDays((int) config('erp.billing.invoice_default_due_days', 30))->toDateString(),
                'status'         => 'draft',
                'discount_total' => $fresh->discount_total,
                'tax_total'      => $fresh->tax_total,
                'notes'          => $fresh->notes,
                'created_by'     => $createdBy ?? auth()->id(),
                'updated_by'     => $createdBy ?? auth()->id(),
            ]);

            foreach ($fresh->items as $item) {
                $invoice->items()->create([
                    'service_id'  => $item->service_id,
                    'description' => $item->description,
                    'quantity'    => $item->quantity,
                    'unit_price'  => $item->unit_price,
                    'line_total'  => $item->line_total,
                ]);
            }

            $fresh->forceFill(['status' => 'accepted'])->saveQuietly();

            app(AuditTrailService::class)->log('quote_converted_to_invoice', $invoice, [
                'quote_id'       => $fresh->getKey(),
                'quote_number'   => $fresh->quote_number,
                'invoice_number' => $invoice->invoice_number,
                'converted_by'   => $createdBy ?? auth()->id(),
            ]);

            return $invoice;
        });
    }

    public function recalculateTotals(): void
    {
        $subtotal = (float) $this->items()->sum('line_total');
        $total = max(0, $subtotal - (float) $this->discount_total + (float) $this->tax_total);

        $this->forceFill([
            'subtotal' => $subtotal,
            'total' => $total,
        ])->saveQuietly();
    }

    public function canBeAccepted(?CarbonInterface $at = null): bool
    {
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
