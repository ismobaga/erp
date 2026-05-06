<?php

namespace App\Models;

use App\Models\Concerns\HasCompanyScope;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

#[Fillable([
    'conversation_id',
    'sendable_type',
    'sendable_id',
    'client_id',
    'phone',
    'type',
    'message',
    'file_path',
    'status',
    'gowa_message_id',
    'ack_status',
    'delivered_at',
    'read_at',
    'response',
    'error_message',
    'sent_by',
    'sent_at',
])]
class WhatsappMessageLog extends Model
{
    use HasCompanyScope;

    protected function casts(): array
    {
        return [
            'response'     => 'array',
            'sent_at'      => 'datetime',
            'delivered_at' => 'datetime',
            'read_at'      => 'datetime',
        ];
    }

    public function sendable(): MorphTo
    {
        return $this->morphTo();
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sent_by');
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(WhatsappConversation::class, 'conversation_id');
    }
}
