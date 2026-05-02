<?php

namespace App\Models;

use App\Models\Concerns\HasCompanyScope;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'client_id',
    'frequency',
    'start_date',
    'next_due_date',
    'end_date',
    'net_days',
    'description',
    'amount',
    'items',
    'notes',
    'is_active',
    'created_by',
])]
class RecurringInvoice extends Model
{
    use HasCompanyScope;
    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'next_due_date' => 'date',
            'end_date' => 'date',
            'items' => 'array',
            'amount' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function frequencyLabel(): string
    {
        return match ($this->frequency) {
            'daily' => 'Quotidien',
            'weekly' => 'Hebdomadaire',
            'monthly' => 'Mensuel',
            'quarterly' => 'Trimestriel',
            'yearly' => 'Annuel',
            default => ucfirst((string) $this->frequency),
        };
    }

    /**
     * Advance next_due_date by one frequency period.
     */
    public function advanceNextDueDate(): void
    {
        $next = $this->next_due_date->copy();

        $next = match ($this->frequency) {
            'daily' => $next->addDay(),
            'weekly' => $next->addWeek(),
            'monthly' => $next->addMonth(),
            'quarterly' => $next->addMonths(3),
            'yearly' => $next->addYear(),
            default => $next->addMonth(),
        };

        $expired = $this->end_date !== null && $next->gte($this->end_date);

        $this->forceFill([
            'next_due_date' => $next,
            'is_active' => ! $expired,
        ])->save();
    }
}
