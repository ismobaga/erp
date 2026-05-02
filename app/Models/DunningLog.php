<?php

namespace App\Models;

use App\Models\Concerns\HasCompanyScope;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'invoice_id',
    'client_id',
    'stage',
    'channel',
    'sent_at',
    'notes',
    'sent_by',
])]
class DunningLog extends Model
{
    use HasCompanyScope;
    protected function casts(): array
    {
        return [
            'sent_at' => 'datetime',
        ];
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sent_by');
    }

    public function scopeForInvoice(Builder $query, int $invoiceId): Builder
    {
        return $query->where('invoice_id', $invoiceId);
    }

    public function stageLabel(): string
    {
        return (string) __('erp.dunning.stages.' . $this->stage, [], null) ?: ('Stage ' . $this->stage);
    }

    public function channelLabel(): string
    {
        return (string) __('erp.dunning.channels.' . $this->channel, [], null) ?: ucfirst((string) $this->channel);
    }
}
